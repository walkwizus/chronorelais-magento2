<?php
namespace Chronopost\Chronorelais\Model\Carrier;

class ChronorelaisEurope extends AbstractChronopost
{
    /**
     * @var string
     */
    protected $_code = 'chronorelaiseur';

    const PRODUCT_CODE = '49';
    const PRODUCT_CODE_STR = 'PRU';

    const CHECK_CONTRACT = true;
    const CHECK_RELAI_WS = true;
}