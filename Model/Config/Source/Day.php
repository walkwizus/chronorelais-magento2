<?php

namespace Chronopost\Chronorelais\Model\Config\Source;

class Day implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            array('value' => 'monday', 'label' => __('Monday')),
            array('value' => 'tuesday', 'label' => __('Tuesday')),
            array('value' => 'wednesday', 'label' => __('Wednesday')),
            array('value' => 'thursday', 'label' => __('Thursday')),
            array('value' => 'friday', 'label' => __('Friday')),
            array('value' => 'saturday', 'label' => __('Saturday')),
            array('value' => 'sunday', 'label' => __('Sunday'))
        ];
    }
}