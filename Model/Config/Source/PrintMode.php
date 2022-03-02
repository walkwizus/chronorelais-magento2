<?php

namespace Chronopost\Chronorelais\Model\Config\Source;

class PrintMode implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            'PDF' => __('Print PDF Laser with proof'),
            'SPD' => __('Print PDF laser without proof'),
            'THE' => __('Print PDF thermal')
        ];
    }
}