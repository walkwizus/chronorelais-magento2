define(
    [
        'jquery'
    ],
    function ($) {
        'use strict';

        var body = $('body');

        body.on("change","#etiquette_retour_adresse",function(e){
            $('#etiquette_retour_adresse_value').val($(this).val());
        });

        body.on("click","a.etiquette_retour_link",function(e){
            e.preventDefault();
            var return_adress_type = $('#etiquette_retour_adresse_value').val();
            var newHref = $(this).attr('href');
            newHref += "recipient_address_type/"+return_adress_type+"/";
            window.location.href = newHref;
            return false;
        });
    }
);