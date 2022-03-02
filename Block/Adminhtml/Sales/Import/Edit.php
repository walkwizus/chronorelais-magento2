<?php
namespace Chronopost\Chronorelais\Block\Adminhtml\Sales\Import;


class Edit extends \Magento\Backend\Block\Template
{
    protected $_template = "sales/import/edit.phtml";
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Edit constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->getToolbar()->addChild(
            'reset_button',
            'Magento\Backend\Block\Widget\Button',
            [
                'label' => __('Reset'),
                'onclick' => 'window.location.href = window.location.href',
                'class' => 'reset'
            ]
        );

        $this->getToolbar()->addChild(
            'save_button',
            'Magento\Backend\Block\Widget\Button',
            [
                'label' => __('Import'),
                'class' => 'save primary',
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'save', 'target' => '#sales_import_edit_form']],
                ]

            ]
        );


        return parent::_prepareLayout();
    }

    /**
     * Return edit flag for block
     *
     * @return boolean
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getEditMode()
    {
        return false;
    }

    /**
     * Return header text for form
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __('Import a list of parcel numbers');
    }

    /**
     * Return form block HTML
     *
     * @return string
     */
    public function getForm()
    {
        return $this->getLayout()->createBlock('Chronopost\Chronorelais\Block\Adminhtml\Sales\Import\Edit\Form')->toHtml();
    }

    /**
     * Return action url for form
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('*/*/import_save');
    }

    /**
     * Retrieve Save As Flag
     *
     * @return int
     */
    public function getSaveAsFlag()
    {
        return $this->getRequest()->getParam('_save_as_flag') ? '1' : '';
    }

    /**
     * Getter for single store mode check
     *
     * @return boolean
     */
    protected function isSingleStoreMode()
    {
        return $this->_storeManager->isSingleStoreMode();
    }

    /**
     * Getter for id of current store (the only one in single-store mode and current in multi-stores mode)
     *
     * @return int
     */
    protected function getStoreId()
    {
        return $this->_storeManager->getStore(true)->getId();
    }
}