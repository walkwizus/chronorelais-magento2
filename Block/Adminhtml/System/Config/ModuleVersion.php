<?php
namespace Chronopost\Chronorelais\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\ModuleListInterface;
use Chronopost\Chronorelais\Helper\Data as HelperData;

class ModuleVersion extends Field
{

    protected $_moduleList;

    /**
     * @param Context $context
     * @param ModuleListInterface $moduleList
     * @param array $data
     */
    public function __construct(
        Context $context,
        ModuleListInterface $moduleList,
        array $data = []
    ) {
        $this->_moduleList = $moduleList;
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
        return '<strong>' . $this->_getVersion() . '</strong>';
    }

    /**
     * Return current version of Module
     *
     * @return mixed|string
     */
    protected function _getVersion()
    {
        $infos = $this->_moduleList->getOne(HelperData::MODULE_NAME);

        return is_array($infos) ? $infos['setup_version'] : '';
    }

}