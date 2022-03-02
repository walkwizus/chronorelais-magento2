<?php

namespace Chronopost\Chronorelais\Plugin;

use Chronopost\Chronorelais\Helper\Data as HelperData;

class ShipmentSave
{
    protected $_helperShipment;

    protected $_scopeConfig;

    protected $_orderFactory;
    protected $contractsOrdersFactory;
    protected $request;
    protected $helperWS;

    /**
     * @var HelperData
     */
    protected $_helperData;

    public function __construct(
        \Chronopost\Chronorelais\Helper\Shipment $helperShipment,
        \Chronopost\Chronorelais\Helper\Webservice $helperWS,
        \Chronopost\Chronorelais\Model\ContractsOrdersFactory $contractsOrdersFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\App\RequestInterface $request,
        HelperData $helperData
    )
    {
        $this->_helperShipment = $helperShipment;
        $this->contractsOrdersFactory = $contractsOrdersFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_orderFactory = $orderFactory;
        $this->_helperData = $helperData;
        $this->_helperWS = $helperWS;
        $this->_request = $request;
    }

    public function beforeSave(\Magento\Sales\Model\Order\Shipment $subject)
    {


        if($subject->getData('create_track_to_shipment') === null){

            $subject->setData('create_track_to_shipment', false);

            //$idShipSubject = $subject->getData('entity_id');
            if($subject->getData('entity_id') === null){
                $subject->setData('create_track_to_shipment', true);
            }

        }

        if (!$subject->getData('create_track_to_shipment')) {
            return $subject;
        }

        $order = $subject->getOrder();
        $subject = $subject->loadByIncrementId($subject->getIncrementId()); /* reload pour etre sur de récup les tracks */
        $shippingMethod = $order->getData('shipping_method');
        $shippingMethodCode = explode("_", $shippingMethod);
        $shippingMethodCode = isset($shippingMethodCode[1]) ? $shippingMethodCode[1] : $shippingMethodCode[0];
        $dimensions = json_decode($this->_request->getParam('dimensions'), true);

        for($i = 0; $i < count($dimensions); $i++){
            $dimensionsLimit = $dimensions[$i];
            $error = false;
            $msg = '';
            $weightLimit = $this->_helperData->getWeightLimit($order->getData('shipping_method'));
            $dimLimit = $this->_helperData->getInputDimensionsLimit($order->getData('shipping_method'));
            $globalLimit = $this->_helperData->getGlobalDimensionsLimit($order->getData('shipping_method'));


            if(isset($dimensionsLimit['weight']) && $dimensionsLimit['weight'] > $weightLimit && !$error){
                $msg = __("One or several packages are above the weight limit (%1 kg)",$weightLimit );
                $error = true;
            }
            if(isset($dimensionsLimit['width']) && $dimensionsLimit['width'] > $dimLimit && !$error){
                $msg = __("One or several packages are above the size limit (%1 cm)",$dimLimit);
                $error = true;
            }
            if(isset($dimensionsLimit['height']) && $dimensionsLimit['height'] > $dimLimit && !$error){
                $msg = __("One or several packages are above the size limit (%1 cm)",$dimLimit );
                $error = true;
            }
            if(isset($dimensionsLimit['length']) && $dimensionsLimit['length'] > $dimLimit && !$error){
                $msg = __("One or several packages are above the size limit (%1 cm)",$dimLimit);
                $error = true;
            }

            if(isset($dimensionsLimit['height']) && isset($dimensionsLimit['width']) && isset($dimensionsLimit['length']) && !$error){

                $global = 2*$dimensionsLimit['height'] + $dimensionsLimit['width'] + 2*$dimensionsLimit['length'];
                if($global > $globalLimit){
                    $msg = __("One or several packages are above the total (L+2H+2l) size limit (%1 cm)",$globalLimit );
                    $error = true;
                }

            }

            if($error){
                Throw new \Exception ($msg);
            }
        }



        if ($this->_helperData->isChronoMethod($shippingMethodCode)) { /* methode chronopost */

            $contractId = $this->_request->getParam('contract');
            $contract = null;
            if ($contractId !== null) {
                $contract = $this->_helperData->getSpecificContract($contractId);
            }

            if ($contract === null || $contractId === null) {
                $contract = $this->_helperData->getCarrierContract($shippingMethodCode);
            }

            $result = $this->_helperWS->checkContract($contract);

            if (!$result->return->errorCode) {
                return $subject;
            } else {
                switch ($result->return->errorCode) {
                    case 3:
                        $message = __('An error occured during the label creation. Please check if this contract can edit labels for this carrier.');
                        break;
                    default:
                        $message = __($result->return->errorMessage);
                        break;
                }
                Throw new \Exception ($message);
            }
        }

    }


    /**
     * @param \Magento\Sales\Model\Order\Shipment $subject
     * @return \Magento\Sales\Model\Order\Shipment
     */
    public function afterSave(\Magento\Sales\Model\Order\Shipment $subject)
    {
        $dimensions = $this->_request->getParam('dimensions');
        $nb_colis = (int)$this->_request->getParam('nb_colis');
        $contractId = (int)$this->_request->getParam('contract');

        if(null !== $dimensions){
            $dimensions = json_decode($dimensions, true);
        }
        if(null === $nb_colis){
            $nb_colis = 1;
        }

        if (!$subject->getData('create_track_to_shipment')) { /* pour eviter multiple creation etiquette */
            return $subject;
        }
        $order = $subject->getOrder();
        $trackData = $subject->getTrackData() ? $subject->getTrackData() : array();
        $subject = $subject->loadByIncrementId($subject->getIncrementId()); /* reload pour etre sur de récup les tracks */
        $shippingMethod = $order->getData('shipping_method');
        $shippingMethodCode = explode("_", $shippingMethod);
        $shippingMethodCode = isset($shippingMethodCode[1]) ? $shippingMethodCode[1] : $shippingMethodCode[0];
        if ($this->_helperData->isChronoMethod($shippingMethodCode)) { /* methode chronopost */
            $trackExist = false;
            $tracks = $subject->getAllTracks();
            if (count($tracks)) {
                foreach ($tracks as $track) {
                    if ($track->getData('chrono_reservation_number')) {
                        $trackExist = true;
                        break;
                    }
                }
            }
            /* commande chronopost sans track : on le crée */
            if (!$trackExist) {
                $this->_helperShipment->createTrackToShipment($subject, $trackData, $dimensions, $nb_colis, $contractId);
            }

            $this->linkContractToOrder($subject,$shippingMethodCode);


        }
        return $subject;
    }

    /*
     * à la sauvegarde d'une expédition : verif si creation de plusieurs expédition au lieu d'une
     */
//    public function aroundSave(\Magento\Sales\Model\Order\Shipment $subject, callable $proceed)
//    {
//
//        $canSaveObject = true;
//
//        $order = $subject->getOrder();
//        $shippingMethod = $order->getData('shipping_method');
//        $shippingMethodCode = explode("_", $shippingMethod);
//        $shippingMethodCode = isset($shippingMethodCode[1]) ? $shippingMethodCode[1] : $shippingMethodCode[0];
//        if ($this->_helperData->isChronoMethod($shippingMethodCode)) { /* methode chronopost */
//            $items = $subject->getItems();
//
//            $shippingMethod = explode("_", $shippingMethod);
//            $shippingMethod = $shippingMethod[1];
//            $weight_limit = $this->_scopeConfig->getValue('carriers/' . $shippingMethod . '/weight_limit');
//            $weightShipping = 0;
//            foreach ($items as $item) {
//                $weightShipping += $item->getWeight() * $item->getQty();
//            }
//            if ($this->_scopeConfig->getValue('chronorelais/weightunit/unit') == 'g') {
//                $weightShipping = $weightShipping / 1000; // conversion g => kg
//            }
//            if ($weightShipping > $weight_limit) {
//
//                /* Create one shipment by product ordered */
//                $canSaveObject = false;
//                $order = $this->_orderFactory->create()->loadByIncrementId($order->getIncrementId());
//                foreach ($items as $item) {
//                    for ($i = 1; $i <= $item->getQty(); $i++) {
//                        try {
//                            $this->_helperShipment->createNewShipment($order, array($item->getOrderItemId() => '1'));
//                        } catch (\Exception $e) {
//                            //echo "erreur createNewShipment : ".$e->getMessage()."<br>";
//                        }
//
//                    }
//                }
//                /* return first shipment */
//                $shipments = $order->getShipmentsCollection();
//                if ($shipments) {
//                    $shipment = $shipments->getLastItem();
//                    return $shipment;
//                }
//
//            }
//        }
//
//        if ($canSaveObject) {
//            $result = $proceed();
//            return $result;
//        }
//    }


    private function linkContractToOrder($subject, $shippingMethodCode)
    {

        $order = $subject->getOrder();

        $contractId = $this->_request->getParam('contract');
        $contract = null;

        if ($contractId !== null) {
            $contract = $this->_helperData->getSpecificContract($contractId);
        }

        if ($contract === null && $contractId === null) {
            $contract = $this->_helperData->getCarrierContract($shippingMethodCode);
        }


        $contractOrder = $this->contractsOrdersFactory->create();
        $contractOrder->setData('order_id', $order->getId());
        $contractOrder->setData('contract_name', $contract['name']);
        $contractOrder->setData('contract_account_number', $contract['number']);
        $contractOrder->setData('contract_sub_account_number', $contract['subAccount']);
        $contractOrder->setData('contract_account_password', $contract['pass']);
        $contractOrder->save();

        return true;

    }
}
