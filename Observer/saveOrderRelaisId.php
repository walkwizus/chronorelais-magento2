<?php
namespace Chronopost\Chronorelais\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class saveOrderRelaisId implements ObserverInterface
{
    public function execute(EventObserver $observer)
    {
        $quote = $observer->getQuote();
        $order = $observer->getOrder();
        $order->setRelaisId($quote->getRelaisId());
        return $this;
    }
}