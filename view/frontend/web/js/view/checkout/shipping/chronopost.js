define([
    'uiComponent',
    'Magento_Checkout/js/model/shipping-service',
    'jquery',
    'mage/url',
    'Magento_Checkout/js/model/quote',
    'Chronopost_Chronorelais/js/view/checkout/map',
    'mage/template',
    'Magento_Checkout/js/model/full-screen-loader',
    'Chronopost_Chronorelais/js/view/checkout/relais',
    'Chronopost_Chronorelais/js/view/checkout/rdv',
    'Chronopost_Chronorelais/js/jquery.bxslider.min'

], function (Component, shippingService, $, urlBuilder, quote, leafletMap, mageTemplate, Loader, Relais, Rdv) {
    'use strict';

    return Component.extend({
        templateHtml: {
            logo: "<img src='<%- data.methodLogo %>' style='vertical-align:middle;' alt='<%- data.label %>'/>",
            point_relais : '<li>'+
                '<input name="shipping_method_chronorelais" type="radio" value="<%- data.identifiantChronopostPointA2PAS %>" id="s_method_chronorelais_<%- data.identifiantChronopostPointA2PAS %>" class="radio" <%- data.checked %>/>'+
                '<label for="s_method_chronorelais_<%- data.identifiantChronopostPointA2PAS %>"><%- data.nomEnseigne %> - <%- data.adresse1 %> - <%- data.codePostal %> - <%- data.localite %></label>'+
            '</li>'
        },
        shippingRates: shippingService.getShippingRates(),
        shippingMethod: quote.shippingMethod,
        logos: {},
        carrier_logos_url: urlBuilder.build("chronorelais/ajax/getCarriersLogos"),
        get_relais_url: urlBuilder.build("chronorelais/ajax/getRelais"),
        reset_session_relais_url: urlBuilder.build("chronorelais/ajax/resetSessionRelais"),
        set_session_relais_url: urlBuilder.build("chronorelais/ajax/setSessionRelais"),
        flagBindRelais: false,
        leafletMap: leafletMap,
        xhrSetSessionRelais: false,
        xhrResetSessionRelais: false,
        xhrBindRelais: false,
        showHomeIcon: true,
        get_planning_url: urlBuilder.build("chronorelais/ajax/getPlanning"),
        set_session_rdv_url: urlBuilder.build("chronorelais/ajax/setSessionRdv"),
        reset_session_rdv_url: urlBuilder.build("chronorelais/ajax/resetSessionRdv"),
        xhrSetSessionRdv: false,
        xhrResetSessionRdv: false,
        xhrGetPlanning: false,

        initialize: function () {
            this._super();

            var self = this;

            /* ecouteur sur la variable shippingRates : lorsqu'elle change on verifie les logos chronopost */
            this.shippingRates.subscribe(function (rates) {
                self.updateChronopostCarriersLogo(rates);
                self.showHomeIcon = true;
                self.bindRelais(false, false);
            });

            /* ecouteur sur la variable shippingMethod : lorsqu'elle change on verifie si affichage point relais */
            this.shippingMethod.subscribe(function(shippingMethod) {
                self.showHomeIcon = true;
                self.bindRelais(shippingMethod, false);
            });

            /* resize block rdv mobile */
            $(window).resize(function() {
                self.resizeRdvMobile();
            });
        },

        /***********************************************************************
         ******************* LOGOS Chronopost *********************************
         *********************************************************************/

        /* ajout des logos chronopost */
        updateChronopostCarriersLogo: function(rates) {
            var self = this;
            for(var i = 0; i < rates.length; i++) {
                var carrierCode = rates[i].carrier_code;
                var methodCode = rates[i].method_code;
                if(carrierCode.indexOf("chrono") !== -1) { /* ajout des logos pour les carrier chronopost */
                    if(!self.logos.hasOwnProperty(methodCode)) { /* recup logos en ajax */
                        $.ajax({
                            url: self.carrier_logos_url,
                            method_code: methodCode
                        }).done(function(response) {
                            self.logos = $.extend({},self.logos,response);
                            self.updateCarrierMethodLogo(this.method_code);
                        });
                    } else {
                        self.updateCarrierMethodLogo(methodCode);
                    }
                }
            }
        },
        /* ajoute le logo pour la methode chronopost */
        updateCarrierMethodLogo: function(method_code) {
            var methodLogo = this.logos[method_code];
            var label = $('#label_method_'+method_code+'_'+method_code);
            if(methodLogo && !label.find("img").length) {

                var logoHtml = mageTemplate(this.templateHtml['logo']);
                var tmpl = logoHtml({
                    data: {
                        label: label.text(),
                        methodLogo: methodLogo
                    }
                });
                label.prepend(tmpl);
            }
        },

        /***********************************************************************
         ******************* Chronorelais et RDV *******************************
         *********************************************************************/

        /* Affiche la gmap et les points relais */
        bindRelais: function(shippingMethod, postcode) {
            var self = this;
            if(!shippingMethod) {
                shippingMethod = this.shippingMethod();
            }

            if(this.xhrBindRelais) {
                this.xhrBindRelais.abort();
            }

            /* reset session relais */
            this.resetSessionRelais();

            /* reset session RDV */
            this.resetSessionRdv();

            var mapContainer = $('.chronomap_container');
            if(mapContainer.length) {
                if(!postcode) { /* si pas de postcode : on masque la map */
                    mapContainer.remove();
                    mapContainer = null;
                } else { /* recherche par cp : on masque le bouton valider recherche par cp */
                    var mappostalcodebtn = $('#mappostalcodebtn');
                    if(mappostalcodebtn.length) {
                        mappostalcodebtn.hide();
                    }
                    var postalcode_please_wait = $('#postalcode-please-wait');
                    if(postalcode_please_wait.length) {
                        postalcode_please_wait.show();
                    }
                }
            }
            if(!shippingMethod || !this.isMethodAvailable(shippingMethod['method_code'])) {
                return;
            }
            /* get relais map */
            if(
                shippingMethod
                && typeof shippingMethod['method_code'] != "undefined"
                && shippingMethod['method_code'].indexOf("chronorelais") !== -1
                /*&& $('input[value="'+shippingMethod['carrier_code']+'_'+shippingMethod['method_code']+'"]').is(':checked')*/
            ) {
                if(this.flagBindRelais) { /* pour eviter multiple appel */
                    return;
                }
                this.flagBindRelais = true;
                var shippingAddressTmp = quote.shippingAddress();
                var shippingAddress = {
                    "country_id": shippingAddressTmp["countryId"],
                    "street": shippingAddressTmp["street"] ? shippingAddressTmp["street"] : $('#co-shipping-form').find('input[name="street[0]"]').val(),
                    "postcode": shippingAddressTmp["postcode"],
                    "city": shippingAddressTmp["city"] ? shippingAddressTmp["city"] :  $('#co-shipping-form').find('input[name="city"]').val()
                };

                Loader.startLoader();

                this.xhrBindRelais = $.ajax({
                    url: this.get_relais_url,
                    data: {
                        'method_code': shippingMethod['method_code'],
                        'postcode': postcode,
                        'shipping_address': shippingAddress
                    },
                    type: 'post'
                }).done(function(response) {

                    var mappostalcodebtn = $('#mappostalcodebtn');
                    if(mappostalcodebtn.length) {
                        mappostalcodebtn.show();
                    }

                    var postalcode_please_wait = $('#postalcode-please-wait');
                    if(postalcode_please_wait.length) {
                        postalcode_please_wait.hide();
                    }

                    if(response.error) {
                        alert(response.message);
                        self.flagBindRelais = false;
                        return false;
                    }

                    /* show map */
                    var label = $("#label_method_"+response.method_code+"_"+response.method_code);
                    var trads = response.trads;
                    if(label.length) {
                        var parent = label.parent('tr').get(0);
                        if(mapContainer && mapContainer.length) {
                            mapContainer.replaceWith(response.content);
                        } else {
                            $(parent).after(response.content);
                        }

                        var mapVisible = $("#chronomap_"+response.method_code).length > 0;

                        /* init map */
                        if(mapVisible) {
                            var leafletMap = self.leafletMap.createMap("chronomap_"+response.method_code);
                            leafletMap.setRelayIcon(response['relay_icon']);
                        }

                        if(response.relaypoints && response.relaypoints.length>0) {
                            var relayPointContainer = $('#relaypoint_container_'+response.method_code);
                            var found = false;
                            for(var s=0; s<response.relaypoints.length; s++) {

                                var relayPoint = response.relaypoints[s];

                                /* Ajout point sur la map si map affichée */
                                if(mapVisible) {
                                    leafletMap.addMarker(relayPoint, trads);
                                }

                                /* Ajout point relai bouton radio */
                                var relayPointHtml = mageTemplate(self.templateHtml['point_relais']);
                                if(relayPoint.identifiantChronopostPointA2PAS == response.chronopost_chronorelais_relais_id) {
                                    relayPoint.checked = "checked='checked'";
                                    found = true;
                                }

                                var tmpl = relayPointHtml({
                                    data: relayPoint
                                });
                                relayPointContainer.append(tmpl);

                                var point = $('#s_method_chronorelais_' + relayPoint.identifiantChronopostPointA2PAS);
                                point.click(function () {
                                    self.setSessionRelais($(this).val());
                                    if(mapVisible) {
                                        leafletMap.loadMyPoint($(this).val());
                                    }
                                }).bind();
                                point.on('setSession',function () {
                                    self.setSessionRelais($(this).val());
                                }).bind();
                            }

                            if(!found){
                                var firstPoint = $("input[name='shipping_method_chronorelais']").first();
                                firstPoint.prop('checked', true);
                            }
                        }

                        /* change postal code */
                        var chronomapContainer = $("#chronomap_container_"+response.method_code);
                        if(chronomapContainer.length && chronomapContainer.find('.mappostalcode button')) {
                            chronomapContainer.find('.mappostalcode button').click(function(){
                                self.showHomeIcon = false;
                                self.bindRelais({"method_code":response.method_code},chronomapContainer.find('.mappostalcode input').val());
                            })
                        }

                    }
                }).always(function() {
                    self.flagBindRelais = false;
                    Loader.stopLoader();
                });

            }

            /* Chronopost SRDV */
            if(shippingMethod && typeof shippingMethod['method_code'] != "undefined" && shippingMethod['method_code'].indexOf("chronopostsrdv") !== -1) {
                this.getPlanning(shippingMethod);
            }
        },
        /* vérifie si la methode fais partie des modes valables */
        isMethodAvailable: function(method_code) {
            var rates = this.shippingRates();
            for(var i = 0; i < rates.length; i++) {
                var methodCode = rates[i].method_code;
                if(methodCode == method_code) {
                    return true;
                }
            }
            return false;
        },
        /* vide le relais_id mis en session */
        resetSessionRelais: function(){

            var shippingMethod = this.shippingMethod();
            if(shippingMethod && shippingMethod['method_code'].indexOf("chronorelais")) {
                if(this.xhrResetSessionRelais) {
                    this.xhrResetSessionRelais.abort();
                }
                this.xhrResetSessionRelais = $.ajax({
                    url: this.reset_session_relais_url
                }).done(function(response) {
                    Relais.relaisAddress('');
                });
            }
        },
        /* met le relais_id mis en session */
        setSessionRelais: function(relais_id){

            /* si mode livraison relais pas coché => erreur */
            var shippingMethod = this.shippingMethod();

            if(!shippingMethod || typeof shippingMethod['method_code'] == "undefined") {
                alert("Veuillez sélectionner votre mode de livraison");
                return;
            }

            var inputChrono = $('input[value="'+shippingMethod['carrier_code']+'_'+shippingMethod['method_code']+'"]');
            if(!inputChrono.is(':checked')) {
                inputChrono.prop('checked',true);
            }



            if(this.xhrSetSessionRelais) {
                this.xhrSetSessionRelais.abort();
            }

            Loader.startLoader();
            this.xhrSetSessionRelais = $.ajax({
                url: this.set_session_relais_url,
                data: {
                    relais_id: relais_id
                }
            }).done(function(response) {
                if(response.success) {
                    /* change l'adresse relais : ce qui la change dans la progress-bar */
                    Relais.relaisAddress(response.relais);
                } else if(response.error) {
                    Relais.relaisAddress('');
                    alert(response.message);
                }
            }).always(function() {
                Loader.stopLoader();
            });
        },
        /* affiche planning de RDV */
        getPlanning: function(shippingMethod) {
            var self = this;
            var planningContainer = $('#chronopost_srdv_planning_container');
            var shippingAddressTmp = quote.shippingAddress();
            var shippingAddress = {
                "country_id": shippingAddressTmp["countryId"],
                "street": shippingAddressTmp["street"] ? shippingAddressTmp["street"] : $('#co-shipping-form').find('input[name="street[0]"]').val(),
                "postcode": shippingAddressTmp["postcode"],
                "city": shippingAddressTmp["city"] ? shippingAddressTmp["city"] :  $('#co-shipping-form').find('input[name="city"]').val(),
                "region_id": shippingAddressTmp["regionId"] ? shippingAddressTmp["regionId"] :  $('#co-shipping-form').find('select[name="region_id"]').val(),
                "region_code": shippingAddressTmp["regionCode"] ? shippingAddressTmp["regionCode"] :  ''
            };

            Loader.startLoader();

            if(this.xhrGetPlanning) {
                this.xhrGetPlanning.abort();
            }
            this.xhrGetPlanning = $.ajax({
                url: this.get_planning_url,
                data: {
                    'method_code': shippingMethod['method_code'],
                    'shipping_address': shippingAddress
                },
                type: 'post'
            }).done(function(response) {
                /* show map */
                var label = $("#label_method_"+response.method_code+"_"+response.method_code);
                if(label.length) {
                    var parent = label.parent('tr').get(0);
                    if(planningContainer && planningContainer.length) {
                        planningContainer.replaceWith(response.content);
                    } else {
                        $(parent).after(response.content);
                    }

                    var rdvCarousel = $("#rdvCarouselContent");
                    if(rdvCarousel.length) {
                        self.resizeRdvMobile();
                        rdvCarousel.bxSlider({
                            /** @see : https://github.com/stevenwanderski/bxslider-4/issues/1240 */
                            touchEnabled : (navigator.maxTouchPoints > 0), /** @todo remove when fixed */
                            infiniteLoop: false,
                            pager: false,
                            nextSelector: '.carousel-control.next',
                            prevSelector: '.carousel-control.prev',
                            nextText: $.mage.__('Next week'),
                            prevText: $.mage.__('Previous week'),
                            onSlideAfter: function (slideElement, oldIndex, newIndex) {
                                $('.carousel-control').removeClass('inactive');
                                if(newIndex == 0) {
                                    $('.carousel-control.prev').addClass('inactive');
                                } else if(newIndex == (this.getSlideCount()-1)) {
                                    $('.carousel-control.next').addClass('inactive');
                                }
                            }
                        });
                    }

                    var globalMobile = $('#global-mobile');
                    if(globalMobile.length) {
                        globalMobile.find('th').click(function(){
                            globalMobile.find('th').removeClass('active');
                            $(this).addClass('active');

                            $('#time-list').find('ul').hide();

                            var idUlHoraireDay = $(this).attr('id').replace("th","ul");
                            $('#'+idUlHoraireDay).show();
                        });
                        globalMobile.find('th:first').click();
                    }

                    /* select horaire */
                    $('input.shipping_method_chronopostsrdv').click(function(){
                        self.selectRdvHoraire($(this));
                    });

                }
            }).always(function() {
                Loader.stopLoader();
            });
        },
        selectRdvHoraire: function(input) {
            var slotValue = input.val();
            var slotValueJson = JSON.parse(slotValue);

            /* mise en valeur jour et horaire */
            var rdvCarousel = $("#rdvCarouselContent");
            if(rdvCarousel.length) {
                rdvCarousel.find('th').removeClass('active');
                input.parents('tr:first').find('th').addClass('active');
                rdvCarousel.find('th#th_'+slotValueJson.deliveryDate.substr(0,10)).addClass('active');
            }

            /* mise en session valeur */
            this.setSessionRdv(slotValueJson);
        },
        /* met le chronopostsrdv_creneaux_info mis en session */
        setSessionRdv: function(slotValueJson){
            var self = this;
            if(this.xhrSetSessionRdv) {
                this.xhrSetSessionRdv.abort();
            }
            this.xhrSetSessionRdv = $.ajax({
                url: this.set_session_rdv_url,
                data: {
                    chronopostsrdv_creneaux_info: slotValueJson
                },
                type: 'post'
            }).done(function(response) {
                if(response.success) {
                    /* change RDV info : ce qui la change dans la progress-bar */
                    Rdv.rdvInfo(response.rdvInfo);
                    var currentShippingMethodTitle = quote.shippingMethod().method_title;
                    currentShippingMethodTitle = self.getBaseShippingMethodTitle(currentShippingMethodTitle);
                    quote.shippingMethod().method_title = currentShippingMethodTitle + response.rdvInfo;
                    if($('#label_method_chronopostsrdv_chronopostsrdv').length) { /* on rechange le label du mode par rdv */
                        var shippingMethodTitle = $('#label_method_chronopostsrdv_chronopostsrdv').html();
                        shippingMethodTitle = self.getBaseShippingMethodTitle(shippingMethodTitle);
                        $('#label_method_chronopostsrdv_chronopostsrdv').html(shippingMethodTitle + response.rdvInfo);
                    }
                } else if(response.error) {
                    Rdv.rdvInfo('');
                    alert(response.message);
                }
            });
        },
        /* vide le rdv mis en session */
        resetSessionRdv: function(){

            var shippingMethod = this.shippingMethod();
            if(shippingMethod && shippingMethod['method_code'].indexOf("chronopostsrdv")) {
                var self = this;
                if (this.xhrResetSessionRdv) {
                    this.xhrResetSessionRdv.abort();
                }
                this.xhrResetSessionRdv = $.ajax({
                    url: this.reset_session_rdv_url
                }).done(function (response) {
                    Rdv.rdvInfo('');
                    if ($('#label_method_chronopostsrdv_chronopostsrdv').length) { /* on rechange le label du mode par rdv */
                        var shippingMethodTitle = $('#label_method_chronopostsrdv_chronopostsrdv').html();
                        shippingMethodTitle = self.getBaseShippingMethodTitle(shippingMethodTitle);
                        $('#label_method_chronopostsrdv_chronopostsrdv').html(shippingMethodTitle);
                    }
                });
            }
        },
        getBaseShippingMethodTitle: function(title) {
            return title.replace(/- Le \d{2}\/\d{2}\/\d{2,4} entre \d{1,2}:\d{0,2} et \d{1,2}:\d{0,2}/g,'');
        },
        resizeRdvMobile: function() {
            var globalMobile = $('#global-mobile');
            if(globalMobile.length) {
                globalMobile.css('max-width',($('main').width()-20)+'px');
            }
        }
    });
});
