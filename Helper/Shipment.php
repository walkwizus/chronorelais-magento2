<?php
namespace Chronopost\Chronorelais\Helper;

use Magento\Sales\Model\Order\Shipment\TrackFactory;

use \Magento\Sales\Model\Convert\Order as ConvertOrder;
use \Magento\Shipping\Model\ShipmentNotifier;
use \Magento\Sales\Model\Order\Shipment as OrderShipment;
use \Chronopost\Chronorelais\Model\HistoryLtFactory;

/**
 * gestion des expeditions
 * Class Shipment
 * @package Chronopost\Chronorelais\Helper
 */
class Shipment extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var TrackFactory
     */
    protected $_trackFactory;

    /**
     * @var ConvertOrder
     */
    protected $_convertOrder;

    /**
     * @var ShipmentNotifier
     */
    protected $_shipmentNotifier;

    /**
     * @var Webservice
     */
    protected $_helperWebservice;

    /**
     * @var OrderShipment
     */
    protected $_shipment;


    /**
     * @var HistoryLtFactory
     */
    protected $_ltHistoryFactory;

    /**
     * Shipment constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param TrackFactory $trackFactory
     * @param ConvertOrder $convertOrder
     * @param ShipmentNotifier $shipmentNotifier
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        TrackFactory $trackFactory,
        ConvertOrder $convertOrder,
        ShipmentNotifier $shipmentNotifier,
        Webservice $webservice,
        OrderShipment $shipment,
        HistoryLtFactory $historyLtFactory
    ) {
        parent::__construct($context);
        $this->_trackFactory = $trackFactory;

        $this->_convertOrder = $convertOrder;
        $this->_shipmentNotifier = $shipmentNotifier;
        $this->_helperWebservice = $webservice;
        $this->_shipment = $shipment;
        $this->_ltHistoryFactory = $historyLtFactory;
    }

    /**
     * Creation expedition + etiquette
     * @param \Magento\Sales\Model\Order $order
     * @param array $savedQtys
     * @param array $trackData
     * @return bool|mixed|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createNewShipment(\Magento\Sales\Model\Order $order,$savedQtys = array(), $trackData = array(), $dimensions = null, $nb_colis = 1, $isImport = false) {
        if (!$order->canShip() && !$isImport) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("You can't create a shipment.")
            );
        }
        $shipment = $this->_convertOrder->toShipment($order);
        foreach ($order->getAllItems() AS $orderItem) {

            if (! $orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                continue;
            }

            if (isset($savedQtys[$orderItem->getId()])) {
                $qtyShipped = min($savedQtys[$orderItem->getId()], $orderItem->getQtyToShip());
            } elseif (!count($savedQtys)) {
                $qtyShipped = $orderItem->getQtyToShip();
            } else {
                continue;
            }

            $shipmentItem = $this->_convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);
            $shipment->addItem($shipmentItem);
        }

        /* cas d'import de tracking via le BO */
        $shipment->setTrackData($trackData);

        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);

        if ($shipment->getExtensionAttributes() !== null && !empty($shipment->getExtensionAttributes())){
            $shipment->getExtensionAttributes()->setSourceCode('default');
        }

        $shipment->setData('create_track_to_shipment',true)->save();
        $shipment->getOrder()->save();


        if((!isset($trackData['send_mail']) || (isset($trackData['send_mail']) && $trackData['send_mail']))) {
            if(isset($trackData['comment'])) {
                $shipment->addComment($trackData['comment'],true, $trackData['include_comment']);
            }
            $this->_shipmentNotifier->notify($shipment);

            $shipment->setData('create_track_to_shipment',false)->save();
            $shipment->getOrder()->save();

        }

        return $shipment;
    }

    /**
     * @param OrderShipment $shipment
     * @param array $trackData
     * @return bool
     */
    public function createTrackToShipment(\Magento\Sales\Model\Order\Shipment $shipment, $trackData = array(), $dimensions = null, $nb_colis = 1, $contractId = null) {
        $order = $shipment->getOrder();
        $_shippingMethod = explode("_", $order->getShippingMethod());

            $expedition = false;
            $trackDatas = array();
            $resultParcelValues = array();

            if(count($trackData) > 0) {
                $trackData = array_merge($trackData,array(
                    'parent_id' => $shipment->getId(),
                    'order_id' => $order->getId()
                ));
                $trackDatas[] = $trackData;
            } else {
                $expedition = $this->_helperWebservice->createEtiquette($shipment, 'expedition', 'returninformation', $dimensions, $nb_colis, $contractId);

                if(is_object($expedition->return->resultParcelValue)){
                    array_push($resultParcelValues,$expedition->return->resultParcelValue);
                }
                else{
                    $resultParcelValues = $expedition->return->resultParcelValue;
                }
                
                for($i = 0; $i<count($resultParcelValues) ; $i++){
                    $trackData = array(
                        'track_number' => $resultParcelValues[$i]->skybillNumber,
                        'parent_id' => $shipment->getId(),
                        'order_id' => $order->getId(),
                        'chrono_reservation_number' => $expedition->return->reservationNumber,
                        'carrier' => ucwords($_shippingMethod[1]),
                        'carrier_code' => $_shippingMethod[1],
                        'title' => ucwords($_shippingMethod[1]),
                        'popup' => '1'
                    );

                    if(!isset($dimensions[$i])) {
                        $dimensions[$i] = $dimensions["weight"];
                    }

                   $this->saveLtHistory($shipment->getId(), $resultParcelValues[$i]->skybillNumber, $dimensions[$i]["weight"]);

                    $trackDatas[] = $trackData;
                }

            }

        try {
            foreach ($trackDatas as $trackData){

                $track = $this->_trackFactory->create();
                $track->addData($trackData);
                $shipment->addTrack($track)->setData('create_track_to_shipment', false)->save();
            }

            return true;
        } catch(\Exception $e) {
                var_dump($e->getMessage());
        }
        return false;
    }

    protected function saveLtHistory($shipmentId, $ltNumber, $weight) {
        $ltHistory = $this->_ltHistoryFactory->create();
        $ltHistory->setData('shipment_id', $shipmentId);
        $ltHistory->setData('lt_number', $ltNumber);
        $ltHistory->setData('weight', $weight);
        $ltHistory->save();
    }

    /**
     * @param $incrementId
     * @return OrderShipment
     */
    public function getShipmentByIncrementId($incrementId) {
        $shipment = $this->_shipment->setId(null)->loadByIncrementId($incrementId);
        return $shipment;
    }

    public function getEtiquetteUrl($shipment, $dimensions = null, $trackNumber = null)
    {


        $etiquetteUrl = array();
        if(null !== $trackNumber){

            $track = $this->_trackFactory->create()->getCollection()
                ->addFieldToFilter('track_number', $trackNumber)
                ->getFirstItem();
            $chrono_reservation_number = $track->getData('chrono_reservation_number');

            if(strlen($chrono_reservation_number) > 50){
                $etiquetteUrl[] =  base64_decode($chrono_reservation_number);
            }else{

                $etiquetteUrl[] = base64_decode($this->_helperWebservice->getEtiquetteByReservationNumber($trackNumber));

            }

            return $etiquetteUrl;

        }


        if(!$shipment instanceof \Magento\Sales\Model\Order\Shipment) {
            $shipment = $this->_shipment->setData(null)->load($shipment);
        }


        if ($_shipTracks = $shipment->getAllTracks()) {

            $revisionnumbers = array();
            foreach ($_shipTracks as $_shipTrack) {
                $conditionTrack = true;
                if($trackNumber != null){
                    $conditionTrack = $_shipTrack->getNumber() == $trackNumber;
                }

                if ($_shipTrack->getNumber() && $_shipTrack->getChronoReservationNumber() && $conditionTrack) {
                    $chrono_reservation_number = $_shipTrack->getData('chrono_reservation_number');


                    if(strlen($chrono_reservation_number) > 50){
                        $etiquetteUrl[] = base64_decode($chrono_reservation_number);
                    }else{
                        if(!in_array($chrono_reservation_number , $revisionnumbers)){
                            $revisionnumbers[] = $chrono_reservation_number;
                            $etiquetteUrl[] = base64_decode($this->_helperWebservice->getEtiquetteByReservationNumber($chrono_reservation_number));
                        }

                    }

                }
            }
            if (count($etiquetteUrl) > 0) {
                return $etiquetteUrl;
            }
        }

        /* pas de tracking chronopost */
        return $this->createTrackToShipment($shipment, array(), $dimensions);
    }
}
