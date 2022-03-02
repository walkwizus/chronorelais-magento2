<?php

namespace Chronopost\Chronorelais\Controller\Adminhtml\Sales\Impression;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\App\Filesystem\DirectoryList;

use Magento\Sales\Model\OrderFactory as OrderFactory;
use Magento\Sales\Model\Order\ShipmentRepository;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;

use Chronopost\Chronorelais\Helper\Data as HelperData;
use Chronopost\Chronorelais\Helper\Shipment as HelperShipment;

class generateEtiquette extends AbstractImpression
{
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

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
     * @var ShipmentRepository
     */
    private $shipmentRepository;

    /**
     * PrintEtiquette constructor.
     *
     * @param Context        $context
     * @param DirectoryList  $directoryList
     * @param PageFactory    $resultPageFactory
     * @param OrderFactory   $orderFactory
     * @param HelperData     $helperData
     * @param HelperShipment $helperShipment
     */
    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        PageFactory $resultPageFactory,
        OrderFactory $orderFactory,
        HelperData $helperData,
        HelperShipment $helperShipment,
        ShipmentRepository $shipmentRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilder
    ) {
        parent::__construct($context, $directoryList, $resultPageFactory, $helperData);
        $this->_helperShipment = $helperShipment;
        $this->_orderFactory = $orderFactory;
        $this->shipmentRepository = $shipmentRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $orderId = $this->getRequest()->getParam('order_id');
        $dimensions = json_decode($this->getRequest()->getParam('dimensions'), true);


        $etiquetteUrl = array();
        try {
            if ($orderId) { /* pas encore d'expédition : génération de ou des étiquettes */
                $order = $this->_orderFactory->create()->load($orderId);
                if ($order && $order->getId()) {
                    $shippingMethod = $order->getData('shipping_method');
                    $shippingMethodCode = explode("_", $shippingMethod);
                    $shippingMethodCode = isset($shippingMethodCode[1]) ? $shippingMethodCode[1] : $shippingMethodCode[0];
                    if (!$this->_helperData->isChronoMethod($shippingMethodCode)) { /* methode NON chronopost */
                        Throw new \Exception("Delivery option not Chronopost for order %1", $order->getIncrementId());
                    }

                    for ($i = 0; $i < count($dimensions); $i++) {
                        $dimensionsLimit = $dimensions[$i];
                        $error = false;
                        $msg = array();
                        $weightLimit = $this->_helperData->getWeightLimit($order->getData('shipping_method'));
                        $dimLimit = $this->_helperData->getInputDimensionsLimit($order->getData('shipping_method'));
                        $globalLimit = $this->_helperData->getGlobalDimensionsLimit($order->getData('shipping_method'));


                        if (isset($dimensionsLimit['weight']) && $dimensionsLimit['weight'] > $weightLimit) {
                            $msg[] = __("One or several packages are above the weight limit (%1 kg)", $weightLimit);
                            $error = true;
                        }
                        if (isset($dimensionsLimit['width']) && $dimensionsLimit['width'] > $dimLimit) {
                            $msg[] = __("One or several packages are above the size limit (%1 cm)", $dimLimit);
                            $error = true;
                        }
                        if (isset($dimensionsLimit['height']) && $dimensionsLimit['height'] > $dimLimit) {
                            $msg[] = __("One or several packages are above the size limit (%1 cm)", $dimLimit);
                            $error = true;
                        }
                        if (isset($dimensionsLimit['length']) && $dimensionsLimit['length'] > $dimLimit) {
                            $msg[] = __("One or several packages are above the size limit (%1 cm)", $dimLimit);
                            $error = true;
                        }

                        if (isset($dimensionsLimit['height']) && isset($dimensionsLimit['width']) && isset($dimensionsLimit['length'])) {

                            $global = 2 * $dimensionsLimit['height'] + $dimensionsLimit['width'] + 2 * $dimensionsLimit['length'];
                            if ($global > $globalLimit) {
                                $msg[] = __("One or several packages are above the total (L+2H+2l) size limit (%1 cm)",
                                    $globalLimit);
                                $error = true;
                            }

                        }

                        if ($error) {

                            $this->messageManager->addErrorMessage(__(implode('\n', $msg)));
                            $resultRedirect->setPath("chronopost_chronorelais/sales/impression");

                            return $resultRedirect;

                        }
                    }

                    $_shipments = $order->getShipmentsCollection();
                    $nb_colis = (int)$this->_request->getParam('nb_colis');
                    if ($_shipments->count()) { /* expedition existe deja */
                        $shippmentId = $this->_request->getParam('shipment_id');
                        if ($shippmentId) {
                            $searchCriteriaBuilder = $this->searchCriteriaBuilder->create();

                            $searchCriteriaBuilder->addFilter(
                                'increment_id',
                                $shippmentId,
                                'eq'
                            );

                            $searchCriteria = $searchCriteriaBuilder->create();

                            $_shipment = $this->shipmentRepository->getList($searchCriteria)->getFirstItem();

                        } else {
                            $_shipment = $_shipments->getFirstItem();
                        }
                        $this->createTracksWithNumber($_shipment, $nb_colis, $dimensions);

                    } else { /* creation etiquette */

                        $this->_helperShipment->createNewShipment($order, array(), array(), $dimensions, $nb_colis);

                    }
                }
            }

            $this->messageManager->addSuccessMessage(__("Labels are correctly generated"));
            $resultRedirect->setPath("chronopost_chronorelais/sales/impression");

            return $resultRedirect;


        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
            $resultRedirect->setPath("chronopost_chronorelais/sales/impression");

            return $resultRedirect;
        }
    }

    private function createTracksWithNumber($_shipment, $nb_colis, $dimensions = null)
    {


        $trackData = $_shipment->getTrackData() ? $_shipment->getTrackData() : array();
        $order = $_shipment->getOrder();

        $_shipment = $_shipment->loadByIncrementId($_shipment->getIncrementId()); /* reload pour etre sur de récup les tracks */
        $shippingMethod = $order->getData('shipping_method');
        $shippingMethodCode = explode("_", $shippingMethod);
        $shippingMethodCode = isset($shippingMethodCode[1]) ? $shippingMethodCode[1] : $shippingMethodCode[0];
        if ($this->_helperData->isChronoMethod($shippingMethodCode)) { /* methode chronopost */

            $this->_helperShipment->createTrackToShipment($_shipment, $trackData, $dimensions, $nb_colis);


        }

    }

}
