<?php

namespace Chronopost\Chronorelais\Model\Config\Source;

class FileSeparator implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ';' => __('Semicolon'),
            ',' => __('Comma')
        ];
    }
}