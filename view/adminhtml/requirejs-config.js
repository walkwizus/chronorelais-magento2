var config = {
    deps: [
        "Chronopost_Chronorelais/js/initLinkRetour",
        "Chronopost_Chronorelais/js/initLinkContract",
        "Chronopost_Chronorelais/js/weightAndDimensions",
        "Chronopost_Chronorelais/js/shipmentNew",
        "Chronopost_Chronorelais/js/shipmentDimensions"
    ],
    map: {
        "*": {
            weightAndDimensions : "Chronopost_Chronorelais/js/weightAndDimensions"
        }
    },
    shim: {
        "Chronopost_Chronorelais/js/initLinkRetour": ["jquery"],
        "Chronopost_Chronorelais/js/slick": ["jquery"],
        "Chronopost_Chronorelais/js/initLinkContract": {
            deps: ["jquery"]
        },
        "Chronopost_Chronorelais/js/contracts": {
            deps: ["jquery"]
        },
        "Chronopost_Chronorelais/js/cleanInformations": {
            deps: ["jquery"]
        },
        "Chronopost_Chronorelais/js/weightAndDimensions":  {
            deps: ["jquery"]
        },
        "Chronopost_Chronorelais/js/shipmentNew":  {
            deps: ["jquery"]
        },
        "Chronopost_Chronorelais/js/shipmentDimensions":  {
            deps: ["jquery"]
        }
    }

};
