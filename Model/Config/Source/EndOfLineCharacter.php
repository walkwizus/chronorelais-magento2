<?php

namespace Chronopost\Chronorelais\Model\Config\Source;

class EndOfLineCharacter implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            'lf' => __('LF'),
            'cr' => __('CR'),
            'crlf' => __('CR+LF')
        ];
    }
}