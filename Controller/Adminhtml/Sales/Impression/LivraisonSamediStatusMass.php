<?php
namespace Chronopost\Chronorelais\Controller\Adminhtml\Sales\Impression;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Chronopost\Chronorelais\Model\OrderExportStatusFactory;

class LivraisonSamediStatusMass extends \Magento\Backend\App\Action
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var OrderExportStatusFactory
     */
    protected $_orderExportStatusFactory;

    /**
     * @var Filter
     */
    protected $_filter;

    /**
     * LivraisonSamediStatusMass constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param OrderExportStatusFactory $exportStatusFactory
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        OrderExportStatusFactory $exportStatusFactory,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->_orderExportStatusFactory = $exportStatusFactory;
        $this->_filter = $filter;
        $this->_collectionFactory = $collectionFactory;
    }

    /**
     * Is the user allowed to view the blog post grid.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Chronopost_Chronorelais::sales');
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $status = $this->getRequest()->getParam('status');

        try {
            $collection = $this->_filter->getCollection($this->_collectionFactory->create());
            foreach ($collection->getItems() as $order) {
                $orderStatus = $this->_orderExportStatusFactory->create()->load($order->getId(),'order_id');
                $orderStatus
                    ->setData('order_id',$order->getId())
                    ->setData('livraison_le_samedi',$status)
                    ->save();
            }

        } catch(\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }
        $resultRedirect->setPath("chronopost_chronorelais/sales/impression");
        return $resultRedirect;

    }

}