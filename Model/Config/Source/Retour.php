<?php

namespace Chronopost\Chronorelais\Model\Config\Source;

class Retour implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            'shipperinformation' => __("Shipment address"),
            'customerinformation' => __('Invoicing address'),
            'returninformation' => __('Return address')
        ];
    }
}