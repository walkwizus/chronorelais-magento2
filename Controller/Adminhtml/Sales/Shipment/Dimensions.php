<?php
namespace Chronopost\Chronorelais\Controller\Adminhtml\Sales\Shipment;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\LayoutFactory;


class Dimensions extends \Magento\Backend\App\Action
{

    protected $resultJsonFactory;
    protected $layoutFactory;
    protected $helperData;
    protected $_shippingMethod;
    protected $_orderId;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param LayoutFactory $layoutFactory
     *
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        LayoutFactory $layoutFactory,
        \Chronopost\Chronorelais\Helper\Data $helperData
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->layoutFactory = $layoutFactory;
        $this->helperData = $helperData;
        $this->_shippingMethod = $context->getRequest()->getParam('shipping_method');
        $this->_orderId = $context->getRequest()->getParam('order_id');
    }


    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $result = $this->resultJsonFactory->create();
        $layout = $this->layoutFactory->create();

        $data['html'] = $layout->getLayout()
            ->createBlock(\Chronopost\Chronorelais\Block\Adminhtml\Sales\Shipment\Dimensions::class, '',
                [
                    'data' => [
                        'shipping_method' => $this->_shippingMethod,
                        'order_id' => $this->_orderId
                    ]
                ])
            ->setTemplate('Chronopost_Chronorelais::sales/shipment/dimensions.phtml')
            ->toHtml();

        $data['error'] = $data['html'] !== null ? 0 : 1;

        return $result->setData($data);
    }


}