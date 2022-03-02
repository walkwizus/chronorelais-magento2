<?php
namespace Chronopost\Chronorelais\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Chronopost\Chronorelais\Helper\Webservice;

class Enabled extends Field
{

    protected $helperWS;

    /**
     * @param Context $context
     * @param Webservice $helperWS
     * @param array $data
     */
    public function __construct(
        Context $context,
        Webservice $helperWS,
        array $data = []
    ) {
        $this->helperWS = $helperWS;
        parent::__construct($context, $data);
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $id = $element->getId();
        $carrier = explode('_', $id);
        $carrier = $carrier[1];

        if(!$this->helperWS->shippingMethodEnabled($carrier)) {
            $element->setDisabled('disabled');
            $element->setValue(0);
        }

        return parent::_getElementHtml($element).$this->_toHtml();
    }
}