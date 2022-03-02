<?php

namespace Chronopost\Chronorelais\Model\Config\Source;

use Chronopost\Chronorelais\Helper\Data;

class Contract implements \Magento\Framework\Option\ArrayInterface
{

    protected $helper;

    public function __construct(
        Data $helper
    )
    {

        $this->helper = $helper;

    }

    /**
     * @return array
     */
    public function toOptionArray()
    {

        $contracts = $this->helper->getConfigContracts();

        $to_return = array();
        foreach ($contracts as $number => $contract) {
            array_push($to_return, array(
                'value'=>$number, 'label'=>$contract["name"]
            ));
        }


        return $to_return;
    }
}