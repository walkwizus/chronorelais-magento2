<?php

namespace Chronopost\Chronorelais\Model\Config\Source;

class Minute implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
    {
        $minute = array();
        for($i = 0; $i <= 59; $i++) {
            $minute_str = str_pad($i, 2, '0',STR_PAD_LEFT);
            $minute[] = array('value' => $minute_str, 'label' => $minute_str);
        }
        return $minute;
    }
}