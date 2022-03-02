<?php
namespace Chronopost\Chronorelais\Model\Carrier;

class Chronopost extends AbstractChronopost
{
    /**
     * @var string
     */
    protected $_code = 'chronopost';

    const PRODUCT_CODE = '01';
    const PRODUCT_CODE_STR = '13H';

    /* option boite au lettre disponible pour ce mode */
    const OPTION_BAL_ENABLE = true;
    const PRODUCT_CODE_BAL = '58';
    const PRODUCT_CODE_BAL_STR = '13H BAL';

    /* autoriser la livraison le samedi */
    const DELIVER_ON_SATURDAY = true;
}