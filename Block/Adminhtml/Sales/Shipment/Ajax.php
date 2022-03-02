<?php
namespace Chronopost\Chronorelais\Block\Adminhtml\Sales\Shipment;


class Ajax extends \Magento\Shipping\Block\Adminhtml\Create\Items
{

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Data $salesData,
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        array $data = []
    ) {
        parent::__construct($context, $stockRegistry, $stockConfiguration, $registry, $salesData, $carrierFactory,
            $data);
    }

    public function getDimensionsUrl() {
        return $this->getUrl("chronopost_chronorelais/sales_shipment/dimensions");
    }
}