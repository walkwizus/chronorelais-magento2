<?php
namespace Chronopost\Chronorelais\Model\Config\Backend;

class Date extends \Magento\Framework\App\Config\Value
{
    public function beforeSave(){
        $range = $this->getValue();
        $this->setValue($range[0].':'.$range[1].':'.$range[2]);
        return $this;
    }
}
