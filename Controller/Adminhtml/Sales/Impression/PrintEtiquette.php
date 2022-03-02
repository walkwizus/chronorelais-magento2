<?php
namespace Chronopost\Chronorelais\Controller\Adminhtml\Sales\Impression;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\App\Filesystem\DirectoryList;

use Magento\Sales\Model\OrderFactory as OrderFactory;

use Chronopost\Chronorelais\Helper\Data as HelperData;
use Chronopost\Chronorelais\Helper\Shipment as HelperShipment;

class PrintEtiquette extends AbstractImpression
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var HelperShipment
     */
    protected $_helperShipment;

    /**
     * PrintEtiquette constructor.
     * @param Context $context
     * @param DirectoryList $directoryList
     * @param PageFactory $resultPageFactory
     * @param OrderFactory $orderFactory
     * @param HelperData $helperData
     * @param HelperShipment $helperShipment
     */
    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        PageFactory $resultPageFactory,
        OrderFactory $orderFactory,
        HelperData $helperData,
        HelperShipment $helperShipment
    ) {
        parent::__construct($context,$directoryList,$resultPageFactory,$helperData);
        $this->_helperShipment = $helperShipment;
        $this->_orderFactory = $orderFactory;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $orderId = $this->getRequest()->getParam('order_id');

        $etiquetteUrl = array();
        try {
            if(!$orderId) { /* expedition existante */
                $shipmentId = $this->getRequest()->getParam('shipment_id');
                $trackNumber = $this->getRequest()->getParam('track_number');
                if ($shipmentId && null === $trackNumber) {
                    $etiquetteUrl = $this->_helperShipment->getEtiquetteUrl($shipmentId);
                } else if($trackNumber &&  null === $shipmentId) {

                    $etiquetteUrl = $this->_helperShipment->getEtiquetteUrl($shipmentId, null, $trackNumber);

                }
            }

            if(count($etiquetteUrl)) {
                if(count($etiquetteUrl) === 1) {
                    $this->prepareDownloadResponse('Etiquette_chronopost.pdf',  $etiquetteUrl[0]);
                } else { /* plusieurs etiquettes générées */
                    if($this->gsIsActive()) {
                        $this->_processDownloadMass($etiquetteUrl);
                    } else {
                        $this->messageManager->addNoticeMessage(__("This order contains several shipments, click the link to obtain the labels"));
                        $resultRedirect->setPath("chronopost_chronorelais/sales/impression");
                        return $resultRedirect;
                    }
                }
            }

        } catch(\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
            $resultRedirect->setPath("chronopost_chronorelais/sales/impression");
            return $resultRedirect;
        }
    }

}
