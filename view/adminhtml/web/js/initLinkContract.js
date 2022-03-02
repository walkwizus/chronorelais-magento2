define(
    [
        'jquery', 'weightAndDimensions'
    ],
    function ($, weightAndDimensions) {
        'use strict';

        var body = $('body');

        body.on("change", "select[id^='contract-']", function () {

            var entity_id = $(this).data('entityid');
            var form = $('#form_' + entity_id);

            form.find('input[name="contract"]').val($(this).val());

            /*var shipmentid = $(this).data('id');
            var contract = $('#contract-' + shipmentid);
            var idcontract = contract.val();
            var forms = $('.form_' + shipmentid);

            var input = $('input[data-orderid="'+ shipmentid + '"][data-position="1"][name="weight_input"]');
            if(!weightAndDimensions.correctDims(input, false)) {
                return false;
            } else {
                if (idcontract) {
                    forms.each(function() {
                        var action = $(this).attr('action');
                        $(this).attr('action', action + "contract/" + idcontract + "/");
                    });
                }
                contract.attr('disabled','disabled');
                $(this).parent('form').submit();
            }

            return false;*/

        });

        body.on('click', "form[id^='form_'] > button[type='submit']", function() {
            var form = $(this).parent();
            var id = form.find('input[name="order_id"]').val();
            if($("#messages > .id-" + id).length !== 0) {
                return false;
            }
        })

    }
);
