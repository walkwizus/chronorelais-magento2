<?php
namespace Chronopost\Chronorelais\Block\Adminhtml\Sales\Shipment;

use \Magento\Backend\Block\Template\Context;
use \Chronopost\Chronorelais\Helper\Webservice as HelperWS;

class Dimensions extends \Magento\Framework\View\Element\Template
{

    private $_helperData;

    public function __construct(
        Context $context,
        HelperWS $_helperWS,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_helperData = $_helperWS;
    }

    public function getContractsHtml($orderId) {
        return $this->_helperData->getContractsHtml($orderId);
    }

}
