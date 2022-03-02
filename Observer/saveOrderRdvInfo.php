<?php
namespace Chronopost\Chronorelais\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class saveOrderRdvInfo implements ObserverInterface
{
    public function execute(EventObserver $observer)
    {
        $quote = $observer->getQuote();
        $order = $observer->getOrder();
        $order->setData('chronopostsrdv_creneaux_info',$quote->getData('chronopostsrdv_creneaux_info'));
        return $this;
    }
}