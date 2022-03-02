<?php
namespace Chronopost\Chronorelais\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class RdvConfig extends Field
{
    protected $_template = 'Chronopost_Chronorelais::system/config/rdvconfig.phtml';

    /**
     * Date constructor.
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
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
        $html = parent::_getElementHtml($element);
        $this->addData(
            array(
                'element' => $element
            )
        );
        return $html.$this->_toHtml();
    }

}