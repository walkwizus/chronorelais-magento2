<?php

namespace Chronopost\Chronorelais\Model\Config\Source;

class FileExtension implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            '.txt' => __('.txt'),
            '.csv' => __('.csv'),
            '.chr' => __('.chr')
        ];
    }
}