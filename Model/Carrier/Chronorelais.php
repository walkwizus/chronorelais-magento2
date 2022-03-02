<?php
namespace Chronopost\Chronorelais\Model\Carrier;

class Chronorelais extends AbstractChronopost
{
    /**
     * @var string
     */
    protected $_code = 'chronorelais';

    const PRODUCT_CODE = '86';
    const CARRIER_CODE = 'chronorelais';
    const PRODUCT_CODE_STR = 'PR';

    const CHECK_RELAI_WS = true;

    /* autoriser la livraison le samedi */
    const DELIVER_ON_SATURDAY = true;
}