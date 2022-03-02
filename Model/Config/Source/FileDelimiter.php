<?php

namespace Chronopost\Chronorelais\Model\Config\Source;

class FileDelimiter implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            'none' => __('None'),
            'simple_quote' => __('Simple quote'),
            'double_quotes' => __('Double quotes')
        ];
    }
}