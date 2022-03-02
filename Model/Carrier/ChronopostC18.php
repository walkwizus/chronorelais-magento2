<?php
namespace Chronopost\Chronorelais\Model\Carrier;

class ChronopostC18 extends AbstractChronopost
{
    /**
     * @var string
     */
    protected $_code = 'chronopostc18';

    const PRODUCT_CODE = '16';
    const PRODUCT_CODE_STR = '18H';

    const CHECK_CONTRACT = true;

    /* option boite au lettre disponible pour ce mode */
    const OPTION_BAL_ENABLE = true;
    const PRODUCT_CODE_BAL = '2M';
    const PRODUCT_CODE_BAL_STR = '18H BAL';

    /* autoriser la livraison le samedi */
    const DELIVER_ON_SATURDAY = true;
}