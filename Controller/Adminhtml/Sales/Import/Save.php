<?php

namespace Chronopost\Chronorelais\Controller\Adminhtml\Sales\Import;

use Chronopost\Chronorelais\Helper\Shipment;
use Magento\Backend\App\Action\Context;
use Chronopost\Chronorelais\Helper\Data as HelperData;
use Chronopost\Chronorelais\Helper\Shipment as HelperShipment;

use Magento\Framework\File\Csv;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\HTTP\PhpEnvironment\Request as RequestPhp;
use \Magento\Framework\App\Config\Storage\WriterInterface;
use \Magento\Sales\Model\Order\Shipment as OrderShipment;

class Save extends \Magento\Backend\App\Action
{

    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var HelperShipment
     */
    protected $_helperShipment;

    /**
     * @var Csv
     */
    protected $_fileCsv;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var RequestPhp
     */
    protected $_requestPhp;

    /**
     *  @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    protected $_configWriter;


    /**
     * @var \Magento\Sales\Model\Order\Shipment
     */
    protected $_shipment;

    /**
     * @param Context        $context
     * @param HelperShipment $helperShipment
     * @param HelperData     $helperData
     * @param Csv            $csv
     * @param OrderFactory   $orderFactory
     * @param RequestPhp     $requestPhp
     * @param WriterInterface $configWriter
     * @param Shipment $shipment
     */
    public function __construct(
        Context $context,
        HelperShipment $helperShipment,
        HelperData $helperData,
        Csv $csv,
        OrderFactory $orderFactory,
        RequestPhp $requestPhp,
        WriterInterface $configWriter,
        Shipment $shipment
    ) {
        parent::__construct($context);
        $this->_helperData = $helperData;
        $this->_helperShipment = $helperShipment;
        $this->_fileCsv = $csv;
        $this->_orderFactory = $orderFactory;
        $this->_requestPhp = $requestPhp;
        $this->_configWriter = $configWriter;
        $this->_shipment = $shipment;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $chronoFile = $this->_requestPhp->getFiles('import_chronorelais_file');
            if ($this->getRequest()->getParams() && $chronoFile && !empty($chronoFile['tmp_name'])) {
                $trackingTitle = $this->_requestPhp->getPost('import_chronorelais_tracking_title');
                $numberColumnParcel = $this->_requestPhp->getPost('import_chronorelais_column_parcel');
                $numberColumnOrder = $this->_requestPhp->getPost('import_chronorelais_column_order');
                if (!$trackingTitle) {
                    Throw new \Exception(__("Please enter a title for the tracking"));
                }
                if ($numberColumnParcel == null || $numberColumnParcel == "" || !is_numeric($numberColumnParcel)) {
                    Throw new \Exception(__("Veuillez renseigner un numéro de colonne contenant le numéro du colis."));
                }
                if ($numberColumnOrder == null || $numberColumnOrder == "" || !is_numeric($numberColumnOrder)) {
                    Throw new \Exception(__("Veuillez renseigner un numéro de colonne contenant le numéro de commande."));
                }
                $this->_configWriter->save('chronopost/import/number_column_parcel', $numberColumnParcel);
                $this->_configWriter->save('chronopost/import/number_column_order', $numberColumnOrder);
                $numberColumnParcel--;
                $numberColumnOrder--;
                if($this->_importChronorelaisFile($chronoFile['tmp_name'], $trackingTitle, $numberColumnParcel, $numberColumnOrder)) {
                    $this->messageManager->addSuccessMessage(__("The parcels have been imported"));
                }
            } else {
                Throw new \Exception(__("Please select a file"));
            }

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }

        $resultRedirect->setPath("chronopost_chronorelais/sales/import");

        return $resultRedirect;
    }

    /**
     * @param $fileName
     * @param $trackingTitle
     */
    protected function _importChronorelaisFile($fileName, $trackingTitle, $numberColumnParcel, $numberColumnOrder)
    {
        /**
         * File handling
         **/
        ini_set('auto_detect_line_endings', true);
        $csvData = $this->_fileCsv->setDelimiter(';')->getData($fileName);

        /**
         * Get configuration
         */
        $sendEmail = $this->_helperData->getConfig("chronorelais/import/send_mail");
        $comment = $this->_helperData->getConfig("chronorelais/import/shipping_comment");
        $includeComment = $this->_helperData->getConfig("chronorelais/import/include_comment");

        /**
         * $k is line number
         * $v is line content array
         */
        foreach ($csvData as $k => $v) {

            /**
             * Check if current line is header or contains non-numerical value
             */
            if (!is_numeric($v[$numberColumnOrder])) {
                continue;
            }

            /**
             * End of file has more than one empty lines
             */
            if (count($v) <= 1 && !strlen($v[0])) {
                continue;
            }

            /**
             * Get fields content
             */
            if(!isset($v[$numberColumnOrder]) || !isset($v[$numberColumnParcel])){
                continue;
            }

            $orderId = $v[$numberColumnOrder];
            $trackingNumbers = $v[$numberColumnParcel];

            /**
             * Try to load the order
             */
            $order = $this->_orderFactory->create()->loadByIncrementId($orderId);
            if (!$order->getId()) {
                $this->messageManager->addErrorMessage(__("The order %1 does not exist", $orderId));
                continue;
            }

            /**
             * Try to create a shipment
             */
            try {
                $_shippingMethod = explode("_", $order->getShippingMethod());

                if (!$this->_helperData->isChronoMethod($_shippingMethod[1])) { /* methode chronopost */
                    $carrier_code = $_shippingMethod[1];
                    $popup = 1;
                } else {
                    $carrier_code = 'custom';
                    $popup = 0;
                }

                $shipmentCreated = false;
                $trackingNumbers = explode(',', trim($trackingNumbers, '[]'));
                foreach ($trackingNumbers as $trackingNumber) {
                    $_shipmentsCollection = $order->getShipmentsCollection();
                    $trackingNumber = trim($trackingNumber);
                    $trackData = array(
                        'track_number'    => $trackingNumber,
                        'carrier'         => ucwords($carrier_code),
                        'carrier_code'    => $carrier_code,
                        'title'           => $trackingTitle,
                        'popup'           => $popup,
                        'send_mail'       => $sendEmail,
                        'comment'         => $comment,
                        'include_comment' => $includeComment
                    );
                    if($shipmentCreated || count($_shipmentsCollection) > 0) {
                        if(!$shipmentCreated) {
                            $this->_shipment = $_shipmentsCollection->getFirstItem();
                        }
                        $this->_helperShipment->createTrackToShipment($this->_shipment, $trackData, null, 1, null);
                    } else {
                        $this->_shipment = $this->_helperShipment->createNewShipment($order, array(), $trackData, null, 1, true);
                        $shipmentCreated = true;
                    }

                    $this->messageManager->addSuccessMessage(__("The shipment with the number %1 has been created for order %2",
                        $trackingNumber, $orderId));
                }

            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }

        }//foreach

        return $this->messageManager->getMessages()->getCount() == 0;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Chronopost_Chronorelais::sales');
    }

}