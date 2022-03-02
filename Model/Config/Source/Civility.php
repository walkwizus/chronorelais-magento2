<?php

namespace Chronopost\Chronorelais\Model\Config\Source;

class Civility implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            'E' => __('Mrs.'),
            'L' => __('Miss'),
            'M' => __('Mr')
        ];
    }
}