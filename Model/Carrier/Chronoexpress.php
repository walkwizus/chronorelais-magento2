<?php
namespace Chronopost\Chronorelais\Model\Carrier;

class Chronoexpress extends AbstractChronopost
{
    /**
     * @var string
     */
    protected $_code = 'chronoexpress';

    const PRODUCT_CODE = '17';
    const PRODUCT_CODE_STR = 'EI';
}