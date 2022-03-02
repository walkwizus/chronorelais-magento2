<?php
namespace Chronopost\Chronorelais\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Chronopost\Chronorelais\Helper\Data;

class CleanButton extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Chronopost_Chronorelais::system/config/cleanbutton.phtml';
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
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->addData(array(
            'html_id' => $element->getHtmlId()
        ));

        return $this->_toHtml();
    }

    public function getLabelButtonCleanInformations()
    {
        return __("Clean Informations");
    }

}