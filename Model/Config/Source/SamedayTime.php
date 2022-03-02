<?php

namespace Chronopost\Chronorelais\Model\Config\Source;

class SamedayTime implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
    {
        $time = array();
        for($i = 7; $i <= 15; $i++) {
            $timeStr = str_pad($i,2,'0',STR_PAD_LEFT).":00";
            $time[$timeStr] = $timeStr;
            if($i < 15) {
                $timeStr = $i.":30";
                $time[$timeStr] = $timeStr;
            }
        }
        return $time;
    }
}