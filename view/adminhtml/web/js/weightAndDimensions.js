define(['jquery', 'mage/translate'],

    function ($, tr) {


        'use strict';
        var body = $('body');
        var weightAndDimensions = {};

        $(document).ready(function(){

            if (body.hasClass('adminhtml-order-shipment-new') || body.hasClass('adminhtml-order_shipment-new')) {
                onloadWeightShipment();
            }
            if ($('input[name="weight_input"]').data('inshipment') == true) {
                setEventChange($('input[name="weight_input"]'), true);
            }

        })

        function onloadWeightShipment(){
            if ($('input[name="weight_input"]').length == 0) {
                setTimeout(onloadWeightShipment, 1000);
                return;
            }
            setEventChange($('input[name="weight_input"]'), true);
        }

        body.on('keyup', 'input[name="weight_input"]', function () {
            var inShipment = false;
            if ($(this).data('inshipment') == true) {
                inShipment = true;
            }
            setEventChange(this, inShipment);
        });

        body.on('keyup', 'input[name="weight_input"]', function () {
            var inShipment = false;
            if ($(this).data('inshipment') == true) {
                inShipment = true;
            }
            setEventChange(this, inShipment);
        });

        body.on('keyup', 'input[name="width_input"]', function () {
            var inShipment = false;
            if ($(this).data('inshipment') == true) {
                inShipment = true;
            }
            setEventChange(this, inShipment);
        });

        body.on('keyup', 'input[name="height_input"]', function () {
            var inShipment = false;
            if ($(this).data('inshipment') == true) {
                inShipment = true;
            }
            setEventChange(this, inShipment);
        });

        body.on('keyup', 'input[name="length_input"]', function () {
            var inShipment = false;
            if ($(this).data('inshipment') == true) {
                inShipment = true;
            }
            setEventChange(this, inShipment);
        });


        body.on('load', 'input[name="nb_colis"]', function () {
            if ($(this).data('inshipment') == true) {
                $("#input_dimensions").val(buildDimensionsObject($(this), true));
            }
        });

        body.on('keyup', 'input[name="nb_colis"]', function () {
            $(this).val($(this).val().replace(/\D/g, ""));
            var nb_colis = $(this).val();
            var order_id = $(this).data('orderid');
            var inShipment = false;
            if ($(this).data('inshipment') == true) {
                inShipment = true;
            }
            reloadInputsDim(nb_colis, order_id, inShipment);
            if (inShipment) {
                $("#input_dimensions").val(buildDimensionsObject($(this), inShipment));
            }

        });

        function reloadInputsDim(nb_colis, order_id, inShipment) {
            if (nb_colis < 1 || nb_colis > 60) {
                return false;
            }

            var weightElems = $("input[name='weight_input'][data-orderid='" + order_id + "']");

            if (weightElems.length > nb_colis) {

                for (var i = weightElems.length; i > nb_colis; i--) {

                    $("input[name='weight_input'][data-orderid='" + order_id + "']").last().remove();
                    $("input[name='width_input'][data-orderid='" + order_id + "']").last().remove();
                    $("input[name='height_input'][data-orderid='" + order_id + "']").last().remove();
                    $("input[name='length_input'][data-orderid='" + order_id + "']").last().remove();

                }

            } else if (weightElems.length < nb_colis) {
                for (var i = 0; i < nb_colis - weightElems.length; i++) {
                    addRowDimensions(order_id, inShipment);
                }
            }

            setEventChange(weightElems.first(), inShipment);
        }

        function addRowDimensions(order_id, inshipment = false) {

            var weightElems = $("input[name='weight_input'][data-orderid='" + order_id + "']");
            var widthElems = $("input[name='width_input'][data-orderid='" + order_id + "']");
            var heightElems = $("input[name='height_input'][data-orderid='" + order_id + "']");
            var lenghtElems = $("input[name='length_input'][data-orderid='" + order_id + "']");

            var containerWeight = null;
            var containerWidth = null;
            var containerHeight = null;
            var containerLength = null;
            if (inshipment) {

                containerWeight = weightElems.parent("td.weight_input");
                containerWidth = widthElems.parent("td.width_input");
                containerHeight = heightElems.parent("td.height_input");
                containerLength = lenghtElems.parent("td.length_input");

            } else {

                containerWeight = weightElems.parent("div.data-grid-cell-content");
                containerWidth = widthElems.parent("div.data-grid-cell-content");
                containerHeight = heightElems.parent("div.data-grid-cell-content");
                containerLength = lenghtElems.parent("div.data-grid-cell-content");

            }

            var lastPos = $("input[name='weight_input'][data-orderid='" + order_id + "']").last().data('position');
            containerWeight.append(getDefaultInputWeight(order_id, lastPos));
            containerWidth.append(getDefaultInputWidth(order_id, lastPos));
            containerHeight.append(getDefaultInputHeight(order_id, lastPos));
            containerLength.append(getDefaultInputLength(order_id, lastPos));
        }

        function getDefaultInputWeight(order_id, pos) {
            var firstInput = $("input[name='weight_input'][data-orderid='" + order_id + "']").first();
            var newInput = firstInput.clone();
            newInput.val(firstInput.val());
            newInput.attr('data-position', parseInt(pos) + 1);
            return newInput[0];
        }

        function getDefaultInputWidth(order_id, pos) {
            var newInput = $("input[name='width_input'][data-orderid='" + order_id + "']").first().clone();
            newInput.val(1);
            newInput.attr('data-position', parseInt(pos) + 1);
            return newInput[0];
        }

        function getDefaultInputHeight(order_id, pos) {
            var newInput = $("input[name='height_input'][data-orderid='" + order_id + "']").first().clone();
            newInput.val(1);
            newInput.attr('data-position', parseInt(pos) + 1);
            return newInput[0];
        }

        function getDefaultInputLength(order_id, pos) {
            var newInput = $("input[name='length_input'][data-orderid='" + order_id + "']").first().clone();
            newInput.val(1);
            newInput.attr('data-position', parseInt(pos) + 1);
            return newInput[0];
        }

        function setEventChange(input, inShipment = false) {
            $(input).val($(input).val().replace(/[^0-9\.]/g, ""));
            var orderId = $(input).data("orderid");
            if (!correctDimension(input, inShipment)) {
                return false;
            }
            if (inShipment) {
                $("#input_dimensions").val(buildDimensionsObject(input, inShipment));
            } else {
                $('.form_' + orderId + ' input[name="dimensions"]').val(buildDimensionsObject(input));
            }
        }

        function buildDimensionsObject(input, inShipment = false) {
            var dimensions = {};
            var orderId = $(input).data('orderid');
            var weights, widths, heights, lengths;

            if (inShipment) {
                weights = $('#dimensions-weight input[name="weight_input"][data-orderid="' + orderId + '"]');
                widths = $('#dimensions-weight input[name="width_input"][data-orderid="' + orderId + '"]');
                heights = $('#dimensions-weight input[name="height_input"][data-orderid="' + orderId + '"]');
                lengths = $('#dimensions-weight input[name="length_input"][data-orderid="' + orderId + '"]');
            } else {
                weights = $('.data-grid input[name="weight_input"][data-orderid="' + orderId + '"]');
                widths = $('.data-grid input[name="width_input"][data-orderid="' + orderId + '"]');
                heights = $('.data-grid input[name="height_input"][data-orderid="' + orderId + '"]');
                lengths = $('.data-grid input[name="length_input"][data-orderid="' + orderId + '"]');
            }

            for (var i = 0; i < weights.length; i++) {
                var dimension = {};
                dimension.weight = $(weights[i]).val();
                dimension.width = $(widths[i]).val();
                dimension.height = $(heights[i]).val();
                dimension.length = $(lengths[i]).val();
                dimensions[i] = dimension;
            }

            return JSON.stringify(dimensions);
        }

        function getWeightLimit(input) {
            var shippingMethod = $(input).data('shipping-method');
            if (shippingMethod === 'chronorelaiseurope_chronorelaiseurope' || shippingMethod === 'chronorelaiseur_chronorelaiseur' || shippingMethod === 'chronorelais_chronorelais') {
                return 20;
            } else {
                return 30;
            }
        }

        function getInputDimensionsLimit(input) {
            var shippingMethod = $(input).data('shipping-method');
            if (shippingMethod === 'chronorelaiseurope_chronorelaiseurope' || shippingMethod === 'chronorelaiseur_chronorelaiseur' || shippingMethod === 'chronorelais_chronorelais') {
                return 100;
            } else {
                return 150;
            }
        }

        function getGlobalDimensionsLimit(input) {
            var shippingMethod = $(input).data('shipping-method');
            if (shippingMethod === 'chronorelaiseurope_chronorelaiseurope' || shippingMethod === 'chronorelaiseur_chronorelaiseur' || shippingMethod === 'chronorelais_chronorelais') {
                return 250;
            } else {
                return 300;
            }
        }

        function correctDimension(input, inShipment = false) {
            var allInputs;
            var orderId = $(input).data('orderid');
            var weightLimit = getWeightLimit(input);
            var dimensionsLimit = getInputDimensionsLimit(input);
            var globalDimensionsLimit = getGlobalDimensionsLimit(input);

            if (inShipment) {
                allInputs = $('.dimensions-input-container input');
            } else {
                allInputs = $('.data-grid input[data-orderid="' + orderId + '"][data-position]');
            }
            var errorLimitWeight = checkWeightLimit(allInputs, weightLimit);
            var errorLimitDimensions = checkDimensionsLimit(allInputs, dimensionsLimit);
            var errorLimitGlobalDimensions = checkGlobalDimensions(allInputs, globalDimensionsLimit);

            if (errorLimitWeight) {
                addErrorDimensions(orderId, 'weight', weightLimit, dimensionsLimit, globalDimensionsLimit);
            } else {
                removeErrorDimensions(orderId, 'weight');
            }

            if (errorLimitDimensions) {
                addErrorDimensions(orderId, 'length', weightLimit, dimensionsLimit, globalDimensionsLimit);
            } else {
                removeErrorDimensions(orderId, 'length');
            }

            if (errorLimitGlobalDimensions) {
                addErrorDimensions(orderId, 'global', weightLimit, dimensionsLimit, globalDimensionsLimit);
            } else {
                removeErrorDimensions(orderId, 'global');
            }

            var correct = !errorLimitWeight && !errorLimitDimensions && !errorLimitGlobalDimensions;

            if (correct) {
                if ($('#messages').children().length === 0) {
                    enableButtonSubmit();
                }
            } else {
                disableButtonSubmit();
            }

            return correct;
        }

        function checkWeightLimit(allInputs, weightLimit) {
            var error = false;
            allInputs.each(function (i) {
                if ($(allInputs[i]).attr('name') === 'weight_input') {
                    if ($(allInputs[i]).val() > weightLimit) {
                        error = true;
                    }
                }
            });

            return error;
        }


        function checkDimensionsLimit(allInputs, dimensionsLimit) {
            var error = false;
            allInputs.each(function (i) {
                if ($(allInputs[i]).attr('name') !== 'weight_input') {
                    if ($(allInputs[i]).val() > dimensionsLimit) {
                        error = true;
                    }
                }
            });

            return error;
        }

        function checkGlobalDimensions(allInputs, globalDimensionsLimit) {
            var pos = $(allInputs[allInputs.length - 1]).data('position');
            var error = false;
            var globalDimensions;

            for (var i = 1; i <= pos; i++) {
                globalDimensions = 0;
                allInputs.each(function (j) {
                    var currInput = $(allInputs[j]);

                    if (currInput.data('position') == i && currInput.attr('name') !== 'weight_input') {
                        if (currInput.attr('name') == 'width_input') {
                            globalDimensions += parseInt(currInput.val());
                        } else {
                            globalDimensions += 2 * parseInt(currInput.val());
                        }
                    }
                });
                if (globalDimensions > globalDimensionsLimit) {
                    error = true;
                }
            }

            return error;
        }

        function disableButtonSubmit() {
            $(".adminhtml-order_shipment-new button.save.submit-button, .adminhtml-order-shipment-new button.save.submit-button").prop('disabled', true);
        }

        function enableButtonSubmit() {
            $(".adminhtml-order_shipment-new button.save.submit-button, .adminhtml-order-shipment-new button.save.submit-button").prop('disabled', false);
        }

        function removeErrorDimensions(orderId, typeError) {
            $('.' + typeError + '.messages.id-' + orderId).remove();
        }

        function addErrorDimensions(orderId, type, weightLimit, lengthLimit, globalLimit) {
            var ul = $('#messages');
            var ulCreated = false;
            if (ul.length === 0) {
                ulCreated = true;
                ul = document.createElement('ul');
                ul.setAttribute('id', 'messages');
            }

            var containerMessage = document.createElement('div');
            containerMessage.setAttribute('class', type + ' messages id-' + orderId);

            var div = document.createElement('div');
            div.setAttribute('class', 'message message-error error');
            var errorMsg;

            switch (type) {
                case "weight":
                    errorMsg = document.createTextNode(($.mage.__('One or several packages are above the weight limit (%1 kg)')).replace('%1', weightLimit) + ' (' + $.mage.__('Order %1').replace('%1', orderId) + ')');
                    break;
                case "length" :
                    errorMsg = document.createTextNode($.mage.__('One or several packages are above the size limit (%1 cm)').replace('%1', lengthLimit) + ' (' + $.mage.__('Order %1').replace('%1', orderId) + ')');
                    break;
                case "global" :
                    errorMsg = document.createTextNode($.mage.__('One or several packages are above the total (L+2H+2l) size limit (%1 cm)').replace('%1', globalLimit) + ' (' + $.mage.__('Order %1').replace('%1', orderId) + ')');
                    break;
            }
            div.appendChild(errorMsg);

            containerMessage.appendChild(div);
            if ($('.' + type + '.id-' + orderId).length === 0) {
                if (ulCreated) {
                    ul.appendChild(containerMessage);
                } else {
                    ul.append(containerMessage);
                }

                if (ulCreated && $('.messages.id-' + orderId).length === 0) {
                    $('#anchor-content').prepend(ul);
                }
            }
        }

        body.on('submit', 'form[id^="form_"]', function () {
            $(this).find('button[type="submit"]').prop("disabled", true);
        });
    }
);
