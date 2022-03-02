<?php
namespace Chronopost\Chronorelais\Model\Carrier;

class ChronorelaisDom extends AbstractChronopost
{
    /**
     * @var string
     */
    protected $_code = 'chronorelaisdom';

    const PRODUCT_CODE = '4P';
    const PRODUCT_CODE_STR = 'PRDOM';

    const CHECK_CONTRACT = true;
    const CHECK_RELAI_WS = true;
}