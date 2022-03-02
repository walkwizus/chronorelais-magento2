<?php
namespace Chronopost\Chronorelais\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Chronopost\Chronorelais\Helper\Data;

class Contracts extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Chronopost_Chronorelais::system/config/contracts.phtml';
    public $helper;

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
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
        return parent::_getElementHtml($element).$this->_toHtml();
    }
    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function getConfigContracts()
    {
        return $this->helper->getConfigContracts();
    }

    public function getAjaxUrl()
    {
        return $this->getUrl('chronopost_chronorelais/system_config/checklogin');
    }

    public function getLabelButtonDelete()
    {
        return __("Delete contract"); ;
    }

    public function getLabelButtonCheck()
    {
        return __("Check contract"); ;
    }

    public function getLabelButtonCreate()
    {
        return __("Add contract"); ;
    }




}