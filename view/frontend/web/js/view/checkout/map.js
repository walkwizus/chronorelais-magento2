define(
    [
        'jquery',
        'Chronopost_Chronorelais/js/leaflet'
    ],
    function ($) {
        "use strict";
        return {
            map: '',
            bounds: '',
            markers: [],
            relayIcon: false,

            /**
             * Initialize Leaflet map
             *
             * @param elementId
             * @returns {exports}
             */
            createMap: function(elementId) {
                this.map =  L.map(elementId).setView([0, 0], 12);
                this.markers = [];
                this.marker_group = L.featureGroup();
                this.marker_group.addTo(this.map);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(this.map);
                return this;
            },

            /**
             * Add a marker to the map
             *
             * @param relayPoint
             * @param trads
             */
            addMarker: function(relayPoint,trads) {
                var self = this;
                self.createdTabbedMarker(relayPoint,trads);
            },

            /**
             * Create a marker with its own popup
             *
             * @param relayPoint
             * @param trads
             */
            createdTabbedMarker: function(relayPoint,trads) {
                var self = this;
                var marker = L.marker([relayPoint.latitude, relayPoint.longitude], {icon: L.icon({
                        iconUrl: self.relayIcon,
                        iconSize: [45, 30], // size of the icon
                    })
                });
                var relayPoindId = relayPoint.identifiantChronopostPointA2PAS;
                var popup =
                    '<div>' +
                        '<div><h2>' + trads.informations + '</h2>'+self.getMarkerInfoContent(relayPoint)+'</div>' +
                        '<div><h2>' + trads.horaires + '</h2><div style="padding-left:5px">'+self.getHorairesTab(relayPoint, true, trads)+'</div></div>' +
                    '</div>';

                // Save marker and add it to leaflet marker group
                self.markers[relayPoindId] = marker;
                marker.addTo(this.marker_group).bindPopup(popup).on('click', function() {
                    $('#s_method_chronorelais_' + relayPoindId).prop('checked', true);
                });
                this.map.fitBounds(this.marker_group.getBounds()) // Fit map with marker_group bounds
            },

            /**
             *  Get marker information
             *
             * @param relayPoint
             * @returns {string}
             */
            getMarkerInfoContent: function(relayPoint){
                return "<div class=\"sw-map-adresse-wrp\" style=\"padding-left:5px;\">"
                    + "<h2>"+relayPoint.nomEnseigne+"</h2>"
                    + "<div class=\"sw-map-adresse\">"
                    + this.parseAdresse(relayPoint)
                    + relayPoint.codePostal + " " + relayPoint.localite
                    + "</div></div>";
            },

            /**
             * Get "horaires" tab
             *
             * @param anArray
             * @param highlight
             * @param trads
             * @returns {string}
             */
            getHorairesTab: function (anArray, highlight, trads)
            {
                var userAgent = navigator.userAgent.toLowerCase();
                var msie = /msie/.test( userAgent ) && !/opera/.test( userAgent );

                var rs = "<table id=\"sw-table-horaire\" class=\"sw-table\"";
                if(msie) {
                    rs +=  " style=\"width:auto;\"";
                }
                rs +=  ">"
                    + "<tr><td>" + trads.lundi + "</td>"+ this.parseHorairesOuverture(anArray.horairesOuvertureLundi, 1, highlight, trads) +"</tr>"
                    + "<tr><td>" + trads.mardi + "</td>"+ this.parseHorairesOuverture(anArray.horairesOuvertureMardi, 2, highlight, trads) +"</tr>"
                    + "<tr><td>" + trads.mercredi + "</td>"+ this.parseHorairesOuverture(anArray.horairesOuvertureMercredi, 3, highlight, trads) +"</tr>"
                    + "<tr><td>" + trads.jeudi + "</td>"+ this.parseHorairesOuverture(anArray.horairesOuvertureJeudi, 4, highlight, trads) +"</tr>"
                    + "<tr><td>" + trads.vendredi + "</td>"+ this.parseHorairesOuverture(anArray.horairesOuvertureVendredi, 5, highlight, trads) +"</tr>"
                    + "<tr><td>" + trads.samedi + "</td>"+ this.parseHorairesOuverture(anArray.horairesOuvertureSamedi, 6, highlight, trads) +"</tr>"
                    + "<tr><td>" + trads.dimanche + "</td>"+ this.parseHorairesOuverture(anArray.horairesOuvertureDimanche, 0, highlight, trads) +"</tr>"
                    + "</table>" ;
                return rs ;
            },

            /**
             * Parse address
             *
             * @param anArray
             * @returns {string}
             */
            parseAdresse: function(anArray)
            {
                var address = anArray.adresse1 + "<br />" ;
                if (anArray.adresse2)
                    address += anArray.adresse2 + "<br />" ;
                if (anArray.adresse3)
                    address += anArray.adresse3 + "<br />" ;
                return address ;
            },

            /**
             * Parse "horaires ouverture"
             *
             * @param value
             * @param day
             * @param highlight
             * @param trads
             * @returns {string|string}
             */
            parseHorairesOuverture: function(value , day, highlight, trads)
            {
                var rs = "" ;
                var attributedCell = "" ;
                var reg = new RegExp(" ", "g");
                var horaires = value.split(reg) ;

                for (var i=0; i < horaires.length; i++)
                {
                    attributedCell = "" ;

                    // so, re-format time
                    if (horaires[i] === "00:00-00:00")
                    {
                        horaires[i] = "<td "+attributedCell+">"+trads.ferme+"</td>" ;
                    }
                    else
                    {
                        horaires[i] = "<td "+attributedCell+">"+horaires[i]+"</td>" ;
                    }
                    // yeah, concatenates result to the returned value
                    rs += horaires[i] ;
                }

                return rs ;
            },

            /**
             * Set relay icon used to create marker
             *
             * @param icon
             */
            setRelayIcon: function(icon) {
                this.relayIcon = icon;
            },

            /**
             * Load specific point
             *
             * @param relayPointId
             */
            loadMyPoint: function(relayPointId) {
                var self = this;
                self.markers[relayPointId].openPopup();
            },
        };
    }
);

