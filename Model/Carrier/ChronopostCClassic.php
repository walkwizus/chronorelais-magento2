<?php
namespace Chronopost\Chronorelais\Model\Carrier;

class ChronopostCClassic extends AbstractChronopost
{
    /**
     * @var string
     */
    protected $_code = 'chronocclassic';

    const PRODUCT_CODE = '44';
    const PRODUCT_CODE_STR = 'CClassic';

    const CHECK_CONTRACT = true;

}