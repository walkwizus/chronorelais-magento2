<?php

namespace Chronopost\Chronorelais\Model\Config\Source;

class FileCharset implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            'ISO-8859-1' => __('ISO-8859-1'),
            'UTF-8' => __('UTF-8'),
            'ASCII-7' => __('ASCII-7 Bits')
        ];
    }
}