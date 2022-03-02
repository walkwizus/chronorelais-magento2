<?php
namespace Chronopost\Chronorelais\Block\Adminhtml\Sales\Order\View;

use Chronopost\Chronorelais\Helper\Data as HelperData;

class Info extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
    /**
     * @var HelperData
     */
    protected $_helperData;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        HelperData $helperData,
        array $data = []
    ) {
        parent::__construct($context, $registry, $adminHelper, $data);
        $this->_helperData = $helperData;
    }

    /**
     * @return bool
     */
    public function hasOptionBAL() {
        return $this->_helperData->hasOptionBAL($this->getOrder());
    }

    /**
     * @return int|mixed
     */
    public function getOrderAdValorem() {
        $adValoremAmount = $this->_helperData->getOrderAdValorem($this->getOrder());
        return $adValoremAmount ? $this->getOrder()->formatPrice($adValoremAmount) : 0;
    }
}