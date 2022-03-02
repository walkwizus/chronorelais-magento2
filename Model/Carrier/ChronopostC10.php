<?php
namespace Chronopost\Chronorelais\Model\Carrier;

class ChronopostC10 extends AbstractChronopost
{
    /**
     * @var string
     */
    protected $_code = 'chronopostc10';

    const PRODUCT_CODE = '02';
    const PRODUCT_CODE_STR = '10H';

    const CHECK_CONTRACT = true;

    /* autoriser la livraison le samedi */
    const DELIVER_ON_SATURDAY = true;
}