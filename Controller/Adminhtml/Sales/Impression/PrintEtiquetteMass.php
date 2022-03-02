<?php

namespace Chronopost\Chronorelais\Controller\Adminhtml\Sales\Impression;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\App\Filesystem\DirectoryList;

use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

use Chronopost\Chronorelais\Helper\Data as HelperData;
use Chronopost\Chronorelais\Helper\Shipment as HelperShipment;

class PrintEtiquetteMass extends AbstractImpression
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
     * PrintEtiquetteMass constructor.
     * @param Context $context
     * @param DirectoryList $directoryList
     * @param PageFactory $resultPageFactory
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param HelperData $helperData
     * @param HelperShipment $helperShipment
     */
    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        PageFactory $resultPageFactory,
        Filter $filter,
        CollectionFactory $collectionFactory,
        HelperData $helperData,
        HelperShipment $helperShipment
    )
    {
        parent::__construct($context, $directoryList, $resultPageFactory, $helperData);
        $this->_helperShipment = $helperShipment;
        $this->_filter = $filter;
        $this->_collectionFactory = $collectionFactory;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $collection = $this->_filter->getCollection($this->_collectionFactory->create());
            if ($collection->getItems() > 1 && !$this->gsIsActive()) {
                throw new \Exception(__("Please install Ghostscript on your server for bulk printing"));
            }

            $etiquetteUrl = array();
            foreach ($collection->getItems() as $order) {

                if ($order && $order->getId()) {
                    $shippingMethod = $order->getData('shipping_method');
                    $shippingMethodCode = explode("_", $shippingMethod);
                    $shippingMethodCode = isset($shippingMethodCode[1]) ? $shippingMethodCode[1] : $shippingMethodCode[0];
                    if (!$this->_helperData->isChronoMethod($shippingMethodCode)) { /* methode NON chronopost */
                        Throw new \Exception("Delivery option not Chronopost for order %1", $order->getIncrementId());
                    }


                    $_shipments = $order->getShipmentsCollection();

                    if ($_shipments->count()) { /* expedition existe deja */

                        /* si 1 seule expédition : on recup l'url de l'étiquette */
                        if ($_shipments->count() == 1) {
                            /* @TOOO récup url etiquette existante */
                            $_shipment = $_shipments->getFirstItem();

                            $etiquetteUrl = array_merge($etiquetteUrl, $this->_helperShipment->getEtiquetteUrl($_shipment));

                        } else {
                            if ($this->gsIsActive()) {
                                foreach ($_shipments as $_shipment) {
                                    $etiquetteUrl = array_merge($etiquetteUrl, $this->_helperShipment->getEtiquetteUrl($_shipment));
                                }
                            } else {
                                $this->messageManager->addNoticeMessage(__("This order contains several shipments, click the link to obtain the labels"));
                                $resultRedirect->setPath("chronopost_chronorelais/sales/impression");
                                return $resultRedirect;
                            }
                        }
                    }
                }

            }
            if (count($etiquetteUrl)) {
                if (count($etiquetteUrl) === 1) {

                    $this->prepareDownloadResponse('Etiquette_chronopost.pdf', $etiquetteUrl[0]);
                } else { /* plusieurs etiquettes générées */
                    if ($this->gsIsActive()) {
                        $this->_processDownloadMass($etiquetteUrl);
                    } else {
                        $this->messageManager->addNoticeMessage(__("This order contains several shipments, click the link to obtain the labels"));
                        $resultRedirect->setPath("chronopost_chronorelais/sales/impression");
                        return $resultRedirect;
                    }
                }
            }else{
                $this->messageManager->addNoticeMessage(__("Aucune LT pour les commande(s) selectionnée(s)."));
                $resultRedirect->setPath("chronopost_chronorelais/sales/impression");
                return $resultRedirect;
            }

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
            $resultRedirect->setPath("chronopost_chronorelais/sales/impression");
            return $resultRedirect;
        }
    }

}
