define(['jquery'],

    function ($) {
        'use strict';

        return function (config) {
            $.ajax({
                url: config.urldimensionsajax,
                method: 'GET',
                data: {shipping_method: config.shippingmethod, order_id:config.orderid}
            }).done(function(response){
                if(response.error === 0 ){
                    $('.order-payment-method + .order-shipping-address').append(response.html);

                }
            });
        }
    }
);