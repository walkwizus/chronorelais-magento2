<?php

namespace Chronopost\Chronorelais\Model\Config\Source;

class WeightUnit implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            'kg' => __('Kilogram'),
            'g' => __('gram')
        ];
    }
}