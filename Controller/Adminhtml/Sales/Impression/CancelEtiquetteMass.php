<?php
namespace Chronopost\Chronorelais\Controller\Adminhtml\Sales\Impression;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\App\Filesystem\DirectoryList;

use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

use Chronopost\Chronorelais\Helper\Data as HelperData;
use Chronopost\Chronorelais\Helper\Shipment as HelperShipment;
use Chronopost\Chronorelais\Helper\Webservice as HelperWebservice;
use Chronopost\Chronorelais\Model\ContractsOrdersFactory;

class CancelEtiquetteMass extends AbstractImpression
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
     * @var Filter
     */
    protected $_filter;

    /**
     * @var HelperShipment
     */
    protected $_helperShipment;

    /**
     * @var HelperWebservice
     */
    protected $_helperWebservice;
    /**
     * @var ContractsOrders
     */
    private $contractsOrders;

    /**
     * CancelEtiquetteMass constructor.
     * @param Context $context
     * @param DirectoryList $directoryList
     * @param PageFactory $resultPageFactory
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param HelperData $helperData
     * @param HelperShipment $helperShipment
     * @param HelperWebservice $helperWebservice
     */
    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        PageFactory $resultPageFactory,
        Filter $filter,
        CollectionFactory $collectionFactory,
        HelperData $helperData,
        HelperShipment $helperShipment,
        HelperWebservice $helperWebservice,
        ContractsOrdersFactory $contractsOrders
    ) {
        parent::__construct($context,$directoryList,$resultPageFactory,$helperData);
        $this->_helperShipment = $helperShipment;
        $this->_helperWebservice = $helperWebservice;
        $this->_filter = $filter;
        $this->_collectionFactory = $collectionFactory;
        $this->contractsOrders = $contractsOrders;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $nbEtiquettesDelete = 0;
            $error = array();
            $collection = $this->_filter->getCollection($this->_collectionFactory->create());
            foreach ($collection->getItems() as $order) {
                $_shipments = $order->getShipmentsCollection();
                if($_shipments->count()) {
                    foreach($_shipments as $_shipment) {
                        $tracks = $_shipment->getAllTracks();
                        $contract = $this->_helperData->getContractByOrderId($order->getId());

                        foreach($tracks as $track) {
                            /* numero chrono si getChronoReservationNumber non null */
                            if($track->getChronoReservationNumber()) {

                                /* appel WS pour annuler LT */
                                $webservbt = $this->_helperWebservice->cancelEtiquette($track->getNumber(), $contract);
                                if($webservbt) {
                                    /* suppression du numéro de tracking */
                                    if($webservbt->return->errorCode == 0) {
                                        $nbEtiquettesDelete++;
                                        $track->delete();
                                    } else {
                                        switch($webservbt->return->errorCode) {
                                            case "1" :
                                                $errorMessage = __("A system error has occurred");
                                                break;
                                            case "2" :
                                                $errorMessage = __("The parcel’s parameters do not fall within the scope of the the contract passed or it has not yet been registered in the Chronopost tracking system");
                                                break;
                                            case "3" :
                                                $errorMessage = __("The parcel cannot be cancelled because it has been dispatched by Chronopost");
                                                break;
                                            default :
                                                $errorMessage = '';
                                                break;
                                        }
                                        $error[] = __("An error occurred while deleting labels %1: %2.",$track->getNumber(),$errorMessage);
                                    }
                                } else {
                                    $error[] = __("Sorry, an error occurred while deleting label %1. Please contact Chronopost or try again later",$track->getNumber());
                                }
                            }
                        }
                    }
                }

                if(empty($error)) {
                    $contractOrder = $this->contractsOrders->create()->getCollection()->addFieldToFilter('order_id', $order->getId())->getFirstItem();
                    $contractOrder->delete();
                }
            }
        } catch(\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
            $resultRedirect->setPath("chronopost_chronorelais/sales/impression");
            return $resultRedirect;
        }

        if($nbEtiquettesDelete > 0) {
            if($nbEtiquettesDelete > 1) {
                $this->messageManager->addSuccessMessage(__('%1 shipping labels have been cancelled.',$nbEtiquettesDelete));
            } else {
                $this->messageManager->addSuccessMessage(__('%1 shipping label has been cancelled.',$nbEtiquettesDelete));
            }
        }
        if(count($error)) {
            foreach($error as $err) {
                $this->messageManager->addErrorMessage(__($err));
            }
        }
        $resultRedirect->setPath("chronopost_chronorelais/sales/impression");
        return $resultRedirect;

    }

}
