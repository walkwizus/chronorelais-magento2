require(['jquery'],

    function ($) {
        'use strict';
        var fn =  function () {

                buildDimensionsJson();

                $(".adminhtml-order_shipment-new #edit_form, .adminhtml-order-shipment-new #edit_form").on('save', function(e){
                    buildDimensionsJson();
                    return false;
                });

                function buildDimensionsJson() {
                    var dimensions = {};

                    var weights = jQuery('.dimensions-input-container input[name="weight_input"]');
                    var widths = jQuery('.dimensions-input-container input[name="width_input"]');
                    var heights = jQuery('.dimensions-input-container input[name="height_input"]');
                    var lengths = jQuery('.dimensions-input-container input[name="length_input"]');

                    for (var i = 0; i < weights.length; i++) {
                        var dimension = {};
                        dimension.weight = jQuery(weights[i]).val();
                        dimension.width = jQuery(widths[i]).val();
                        dimension.height = jQuery(heights[i]).val();
                        dimension.length = jQuery(lengths[i]).val();
                        dimensions[i] = dimension;
                    }
                    jQuery('#input_dimensions').val(JSON.stringify(dimensions));
                }
        };

        fn();
    }
);