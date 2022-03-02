<?php

namespace Chronopost\Chronorelais\Model\Config\Source;

class Hour implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
    {
        $hour = array();
        for($i = 0; $i <= 23; $i++) {
            $hour_str = str_pad($i, 2, '0',STR_PAD_LEFT);
            $hour[] = array('value' => $hour_str, 'label' => $hour_str);
        }
        return $hour;
    }
}