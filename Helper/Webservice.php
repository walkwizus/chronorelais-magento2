<?php

namespace Chronopost\Chronorelais\Helper;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Shipping\Model\CarrierFactory;
use Magento\Framework\Locale\Resolver;
use \Magento\Backend\Model\Auth\Session as AuthSession;

use Chronopost\Chronorelais\Helper\Data as HelperData;

use Magento\Sales\Model\Order\AddressFactory;
use \Magento\Framework\Stdlib\DateTime\DateTime;

class Webservice extends \Magento\Framework\App\Helper\AbstractHelper
{

    const WS_QUICKCOST = "https://www.chronopost.fr/quickcost-cxf/QuickcostServiceWS?wsdl";
    const WS_SHIPPING_SERVICE = "https://www.chronopost.fr/shipping-cxf/ShippingServiceWS?wsdl";
    const WS_TRACKING_SERVICE = "https://www.chronopost.fr/tracking-cxf/TrackingServiceWS?wsdl";
    const WS_RELAIS_SERVICE = "http://wsshipping.chronopost.fr/soap.point.relais/services/ServiceRechercheBt?wsdl";
    const WS_RELAIS_POINTRELAIS = "https://www.chronopost.fr/recherchebt-ws-cxf/PointRelaisServiceWS?wsdl";
    const WS_RELAI_SECOURS = "http://mypudo.pickup-services.com/mypudo/mypudo.asmx?wsdl";
    const WS_RDV_CRENEAUX = "https://www.chronopost.fr/rdv-cxf/services/CreneauServiceWS?wsdl";

    protected $methodsAllowed = false;

    /**
     * @var CarrierFactory
     */
    protected $_carrierFactory;

    /**
     * @var Resolver
     */
    protected $_resolver;

    /**
     * @var AuthSession
     */
    protected $_authSession;

    /**
     * @var Data
     */
    protected $_helperData;

    /**
     * @var AddressFactory
     */
    protected $_addressFactory;

    /**
     * @var DateTime
     */
    protected $_datetime;
    /**
     * @var OrderRepositoryInterface
     */
    private $_orderRepository;

    /**
     * Webservice constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param CarrierFactory $carrierFactory
     * @param Resolver $resolver
     * @param AuthSession $authSession
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        \Magento\Framework\App\Helper\Context $context,
        CarrierFactory $carrierFactory,
        Resolver $resolver,
        AuthSession $authSession,
        HelperData $helperData,
        AddressFactory $addressFactory,
        DateTime $dateTime
    )
    {
        parent::__construct($context);
        $this->_carrierFactory = $carrierFactory;
        $this->_resolver = $resolver;
        $this->_authSession = $authSession;
        $this->_helperData = $helperData;
        $this->_addressFactory = $addressFactory;
        $this->_datetime = $dateTime;
        $this->_orderRepository = $orderRepository;
    }

    /**
     * @param $wsParams
     * @param string $quickcost_url
     * @return bool
     */
    public function checkLogin($wsParams, $quickcost_url = '')
    {
        if (!$quickcost_url) {
            $quickcost_url = static::WS_QUICKCOST;
        }
        try {
            $client = new \SoapClient($quickcost_url);
            $webservbt = $client->calculateProducts($wsParams);
            return $webservbt;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $wsParams
     * @param string $quickcost_url
     * @return bool
     */
    public function getQuickcost($wsParams, $quickcost_url = '')
    {
        if (!$quickcost_url) {
            $quickcost_url = static::WS_QUICKCOST;
        }
        try {
            $client = new \SoapClient($quickcost_url);
            $webservbt = $client->quickCost($wsParams);
            return $webservbt->return;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Return true si la méthode de livraison fait partie du contrat
     * @param $code
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @return bool
     */
    public function getMethodIsAllowed($code, \Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        try {

            $address = $this->_addressFactory->create();
            $address->setCountryId($request->getDestCountryId());
            $address->setPostcode($request->getDestPostcode());
            $address->setCity($request->getDestCity());

            $methodAllowed = $this->getMethods($address, $code);

            if (!empty($methodAllowed) && in_array($code, $methodAllowed)) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Address $address
     * @return array|bool
     */
    public function getMethods(\Magento\Sales\Model\Order\Address $address, $carrierCode)
    {
        $accountNumber = '';
        $accountPassword = '';
        $contract = $this->_helperData->getCarrierContract($carrierCode);
        if ($contract != null) {
            $accountNumber = $contract['number'];
            $accountPassword = $contract['pass'];
        }
        try {
            if ($this->methodsAllowed === false) {
                $this->methodsAllowed = array();
                $client = new \SoapClient(static::WS_QUICKCOST, array('trace' => 0, 'connection_timeout' => 10));
                $params = array(
                    'accountNumber' => $accountNumber,
                    'password' => $accountPassword,
                    'depCountryCode' => $this->scopeConfig->getValue("chronorelais/shipperinformation/country"),
                    'depZipCode' => $this->scopeConfig->getValue("chronorelais/shipperinformation/zipcode"),
                    'arrCountryCode' => $this->getFilledValue($address->getCountryId()),
                    'arrZipCode' => $this->getFilledValue($address->getPostcode()),
                    'arrCity' => $address->getCity() ? $this->getFilledValue($address->getCity()) : '-',
                    'type' => 'M',
                    'weight' => 1
                );
                $webservbt = $client->calculateProducts($params);
                if ($webservbt->return->errorCode == 0 && isset($webservbt->return->productList)) {
                    if (is_array($webservbt->return->productList)) {
                        foreach ($webservbt->return->productList as $product) {
                            $this->methodsAllowed[] = $product->productCode;
                        }
                    } else { /* cas ou il y a un seul résultat */
                        $product = $webservbt->return->productList;
                        $this->methodsAllowed[] = $product->productCode;
                    }
                }
            }
            return $this->methodsAllowed;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $value
     * @return string
     */
    protected function getFilledValue($value)
    {
        if ($value) {
            return $this->removeaccents(trim($value));
        } else {
            return '';
        }
    }

    /*****************************************************************************************************************
     ************************************ ETIQUETTES ******************************************************************
     **************************************************************************************************************/

    /**
     * @param $string
     * @return mixed
     */
    public function removeaccents($string)
    {
        $stringToReturn = str_replace(
            array('à', 'á', 'â', 'ã', 'ä', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', '/', '\xa8'), array('a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'A', 'A', 'A', 'A', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'N', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', ' ', 'e'), $string);
        // Remove all remaining other unknown characters
        $stringToReturn = preg_replace('/[^a-zA-Z0-9\-]/', ' ', $stringToReturn);
        $stringToReturn = preg_replace('/^[\-]+/', '', $stringToReturn);
        $stringToReturn = preg_replace('/[\-]+$/', '', $stringToReturn);
        $stringToReturn = preg_replace('/[\-]{2,}/', ' ', $stringToReturn);
        return $stringToReturn;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param string $mode
     * @param string $recipient_address_type
     * @return bool
     * @throws \Exception
     */
    public function createEtiquette($shipment, $mode = 'expedition', $recipient_address_type = 'returninformation', $dimensions = null, $nb_colis = 1, $contractId = null)
    {

        if ($mode == 'expedition') {
            $expeditionArray = $this->getExpeditionParams($shipment, $dimensions, $nb_colis, $contractId);
        } else {
            $expeditionArray = $this->getRetourParams($shipment, $recipient_address_type);
        }

        if ($expeditionArray) {
            $client = new \SoapClient(self::WS_SHIPPING_SERVICE, array('trace' => true));
            if($mode == 'expedition') {
                $expedition = $client->shippingMultiParcelWithReservationV3($expeditionArray);
            } else {
                $expedition = $client->shippingV7($expeditionArray);
            }

            if (!$expedition->return->errorCode) {
                return $expedition;
            } else {
                switch ($expedition->return->errorCode) {
                    case 33:
                        $message = __('An error occured during the label creation. Please check if this contract can edit labels for this carrier.');
                        break;
                    default:
                        $message = __($expedition->return->errorMessage);
                        break;
                }
                Throw new \Exception ($message);
            }
        }

        return false;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return array
     */
    protected function getExpeditionParams(\Magento\Sales\Model\Order\Shipment $shipment, $dimensions = null, $nb_colis, $contractId = null)
    {
        $_order = $shipment->getOrder();
        $_shippingAddress = $shipment->getShippingAddress();
        $_billingAddress = $shipment->getBillingAddress();
        if ($dimensions == null) {
            $dimensions = array();
        }


        $shippingMethod = $_order->getData('shipping_method');
        $shippingMethod = explode("_", $shippingMethod);
        $shippingMethod = $shippingMethod[1];

        $carrier = $this->_carrierFactory->get($shippingMethod);
        if (!$carrier || !$carrier->getIsChronoMethod()) { /* methode NON chronopost */
            return false;
        }


        $esdParams = $header = $shipper = $customer = $recipient = $ref = $skybill = $skybillParams = $password = array();

        //header parameters
        $header = $this->getParamsHeader($_order, $contractId);
        $password = $this->getPasswordContract($_order, $contractId);

        //shipper parameters
        $shipperMobilePhone = $this->checkMobileNumber($this->scopeConfig->getValue("chronorelais/shipperinformation/mobilephone"));
        $shipper = array(
            'shipperAdress1' => $this->scopeConfig->getValue("chronorelais/shipperinformation/address1"),
            'shipperAdress2' => $this->scopeConfig->getValue("chronorelais/shipperinformation/address2"),
            'shipperCity' => $this->scopeConfig->getValue("chronorelais/shipperinformation/city"),
            'shipperCivility' => $this->scopeConfig->getValue("chronorelais/shipperinformation/civility"),
            'shipperContactName' => $this->scopeConfig->getValue("chronorelais/shipperinformation/contactname"),
            'shipperCountry' => $this->scopeConfig->getValue("chronorelais/shipperinformation/country"),
            'shipperEmail' => $this->scopeConfig->getValue("chronorelais/shipperinformation/email"),
            'shipperMobilePhone' => $shipperMobilePhone,
            'shipperName' => $this->scopeConfig->getValue("chronorelais/shipperinformation/name"),
            'shipperName2' => $this->scopeConfig->getValue("chronorelais/shipperinformation/name2"),
            'shipperPhone' => $this->scopeConfig->getValue("chronorelais/shipperinformation/phone"),
            'shipperPreAlert' => '',
            'shipperZipCode' => $this->scopeConfig->getValue("chronorelais/shipperinformation/zipcode")
        );

        //customer parameters
        $customer = $this->getParamsCustomer();

        //recipient parameters
        $recipient_address = $_shippingAddress->getStreet();
        if (!isset($recipient_address[1])) {
            $recipient_address[1] = '';
        }
        $customer_email = ($_shippingAddress->getEmail()) ? $_shippingAddress->getEmail() : ($_billingAddress->getEmail() ? $_billingAddress->getEmail() : $_order->getCustomerEmail());
        $recipientMobilePhone = $this->checkMobileNumber($_shippingAddress->getTelephone());
        $recipientName = $this->getFilledValue($_shippingAddress->getCompany()); //RelayPoint Name if chronorelais or Companyname if chronopost and
        $recipientName2 = $this->getFilledValue($_shippingAddress->getFirstname() . ' ' . $_shippingAddress->getLastname());
        //remove any alphabets in phone number

        $recipientPhone = trim(preg_replace("/[^0-9\.\-]/", " ", $_shippingAddress->getTelephone()));

        $recipient = array(
            'recipientAdress1' => substr($this->getFilledValue($recipient_address[0]), 0, 38),
            'recipientAdress2' => substr($this->getFilledValue($recipient_address[1]), 0, 38),
            'recipientCity' => $this->getFilledValue($_shippingAddress->getCity()),
            'recipientContactName' => $recipientName2,
            'recipientCountry' => $this->getFilledValue($_shippingAddress->getCountryId()),
            'recipientEmail' => $customer_email,
            'recipientMobilePhone' => $recipientMobilePhone,
            'recipientName' => $recipientName,
            'recipientName2' => $recipientName2,
            'recipientPhone' => $recipientPhone,
            'recipientPreAlert' => '',
            'recipientZipCode' => $this->getFilledValue($_shippingAddress->getPostcode()),
        );

        //ref parameters
        $recipientRef = $this->getFilledValue($_order->getRelaisId());
        if (!$recipientRef) {
            $recipientRef = $_order->getCustomerId();
        }
        $shipperRef = $_order->getRealOrderId();

        $ref = array(
            'recipientRef' => $recipientRef,
            'shipperRef' => $shipperRef
        );

        //skybill parameters
        /* Livraison Samedi (Delivery Saturday) field */
        $SaturdayShipping = $this->getParamSaturdayShipping($_order, $carrier, $shippingMethod);

        $weight = $this->getParamWeight($shipment);

        /* si chronorelaiseurope : service : 337 si poids < 3kg ou 338 si > 3kg */
        if (preg_match('/chronorelaiseur/', $shippingMethod, $matches, PREG_OFFSET_CAPTURE)) {
            $weight <= 3 ? $SaturdayShipping = '337' : $SaturdayShipping = '338';
        }

        $skybills = array();

        foreach (range(1 , $nb_colis) as $value) {
            $skybill = array(
                'codCurrency' => 'EUR',
                'codValue' => '',
                'content1' => '',
                'content2' => '',
                'content3' => '',
                'content4' => '',
                'content5' => '',
                'customsCurrency' => 'EUR',
                'customsValue' => '',
                'evtCode' => 'DC',
                'insuredCurrency' => 'EUR',
                //on multiplie par 100 la valeur de l'assurance car exprimée en centime
                'insuredValue' => $nb_colis == 1 ? $this->_helperData->getOrderAdValorem($_order) * 100 : 0,
                'objectType' => 'MAR',
                'productCode' => $carrier->getChronoProductCodeToShipment(),
                'service' => $SaturdayShipping,
                'shipDate' => date('c'),
                'shipHour' => date('H'),
                'weightUnit' => 'KGM',
                'skybillRank' => $value
            );

            if($nb_colis > 1){
                $skybill['bulkNumber'] = $nb_colis;
            }

            $skybill['weight'] = isset($dimensions[$value-1]['weight']) ? $dimensions[$value-1]['weight'] : 2;
            $skybill['height'] = isset($dimensions[$value-1]['height']) ?  $dimensions[$value-1]['height'] : 1;
            $skybill['length'] = isset($dimensions[$value-1]['length']) ?  $dimensions[$value-1]['length'] : 1;
            $skybill['width'] = isset($dimensions[$value-1]['width']) ?  $dimensions[$value-1]['width'] : 1;

            if (preg_match('/chronopostsrdv/', $shippingMethod, $matches, PREG_OFFSET_CAPTURE)) {
                $chronopostsrdv_creneaux_info = $_order->getData('chronopostsrdv_creneaux_info');
                $chronopostsrdv_creneaux_info = json_decode($chronopostsrdv_creneaux_info, true);
                $skybill['productCode'] = $chronopostsrdv_creneaux_info['productCode'];
                $skybill['service'] = $chronopostsrdv_creneaux_info['serviceCode'];
                if ($chronopostsrdv_creneaux_info['dayOfWeek'] == 7 && isset($chronopostsrdv_creneaux_info['asCode'])) {
                    $skybill['as'] = $chronopostsrdv_creneaux_info['asCode'];
                }
            }

            $skybills[] = $skybill;

        }
        $skybillParams = array(
            'mode' => $this->scopeConfig->getValue("chronorelais/skybillparam/mode")
        );

        $expeditionArray = array(
            'headerValue' => $header,
            'shipperValue' => $shipper,
            'customerValue' => $customer,
            'recipientValue' => $recipient,
            'refValue' => $ref,
            'skybillValue' => $skybills,
            'skybillParamsValue' => $skybillParams,
            'password' => $password,
            'numberOfParcel' => $nb_colis
        );

        /* si chronopostsrdv : ajout parametres supplementaires */
        if (preg_match('/chronopostsrdv/', $shippingMethod, $matches, PREG_OFFSET_CAPTURE)) {

            $chronopostsrdv_creneaux_info = $_order->getData('chronopostsrdv_creneaux_info');
            $chronopostsrdv_creneaux_info = json_decode($chronopostsrdv_creneaux_info, true);

            $_dateRdvStart = new \DateTime($chronopostsrdv_creneaux_info['deliveryDate']);
            $_dateRdvStart->setTime($chronopostsrdv_creneaux_info['startHour'], $chronopostsrdv_creneaux_info['startMinutes']);

            $_dateRdvEnd = new \DateTime($chronopostsrdv_creneaux_info['deliveryDate']);
            $_dateRdvEnd->setTime($chronopostsrdv_creneaux_info['endHour'], $chronopostsrdv_creneaux_info['endMinutes']);


            $scheduledValue = array(
                'appointmentValue' => array(
                    'timeSlotStartDate' => $_dateRdvStart->format("Y-m-d") . "T" . $_dateRdvStart->format("H:i:s"),
                    'timeSlotEndDate' => $_dateRdvEnd->format("Y-m-d") . "T" . $_dateRdvEnd->format("H:i:s"),
                    'timeSlotTariffLevel' => $chronopostsrdv_creneaux_info['tariffLevel']
                )
            );
            $expeditionArray['scheduledValue'] = $scheduledValue;
        }
        return $expeditionArray;
    }

    /**
     * @return array
     */
    protected function getParamsHeader($order, $contractId = null)
    {

        $contract = $this->getContractData($order);

        if($contractId != null && (int)$contractId >= 0) {
            $contract = $this->_helperData->getSpecificContract($contractId);
        }

        $params['idEmit'] = 'MAG';
        $params['accountNumber'] = $contract['number'];
        $params['subAccount'] = $contract['subAccount'];

        return $params;

    }

    public function getContractData($order)
    {

        $contract = $this->_helperData->getContractByOrderId($order->getId());

        if (!$contract) {

            if (null !== $this->_request->getParam('contract')) {

                $contract = $this->_helperData->getSpecificContract($this->_request->getParam('contract'));

            } else {

                $shippingMethod = $order->getData('shipping_method');
                $shippingMethodCode = explode("_", $shippingMethod);
                $shippingMethodCode = isset($shippingMethodCode[1]) ? $shippingMethodCode[1] : $shippingMethodCode[0];
                $contract = $this->_helperData->getCarrierContract($shippingMethodCode);

            }

        } else {

            $contractTemp = $contract->getData();
            $contract = array();
            $contract['name'] = $contractTemp['contract_name'];
            $contract['number'] = $contractTemp['contract_account_number'];
            $contract['subAccount'] = $contractTemp['contract_sub_account_number'];
            $contract['pass'] = $contractTemp['contract_account_password'];

        }

        return $contract;


    }

    protected function getPasswordContract($order, $contractId = null)
    {

        $contract = $this->getContractData($order);

        if($contractId != null && (int)$contractId >= 0) {
            $contract = $this->_helperData->getSpecificContract($contractId);
        }

        return $contract['pass'];

    }

    /**
     * @param $value
     * @return string
     */
    protected function checkMobileNumber($value)
    {
        if ($reqvalue = trim($value)) {
            $_number = substr($reqvalue, 0, 2);
            $fixed_array = array('01', '02', '03', '04', '05', '06', '07');
            if (in_array($_number, $fixed_array)) {
                return $reqvalue;
            } else {
                return '';
            }
        }
    }

    protected function getParamsCustomer()
    {
        $customerMobilePhone = $this->checkMobileNumber($this->scopeConfig->getValue("chronorelais/customerinformation/mobilephone"));
        $customer = array(
            'customerAdress1' => $this->scopeConfig->getValue("chronorelais/customerinformation/address1"),
            'customerAdress2' => $this->scopeConfig->getValue("chronorelais/customerinformation/address2"),
            'customerCity' => $this->scopeConfig->getValue("chronorelais/customerinformation/city"),
            'customerCivility' => $this->scopeConfig->getValue("chronorelais/customerinformation/civility"),
            'customerContactName' => $this->scopeConfig->getValue("chronorelais/customerinformation/contactname"),
            'customerCountry' => $this->scopeConfig->getValue("chronorelais/customerinformation/country"),
            'customerEmail' => $this->scopeConfig->getValue("chronorelais/customerinformation/email"),
            'customerMobilePhone' => $customerMobilePhone,
            'customerName' => $this->scopeConfig->getValue("chronorelais/customerinformation/name"),
            'customerName2' => $this->scopeConfig->getValue("chronorelais/customerinformation/name2"),
            'customerPhone' => $this->scopeConfig->getValue("chronorelais/customerinformation/phone"),
            'customerPreAlert' => '',
            'customerZipCode' => $this->scopeConfig->getValue("chronorelais/customerinformation/zipcode")
        );
        return $customer;
    }

    /**
     * @param $_order
     * @param $carrier
     * @param $shippingMethod
     * @return int
     */
    protected function getParamSaturdayShipping($_order, $carrier, $shippingMethod)
    {
        $SaturdayShipping = 0; //default value for the saturday shipping

        /* gestion livraison samedi */
        if ($carrier->canDeliverOnSaturday()) {
            if (!$_deliver_on_saturday = $this->_helperData->getLivraisonSamediStatus($_order->getId())) {
                $_deliver_on_saturday = $this->scopeConfig->getValue('carriers/' . $carrier->getCarrierCode() . '/deliver_on_saturday');
            } else {
                if ($_deliver_on_saturday == 'Yes') {
                    $_deliver_on_saturday = 1;
                } else {
                    $_deliver_on_saturday = 0;
                }
            }
            $is_sending_day = $this->_helperData->isSendingDay();

            if (preg_match('/chronorelaisdom/', $shippingMethod, $matches, PREG_OFFSET_CAPTURE)) {
                if ($_deliver_on_saturday && $is_sending_day) {
                    $SaturdayShipping = 369;
                } else {
                    $SaturdayShipping = 368;
                }
            } else {
                if ($_deliver_on_saturday && $is_sending_day) {
                    $SaturdayShipping = 6;
                }
                elseif(preg_match('/chronosameday/', $shippingMethod, $matches, PREG_OFFSET_CAPTURE)){
                    $SaturdayShipping = 0;
                }
                elseif (!$_deliver_on_saturday && $is_sending_day) {
                    $SaturdayShipping = 1;
                }
            }
        }
        return $SaturdayShipping;
    }

    /**
     * @param $shipment
     * @return float|int
     */
    protected function getParamWeight($shipment)
    {
        $weight = 0;
        foreach ($shipment->getItemsCollection() as $item) {
            $weight += $item->getWeight() * $item->getQty();
        }
        if ($this->scopeConfig->getValue("chronorelais/weighunit/unit") == 'g') {
            $weight = $weight / 1000; /* conversion g => kg */
        }
        return $weight;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param $recipient_address_type
     * @return array
     * @throws \Exception
     */
    protected function getRetourParams(\Magento\Sales\Model\Order\Shipment $shipment, $recipient_address_type)
    {
        $_order = $shipment->getOrder();
        $_shippingAddress = $shipment->getShippingAddress();
        $_billingAddress = $shipment->getBillingAddress();
        $shippingMethod = explode("_", $_order->getShippingMethod());
        $shippingMethod = $shippingMethod[1];
        $carrier = $this->_carrierFactory->get($shippingMethod);

        if ($_shippingAddress->getCountryId() != 'FR'
            && strpos($shippingMethod, 'chronoexpress') === false
            && strpos($shippingMethod, 'chronorelaiseur') === false
            && strpos($shippingMethod, 'chronorelaisdom') === false
            && strpos($shippingMethod, 'chronocclassic') === false
            && strpos($shippingMethod, 'chronopostsrdv') === false
        ) {
            Throw new \Exception(__('Returns are only available for France'));
        }
        $shippingMethodAllow = array(
            'chronorelaiseur',
            'chronorelais',
            'chronopost',
            'chronopostc10',
            'chronopostc18',
            'chronopostsrdv',
            'chronocclassic',
            'chronoexpress'
        );

        if (!in_array($shippingMethod, $shippingMethodAllow)) {
            Throw new \Exception('Returns are not available for this delivery option ' . $shippingMethod);
        }

        if (in_array($shippingMethod, $shippingMethodAllow)) {
            $esdParams = $header = $shipper = $customer = $recipient = $ref = $skybill = $skybillParams = $password = array();

            //header parameters
            $header = $this->getParamsHeader($_order);
            $password = $this->getPasswordContract($_order);

            $recipient = $this->getRecipientReturnAdress($recipient_address_type);

            //customer parameters
            $customer = $this->getParamsCustomer();

            //recipient parameters
            $_recipientAddress = $_shippingAddress;
            if (strpos($shippingMethod, 'chronorelais') !== false) {
                // Nicolas, le 27/11/2014 : si Chronorelais, on doit utiliser l'adresse de facturation, non de livraison (qui est celle du relais)
                $_recipientAddress = $_billingAddress;
            }
            $recipient_address = $_recipientAddress->getStreet();

            // Champs forcément basés sur l'adresse de livraison
            $customer_email = ($_shippingAddress->getEmail()) ? $_shippingAddress->getEmail() : ($_billingAddress->getEmail() ? $_billingAddress->getEmail() : $_order->getCustomerEmail());
            $recipientMobilePhone = $this->checkMobileNumber($_shippingAddress->getTelephone());
            $recipientName = $this->getFilledValue($_recipientAddress->getCompany()); //RelayPoint Name if chronorelais or Companyname if chronopost and
            $recipientName2 = $this->getFilledValue($_shippingAddress->getFirstname() . ' ' . $_shippingAddress->getLastname());
            //remove any alphabets in phone number

            $recipientPhone = trim(preg_replace("/[^0-9\.\-]/", " ", $_shippingAddress->getTelephone()));
            if (!isset($recipient_address[1])) {
                $recipient_address[1] = '';
            }

            $shipper = array(
                'shipperAdress1' => substr($this->getFilledValue($recipient_address[0]), 0, 38),
                'shipperAdress2' => $recipient_address[1] ? substr($this->getFilledValue($recipient_address[1]), 0,
                    38) : '',
                'shipperCity' => $this->getFilledValue($_recipientAddress->getCity()),
                'shipperCivility' => 'M',
                'shipperContactName' => $recipientName2,
                'shipperCountry' => $this->getFilledValue($_recipientAddress->getCountryId()),
                'shipperEmail' => $customer_email,
                'shipperMobilePhone' => $recipientMobilePhone,
                'shipperName' => $recipientName,
                'shipperName2' => $recipientName2,
                'shipperPhone' => $recipientPhone,
                'shipperPreAlert' => '',
                'shipperZipCode' => $this->getFilledValue($_recipientAddress->getPostcode()),
            );

            //ref parameters
            $recipientRef = $this->getFilledValue($_order->getRelaisId());
            if (!$recipientRef) {
                $recipientRef = $_order->getCustomerId();
            }
            $shipperRef = $_order->getRealOrderId();

            $ref = array(
                'recipientRef' => $recipientRef,
                'shipperRef' => $shipperRef
            );

            //skybill parameters
            /* Livraison Samedi (Delivery Saturday) field */
            $SaturdayShipping = $this->getParamSaturdayShipping($_order, $carrier, $shippingMethod);

            $weight = $this->getParamWeight($shipment);

            // return product code
            $productCode = $this->getReturnProductCode($_shippingAddress, $shippingMethod);

            // @todo réactiver ce code quand les WS auront été mis à jour
            //$codeService = $_helper->getReturnServiceCode($productCode);
            if ($SaturdayShipping == 6) {
                $codeService = 1;
            } else {
                $codeService = 0;
            }

            $weight = 0; /* On met le poids à 0 car les colis sont pesé sur place */

            $skybill = array(
                'codCurrency' => 'EUR',
                'codValue' => '',
                'content1' => '',
                'content2' => '',
                'content3' => '',
                'content4' => '',
                'content5' => '',
                'customsCurrency' => 'EUR',
                'customsValue' => '',
                'evtCode' => 'DC',
                'insuredCurrency' => 'EUR',
                'insuredValue' => $this->_helperData->getOrderAdValorem($_order),
                'objectType' => 'MAR',
                'productCode' => $productCode,
                'service' => $codeService,
                'shipDate' => date('c'),
                'shipHour' => date('H'),
                'weight' => $weight,
                'weightUnit' => 'KGM',
                'height'          => 1,
                'length'          => 1,
                'width'           => 1
            );

            $mode = $this->scopeConfig->getValue("chronorelais/skybillparam/mode");
            if ($shippingMethod == 'chronorelaiseur') {
                $mode = 'PPR';
            }
            $skybillParams = array(
                'mode' => $mode,
                'withReservation' => 0
            );

            $expeditionArray = array(
                'headerValue' => $header,
                'shipperValue' => $shipper,
                'customerValue' => $customer,
                'recipientValue' => $recipient,
                'refValue' => $ref,
                'skybillValue' => $skybill,
                'skybillParamsValue' => $skybillParams,
                'password' => $password
            );

            return $expeditionArray;
        }
    }

    protected function getRecipientReturnAdress($recipient_address_type)
    {

        $MobilePhone = $this->checkMobileNumber($this->scopeConfig->getValue("chronorelais/" . $recipient_address_type . "/mobilephone"));
        $recipient = array(
            'recipientAdress1' => $this->scopeConfig->getValue("chronorelais/" . $recipient_address_type . "/address1"),
            'recipientAdress2' => $this->scopeConfig->getValue("chronorelais/" . $recipient_address_type . "/address2"),
            'recipientCity' => $this->scopeConfig->getValue("chronorelais/" . $recipient_address_type . "/city"),
            'recipientCivility' => $this->scopeConfig->getValue("chronorelais/" . $recipient_address_type . "/civility"),
            'recipientContactName' => $this->scopeConfig->getValue("chronorelais/" . $recipient_address_type . "/contactname"),
            'recipientCountry' => $this->scopeConfig->getValue("chronorelais/" . $recipient_address_type . "/country"),
            'recipientEmail' => $this->scopeConfig->getValue("chronorelais/" . $recipient_address_type . "/email"),
            'recipientMobilePhone' => $MobilePhone,
            'recipientName' => $this->scopeConfig->getValue("chronorelais/" . $recipient_address_type . "/name"),
            'recipientName2' => $this->scopeConfig->getValue("chronorelais/" . $recipient_address_type . "/name2"),
            'recipientPhone' => $this->scopeConfig->getValue("chronorelais/" . $recipient_address_type . "/phone"),
            'recipientPreAlert' => '',
            'recipientZipCode' => $this->scopeConfig->getValue("chronorelais/" . $recipient_address_type . "/zipcode")
        );
        return $recipient;
    }

    /**
     * Calcul codeProduct and codeService for Reverse (return)
     * @param \Magento\Sales\Model\Order\Address $address
     * @return int|string
     */
    public function getReturnProductCode(\Magento\Sales\Model\Order\Address $address, $carrierCode = null)
    {
        $productCodes = $this->getMethods($address, $carrierCode);
        $productReturnCodes = $this->_helperData->getReturnProductCodesAllowed($productCodes);
        sort($productReturnCodes, SORT_STRING);

        foreach ($this->_helperData->getMatriceReturnCode() as $code => $combinaisonCodes) {
            if (in_array($productReturnCodes, $combinaisonCodes)) {
                return $code;
            }
        }
        return HelperData::CHRONOPOST_REVERSE_DEFAULT;
    }


    /***************************************************************************************************************
     ************************************ RELAIS *******************************************************************
     **************************************************************************************************************/

    public function cancelEtiquette($number = '', $contract = null)
    {
        if ($number) {
            $accountNumber = '';
            $accountPassword = '';
            if ($contract != null) {
                $accountNumber = $contract['contract_account_number'];
                $accountPassword = $contract['contract_account_password'];
            }
            $client = new \SoapClient(self::WS_TRACKING_SERVICE, array('trace' => 0, 'connection_timeout' => 10));
            $params = array(
                'accountNumber' => $accountNumber,
                'password' => $accountPassword,
                'skybillNumber' => $number,
                'language' => $this->getLocale()
            );
            return $client->cancelSkybill($params);
        }
        return false;
    }

    protected function getLocale()
    {
        if ($this->_authSession->getUser() && $this->_authSession->getUser()->getId()) {
            return $this->_authSession->getUser()->getInterfaceLocale();
        }
        return $this->_resolver->getLocale();
    }

    /**
     * @param $cp
     * @return bool|mixed
     */
    public function getPointsRelaisByCp($cp)
    {
        try {
            $client = new \SoapClient(self::WS_RELAIS_SERVICE, array('trace' => 0, 'connection_timeout' => 10));
            return $client->__call("rechercheBtParCodeproduitEtCodepostalEtDate", array(0, $cp, 0));
        } catch (\Exception $e) {
            return $this->getPointsRelaisByPudo(false, $cp);
        }
    }

    /**
     * WS relais de secours
     * @param bool|\Magento\Quote\Model\Quote\Address $address
     * @param bool|string $cp
     * @return array|bool
     */
    public function getPointsRelaisByPudo($address = false, $cp = false)
    {
        $params = array(
            'carrier' => 'CHR',
            'key' => '75f6fe195dc88ceecbc0f8a2f70a8f3a',
            'address' => $address ? $this->getFilledValue($address->getStreetLine(1)) : '',
            'zipCode' => $address ? $this->getFilledValue($address->getPostcode()) : $cp,
            'city' => $address ? $this->getFilledValue($address->getCity()) : 'Lille',
            'countrycode' => $address ? $this->getFilledValue($address->getCountryId()) : '',
            'requestID' => '1',
            'date_from' => date('d/m/Y'),
            'max_pudo_number' => 5,
            'max_distance_search' => 10,
            'weight' => 1,
            'category' => '',
            'holiday_tolerant' => 1,
        );
        try {
            $client = new \SoapClient(self::WS_RELAI_SECOURS,
                array('trace' => 0, 'connection_timeout' => 10));
            $webservbt = $client->GetPudoList($params);
            $webservbt = json_decode(json_encode((object)simplexml_load_string($webservbt->GetPudoListResult->any)), 1);
            if (!isset($webservbt['ERROR'])) {
                $return = array();

                $listePr = $webservbt['PUDO_ITEMS']['PUDO_ITEM'];
                if ($listePr) {
                    foreach ($listePr as $pr) {
                        if ($pr['@attributes']['active'] == 'true') {
                            $newPr = (object)array();
                            $newPr->adresse1 = $pr['ADDRESS1'];
                            $newPr->adresse2 = is_array($pr['ADDRESS2']) ? implode(' ',
                                $pr['ADDRESS2']) : $pr['ADDRESS2'];
                            $newPr->adresse3 = is_array($pr['ADDRESS3']) ? implode(' ',
                                $pr['ADDRESS3']) : $pr['ADDRESS3'];
                            $newPr->codePostal = $pr['ZIPCODE'];
                            $newPr->identifiantChronopostPointA2PAS = $pr['PUDO_ID'];
                            $newPr->latitude = $pr['coordGeolocalisationLatitude'];
                            $newPr->longitude = $pr['coordGeolocalisationLongitude'];
                            $newPr->localite = $pr['CITY'];
                            $newPr->nomEnseigne = $pr['NAME'];
                            $time = new \DateTime;
                            $newPr->dateArriveColis = $time->format(\DateTime::ATOM);
                            $newPr->horairesOuvertureLundi = $newPr->horairesOuvertureMardi = $newPr->horairesOuvertureMercredi = $newPr->horairesOuvertureJeudi = $newPr->horairesOuvertureVendredi = $newPr->horairesOuvertureSamedi = $newPr->horairesOuvertureDimanche = '';

                            if (isset($pr['OPENING_HOURS_ITEMS']['OPENING_HOURS_ITEM'])) {
                                $listeHoraires = $pr['OPENING_HOURS_ITEMS']['OPENING_HOURS_ITEM'];
                                foreach ($listeHoraires as $horaire) {
                                    switch ($horaire['DAY_ID']) {
                                        case '1' :
                                            if (!empty($newPr->horairesOuvertureLundi)) {
                                                $newPr->horairesOuvertureLundi .= ' ';
                                            }
                                            $newPr->horairesOuvertureLundi .= $horaire['START_TM'] . '-' . $horaire['END_TM'];
                                            break;
                                        case '2' :
                                            if (!empty($newPr->horairesOuvertureMardi)) {
                                                $newPr->horairesOuvertureMardi .= ' ';
                                            }
                                            $newPr->horairesOuvertureMardi .= $horaire['START_TM'] . '-' . $horaire['END_TM'];
                                            break;
                                        case '3' :
                                            if (!empty($newPr->horairesOuvertureMercredi)) {
                                                $newPr->horairesOuvertureMercredi .= ' ';
                                            }
                                            $newPr->horairesOuvertureMercredi .= $horaire['START_TM'] . '-' . $horaire['END_TM'];
                                            break;
                                        case '4' :
                                            if (!empty($newPr->horairesOuvertureJeudi)) {
                                                $newPr->horairesOuvertureJeudi .= ' ';
                                            }
                                            $newPr->horairesOuvertureJeudi .= $horaire['START_TM'] . '-' . $horaire['END_TM'];
                                            break;
                                        case '5' :
                                            if (!empty($newPr->horairesOuvertureVendredi)) {
                                                $newPr->horairesOuvertureVendredi .= ' ';
                                            }
                                            $newPr->horairesOuvertureVendredi .= $horaire['START_TM'] . '-' . $horaire['END_TM'];
                                            break;
                                        case '6' :
                                            if (!empty($newPr->horairesOuvertureSamedi)) {
                                                $newPr->horairesOuvertureSamedi .= ' ';
                                            }
                                            $newPr->horairesOuvertureSamedi .= $horaire['START_TM'] . '-' . $horaire['END_TM'];
                                            break;
                                        case '7' :
                                            if (!empty($newPr->horairesOuvertureDimanche)) {
                                                $newPr->horairesOuvertureDimanche .= ' ';
                                            }
                                            $newPr->horairesOuvertureDimanche .= $horaire['START_TM'] . '-' . $horaire['END_TM'];
                                            break;
                                    }
                                }
                            }
                            if (empty($newPr->horairesOuvertureLundi)) {
                                $newPr->horairesOuvertureLundi = "00:00-00:00 00:00-00:00";
                            }
                            if (empty($newPr->horairesOuvertureMardi)) {
                                $newPr->horairesOuvertureMardi = "00:00-00:00 00:00-00:00";
                            }
                            if (empty($newPr->horairesOuvertureMercredi)) {
                                $newPr->horairesOuvertureMercredi = "00:00-00:00 00:00-00:00";
                            }
                            if (empty($newPr->horairesOuvertureJeudi)) {
                                $newPr->horairesOuvertureJeudi = "00:00-00:00 00:00-00:00";
                            }
                            if (empty($newPr->horairesOuvertureVendredi)) {
                                $newPr->horairesOuvertureVendredi = "00:00-00:00 00:00-00:00";
                            }
                            if (empty($newPr->horairesOuvertureSamedi)) {
                                $newPr->horairesOuvertureSamedi = "00:00-00:00 00:00-00:00";
                            }
                            if (empty($newPr->horairesOuvertureDimanche)) {
                                $newPr->horairesOuvertureDimanche = "00:00-00:00 00:00-00:00";
                            }
                            $return[] = $newPr;
                        }
                    }
                    return $return;
                }
            }
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }

    /**
     * @param string $shippingMethodCode
     * @param bool|\Magento\Quote\Model\Quote\Address $address
     * @return array|bool
     */
    public function getPointRelaisByAddress($shippingMethodCode = 'chronorelais', $address = false)
    {

        if (!$shippingMethodCode || !$address) {
            return false;
        }

        $accountNumber = '';
        $accountPassword = '';
        $contract = $this->_helperData->getCarrierContract($shippingMethodCode);
        if ($contract != null) {
            $accountNumber = $contract['number'];
            $accountPassword = $contract['pass'];
        }

        try {
            $carrier = $this->_carrierFactory->get($shippingMethodCode);


            $pointRelaisWsMethod = $carrier->getConfigData("point_relai_ws_method");
            $pointRelaisProductCode = $carrier->getChronoProductCode();
            $pointRelaisService = 'T';
            $addAddressToWs = $carrier->getConfigData("add_address_to_ws");
            $maxPointChronopost = $carrier->getConfigData("max_point_chronopost");
            $maxDistanceSearch = $carrier->getConfigData("max_distance_search");

            $client = new \SoapClient(self::WS_RELAIS_POINTRELAIS, array('trace' => 0, 'connection_timeout' => 10));

            /* si dom => on ne met pas le code ISO mais un code spécifique, sinon le relai dom ne fonctionne pas */
            $countryDomCode = $this->getCountryDomCode();
            $countryId = $address->getCountryId();

            if (isset($countryDomCode[$countryId])) {
                $countryId = $countryDomCode[$countryId];
            }

            $params = array(
                'accountNumber' => $accountNumber,
                'password' => $accountPassword,
                'zipCode' => $this->getFilledValue($address->getPostcode()),
                'city' => $address->getCity() ? $this->getFilledValue($address->getCity()) : '',
                'countryCode' => $this->getFilledValue($countryId),
                'type' => 'P',
                'productCode' => $pointRelaisProductCode,
                'service' => $pointRelaisService,
                'weight' => 2000,
                'shippingDate' => date('d/m/Y'),
                'maxPointChronopost' => $maxPointChronopost,
                'maxDistanceSearch' => $maxDistanceSearch,
                'holidayTolerant' => 1
            );
            if ($addAddressToWs) {
                $params['address'] = $address->getStreetLine(1) ? $this->getFilledValue($address->getStreetLine(1)) : '';
            }
            $webservbt = $client->$pointRelaisWsMethod($params);

            /* format $webservbt pour avoir le meme format que lors de l'appel du WS par code postal */
            if ($webservbt->return->errorCode == 0) {
                /*
                 * Format entrée
                 *
                 * accesPersonneMobiliteReduite
                    actif
                    adresse1
                    adresse2
                    adresse3
                    codePays
                    codePostal
                    coordGeolocalisationLatitude
                    coordGeolocalisationLongitude
                    distanceEnMetre
                    identifiant
                    indiceDeLocalisation
                    listeHoraireOuverture
                    localite
                    nom
                    poidsMaxi
                    typeDePoint
                    urlGoogleMaps
                 *
                 * Format sortie
                 * adresse1
                    adresse2
                    adresse3
                    codePostal
                    dateArriveColis
                    horairesOuvertureDimanche ("10:00-12:30 14:30-19:00")
                    horairesOuvertureJeudi
                    horairesOuvertureLundi
                    horairesOuvertureMardi
                    horairesOuvertureMercredi
                    horairesOuvertureSamedi
                    horairesOuvertureVendredi
                    identifiantChronopostPointA2PAS
                    localite
                    nomEnseigne
                 *
                 *
                 *
                 * 2013-02-19T10:42:40.196Z
                 *
                 */
                $listePr = $webservbt->return->listePointRelais;
                if (count($webservbt->return->listePointRelais) == 1) {
                    $listePr = array($listePr);
                }
                $return = array();
                foreach ($listePr as $pr) {
                    $newPr = (object)array();
                    $newPr->adresse1 = $pr->adresse1;
                    $newPr->adresse2 = $pr->adresse2;
                    $newPr->adresse3 = $pr->adresse3;
                    $newPr->latitude = $pr->coordGeolocalisationLatitude;
                    $newPr->longitude = $pr->coordGeolocalisationLongitude;
                    $newPr->codePostal = $pr->codePostal;
                    $newPr->identifiantChronopostPointA2PAS = $pr->identifiant;
                    $newPr->localite = $pr->localite;
                    $newPr->nomEnseigne = $pr->nom;
                    $time = new \DateTime;
                    $newPr->dateArriveColis = $time->format(\DateTime::ATOM);
                    $newPr->horairesOuvertureLundi = $newPr->horairesOuvertureMardi = $newPr->horairesOuvertureMercredi = $newPr->horairesOuvertureJeudi = $newPr->horairesOuvertureVendredi = $newPr->horairesOuvertureSamedi = $newPr->horairesOuvertureDimanche = '';
                    foreach ($pr->listeHoraireOuverture as $horaire) {
                        switch ($horaire->jour) {
                            case '1' :
                                $newPr->horairesOuvertureLundi = $horaire->horairesAsString;
                                break;
                            case '2' :
                                $newPr->horairesOuvertureMardi = $horaire->horairesAsString;
                                break;
                            case '3' :
                                $newPr->horairesOuvertureMercredi = $horaire->horairesAsString;
                                break;
                            case '4' :
                                $newPr->horairesOuvertureJeudi = $horaire->horairesAsString;
                                break;
                            case '5' :
                                $newPr->horairesOuvertureVendredi = $horaire->horairesAsString;
                                break;
                            case '6' :
                                $newPr->horairesOuvertureSamedi = $horaire->horairesAsString;
                                break;
                            case '7' :
                                $newPr->horairesOuvertureDimanche = $horaire->horairesAsString;
                                break;
                            default :
                                break;
                        }
                    }
                    if (empty($newPr->horairesOuvertureLundi)) {
                        $newPr->horairesOuvertureLundi = "00:00-00:00 00:00-00:00";
                    }
                    if (empty($newPr->horairesOuvertureMardi)) {
                        $newPr->horairesOuvertureMardi = "00:00-00:00 00:00-00:00";
                    }
                    if (empty($newPr->horairesOuvertureMercredi)) {
                        $newPr->horairesOuvertureMercredi = "00:00-00:00 00:00-00:00";
                    }
                    if (empty($newPr->horairesOuvertureJeudi)) {
                        $newPr->horairesOuvertureJeudi = "00:00-00:00 00:00-00:00";
                    }
                    if (empty($newPr->horairesOuvertureVendredi)) {
                        $newPr->horairesOuvertureVendredi = "00:00-00:00 00:00-00:00";
                    }
                    if (empty($newPr->horairesOuvertureSamedi)) {
                        $newPr->horairesOuvertureSamedi = "00:00-00:00 00:00-00:00";
                    }
                    if (empty($newPr->horairesOuvertureDimanche)) {
                        $newPr->horairesOuvertureDimanche = "00:00-00:00 00:00-00:00";
                    }

                    $return[] = $newPr;
                }

                return $return;
            }
        } catch (\Exception $e) {
            return $this->getPointsRelaisByPudo($address);
        }
    }

    /**
     * @return array
     */
    protected function getCountryDomCode()
    {
        return array(
            'RE' => 'REU',
            'MQ' => 'MTQ',
            'GP' => 'GLP',
            'MX' => 'MYT',
            'GF' => 'GUF'
        );
    }

    /**
     * Get info relais
     * @param $relaisId
     * @return mixed
     */
    public function getDetailRelaisPoint($relaisId)
    {
        $accountNumber = '';
        $accountPassword = '';
        $contract = $this->_helperData->getCarrierContract(\Chronopost\Chronorelais\Model\Carrier\Chronorelais::CARRIER_CODE);
        if ($contract != null) {
            $accountNumber = $contract['number'];
            $accountPassword = $contract['pass'];
        }

        try {
            $params = array(
                'accountNumber' => $accountNumber,
                'password' => $accountPassword,
                'identifiant' => $relaisId
            );

            $client = new \SoapClient(self::WS_RELAIS_POINTRELAIS);
            $webservbt = $client->rechercheDetailPointChronopost($params);

            if ($webservbt->return->errorCode == 0) {
                return $webservbt->return->listePointRelais;
            } else {
                return $this->getDetailRelaisPointByPudo($relaisId);
            }
        } catch (\Exception $e) {
            return $this->getDetailRelaisPointByPudo($relaisId);
        }
    }

    /**
     * get info relai : WS de secours
     * @param $relaisId
     * @return bool|object
     */
    public function getDetailRelaisPointByPudo($relaisId)
    {
        $params = array(
            'carrier' => 'CHR',
            'key' => '75f6fe195dc88ceecbc0f8a2f70a8f3a',
            'pudo_id' => $relaisId,
        );

        try {
            $client = new \SoapClient(self::WS_RELAI_SECOURS, array('trace' => 0, 'connection_timeout' => 10));
            $webservbt = $client->GetPudoDetails($params);
            $webservbt = json_decode(json_encode((object)simplexml_load_string($webservbt->GetPudoDetailsResult->any)),
                1);
            if (!isset($webservbt['ERROR'])) {
                $pr = $webservbt['PUDO_ITEMS']['PUDO_ITEM'];
                if ($pr && $pr['@attributes']['active'] == 'true') {
                    $newPr = (object)array();
                    $newPr->adresse1 = $pr['ADDRESS1'];
                    $newPr->adresse2 = is_array($pr['ADDRESS2']) ? implode(' ', $pr['ADDRESS2']) : $pr['ADDRESS2'];
                    $newPr->adresse3 = is_array($pr['ADDRESS3']) ? implode(' ', $pr['ADDRESS3']) : $pr['ADDRESS3'];
                    $newPr->latitude = $pr['coordGeolocalisationLatitude'];
                    $newPr->longitude = $pr['coordGeolocalisationLongitude'];
                    $newPr->codePostal = $pr['ZIPCODE'];
                    $newPr->identifiantChronopostPointA2PAS = $pr['PUDO_ID'];
                    $newPr->localite = $pr['CITY'];
                    $newPr->nomEnseigne = $pr['NAME'];
                    $time = new \DateTime;
                    $newPr->dateArriveColis = $time->format(\DateTime::ATOM);
                    $newPr->horairesOuvertureLundi = $newPr->horairesOuvertureMardi = $newPr->horairesOuvertureMercredi = $newPr->horairesOuvertureJeudi = $newPr->horairesOuvertureVendredi = $newPr->horairesOuvertureSamedi = $newPr->horairesOuvertureDimanche = '';

                    if (isset($pr['OPENING_HOURS_ITEMS']['OPENING_HOURS_ITEM'])) {
                        $listeHoraires = $pr['OPENING_HOURS_ITEMS']['OPENING_HOURS_ITEM'];
                        foreach ($listeHoraires as $horaire) {
                            switch ($horaire['DAY_ID']) {
                                case '1' :
                                    if (!empty($newPr->horairesOuvertureLundi)) {
                                        $newPr->horairesOuvertureLundi .= ' ';
                                    }
                                    $newPr->horairesOuvertureLundi .= $horaire['START_TM'] . '-' . $horaire['END_TM'];
                                    break;
                                case '2' :
                                    if (!empty($newPr->horairesOuvertureMardi)) {
                                        $newPr->horairesOuvertureMardi .= ' ';
                                    }
                                    $newPr->horairesOuvertureMardi .= $horaire['START_TM'] . '-' . $horaire['END_TM'];
                                    break;
                                case '3' :
                                    if (!empty($newPr->horairesOuvertureMercredi)) {
                                        $newPr->horairesOuvertureMercredi .= ' ';
                                    }
                                    $newPr->horairesOuvertureMercredi .= $horaire['START_TM'] . '-' . $horaire['END_TM'];
                                    break;
                                case '4' :
                                    if (!empty($newPr->horairesOuvertureJeudi)) {
                                        $newPr->horairesOuvertureJeudi .= ' ';
                                    }
                                    $newPr->horairesOuvertureJeudi .= $horaire['START_TM'] . '-' . $horaire['END_TM'];
                                    break;
                                case '5' :
                                    if (!empty($newPr->horairesOuvertureVendredi)) {
                                        $newPr->horairesOuvertureVendredi .= ' ';
                                    }
                                    $newPr->horairesOuvertureVendredi .= $horaire['START_TM'] . '-' . $horaire['END_TM'];
                                    break;
                                case '6' :
                                    if (!empty($newPr->horairesOuvertureSamedi)) {
                                        $newPr->horairesOuvertureSamedi .= ' ';
                                    }
                                    $newPr->horairesOuvertureSamedi .= $horaire['START_TM'] . '-' . $horaire['END_TM'];
                                    break;
                                case '7' :
                                    if (!empty($newPr->horairesOuvertureDimanche)) {
                                        $newPr->horairesOuvertureDimanche .= ' ';
                                    }
                                    $newPr->horairesOuvertureDimanche .= $horaire['START_TM'] . '-' . $horaire['END_TM'];
                                    break;
                                default :
                                    break;
                            }
                        }
                    }
                    if (empty($newPr->horairesOuvertureLundi)) {
                        $newPr->horairesOuvertureLundi = "00:00-00:00 00:00-00:00";
                    }
                    if (empty($newPr->horairesOuvertureMardi)) {
                        $newPr->horairesOuvertureMardi = "00:00-00:00 00:00-00:00";
                    }
                    if (empty($newPr->horairesOuvertureMercredi)) {
                        $newPr->horairesOuvertureMercredi = "00:00-00:00 00:00-00:00";
                    }
                    if (empty($newPr->horairesOuvertureJeudi)) {
                        $newPr->horairesOuvertureJeudi = "00:00-00:00 00:00-00:00";
                    }
                    if (empty($newPr->horairesOuvertureVendredi)) {
                        $newPr->horairesOuvertureVendredi = "00:00-00:00 00:00-00:00";
                    }
                    if (empty($newPr->horairesOuvertureSamedi)) {
                        $newPr->horairesOuvertureSamedi = "00:00-00:00 00:00-00:00";
                    }
                    if (empty($newPr->horairesOuvertureDimanche)) {
                        $newPr->horairesOuvertureDimanche = "00:00-00:00 00:00-00:00";
                    }

                    return $newPr;
                }
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    /*****************************************************************************************************************
     ************************************ RDV **********************************************************************
     **************************************************************************************************************/

    /**
     * @param string $_srdvConfig
     * @param bool|\Magento\Quote\Model\Quote\Address $_shippingAddress
     * @return bool
     */
    public function getPlanning($_shippingAddress)
    {
        $recipient_address = $_shippingAddress->getStreet();
        if (!isset($recipient_address[1])) {
            $recipient_address[1] = '';
        }

        try {
            $accountNumber = '';
            $accountPassword = '';
            $contract = $this->_helperData->getCarrierContract(\Chronopost\Chronorelais\Model\Carrier\ChronopostSrdv::CARRIER_CODE);
            if ($contract != null) {
                $accountNumber = $contract['number'];
                $accountPassword = $contract['pass'];
            }

            $soapHeaders = array();
            $namespace = 'http://cxf.soap.ws.creneau.chronopost.fr/';
            $soapHeaders[] = new \SoapHeader($namespace, 'password', $accountPassword);
            $soapHeaders[] = new \SoapHeader($namespace, 'accountNumber', $accountNumber);

            $client = new \SoapClient(self::WS_RDV_CRENEAUX,
                array('trace' => 1, 'connection_timeout' => 10));
            $client->__setSoapHeaders($soapHeaders);

            $_srdvConfig = json_decode($this->scopeConfig->getValue("carriers/chronopostsrdv/rdv_config"), true);

            /* definition date de debut */
            $dateBegin = date('Y-m-d H:i:s');
            if (isset($_srdvConfig['dateRemiseColis_nbJour']) && $_srdvConfig['dateRemiseColis_nbJour'] > 0) {
                $dateBegin = date('Y-m-d', strtotime('+' . (int)$_srdvConfig['dateRemiseColis_nbJour'] . ' day'));
            } elseif (isset($_srdvConfig['dateRemiseColis_jour']) && isset($_srdvConfig['dateRemiseColis_heures'])) {
                $jour_text = date('l', strtotime("Sunday +" . $_srdvConfig['dateRemiseColis_jour'] . " days"));
                $dateBegin = date('Y-m-d',
                        strtotime('next ' . $jour_text)) . ' ' . $_srdvConfig['dateRemiseColis_heures'] . ':' . $_srdvConfig['dateRemiseColis_minutes'] . ':00';
            }
            $dateBegin = date('Y-m-d', strtotime($dateBegin)) . 'T' . date('H:i:s', strtotime($dateBegin));

            $params = array(

                'callerTool' => 'RDVWS',
                'productType' => 'RDV',

                'shipperAdress1' => $this->scopeConfig->getValue("chronorelais/shipperinformation/address1"),
                'shipperAdress2' => $this->scopeConfig->getValue("chronorelais/shipperinformation/address2"),
                'shipperZipCode' => $this->scopeConfig->getValue("chronorelais/shipperinformation/zipcode"),
                'shipperCity' => $this->scopeConfig->getValue("chronorelais/shipperinformation/city"),
                'shipperCountry' => $this->scopeConfig->getValue("chronorelais/shipperinformation/country"),

                'recipientAdress1' => substr($this->getFilledValue($recipient_address[0]), 0, 38),
                'recipientAdress2' => substr($this->getFilledValue($recipient_address[1]), 0, 38),
                'recipientZipCode' => $this->getFilledValue($_shippingAddress->getPostcode()),
                'recipientCity' => $this->getFilledValue($_shippingAddress->getCity()),
                'recipientCountry' => $this->getFilledValue($_shippingAddress->getCountryId()),

                'weight' => 1,
                'dateBegin' => $dateBegin,
                'shipperDeliverySlotClosed' => '',
                'currency' => 'EUR',
                'isDeliveryDate' => 0,
                'slotType' => ''
            );


            for ($i = 1; $i <= 4; $i++) {

                /* tarif des niveaux tarifaires */
                if (isset($_srdvConfig['N' . $i . '_price'])) {
                    $params['rateN' . $i] = $_srdvConfig['N' . $i . '_price'];
                }

                /* niveaux tarifaires fermés  */
                if (isset($_srdvConfig['N' . $i . '_status']) && $_srdvConfig['N' . $i . '_status'] == 0) {
                    if (!isset($params['rateLevelsNotShow'])) {
                        $params['rateLevelsNotShow'] = array();
                    }
                    $params['rateLevelsNotShow'][] = 'N' . $i;
                }
            }

            /* creneaux à fermer */
            if (isset($_srdvConfig['creneaux'])) {
                foreach ($_srdvConfig['creneaux'] as $_creneau) {

                    $jour_debut_text = date('l',
                        $this->_datetime->timestamp(strtotime("Sunday +" . $_creneau['creneaux_debut_jour'] . " days")));
                    $jour_fin_text = date('l',
                        $this->_datetime->timestamp(strtotime("Sunday +" . $_creneau['creneaux_fin_jour'] . " days")));

                    $dateDebut = '';
                    $dateFin = '';

                    /* creation de creneaux aux bons formats, pour 6 semaines consécutives */
                    for ($indiceWeek = 0; $indiceWeek < 6; $indiceWeek++) {

                        if (empty($dateDebut)) {
                            $dateDebut = date('Y-m-d',
                                    $this->_datetime->timestamp(strtotime('next ' . $jour_debut_text))) . ' ' . (int)$_creneau['creneaux_debut_heures'] . ':' . (int)$_creneau['creneaux_debut_minutes'] . ':00';
                            $dateFin = date('Y-m-d',
                                    $this->_datetime->timestamp(strtotime('next ' . $jour_fin_text))) . ' ' . (int)$_creneau['creneaux_fin_heures'] . ':' . (int)$_creneau['creneaux_fin_minutes'] . ':00';
                            if (date('N') >= $_creneau['creneaux_debut_jour']) {
                                $dateDebut = date('Y-m-d',
                                        $this->_datetime->timestamp(strtotime(date('Y-m-d',
                                                strtotime($dateDebut)) . ' -7 day'))) . ' ' . (int)$_creneau['creneaux_debut_heures'] . ':' . (int)$_creneau['creneaux_debut_minutes'] . ':00';
                            }
                            if (date('N') >= $_creneau['creneaux_fin_jour']) {
                                $dateFin = date('Y-m-d', $this->_datetime->timestamp(strtotime(date('Y-m-d',
                                            strtotime($dateFin)) . ' -7 day'))) . ' ' . (int)$_creneau['creneaux_fin_heures'] . ':' . (int)$_creneau['creneaux_fin_minutes'] . ':00';
                            }

                        } else {
                            $dateDebut = date('Y-m-d',
                                    $this->_datetime->timestamp(strtotime($jour_debut_text . ' next week ' . date('Y-m-d',
                                            $this->_datetime->timestamp(strtotime($dateDebut)))))) . ' ' . (int)$_creneau['creneaux_debut_heures'] . ':' . (int)$_creneau['creneaux_debut_minutes'] . ':00';
                            $dateFin = date('Y-m-d',
                                    $this->_datetime->timestamp(strtotime($jour_fin_text . ' next week ' . date('Y-m-d',
                                            $this->_datetime->timestamp(strtotime($dateFin)))))) . ' ' . (int)$_creneau['creneaux_fin_heures'] . ':' . (int)$_creneau['creneaux_fin_minutes'] . ':00';
                        }

                        $dateDebutStr = date('Y-m-d',
                                $this->_datetime->timestamp(strtotime($dateDebut))) . 'T' . date('H:i:s',
                                $this->_datetime->timestamp(strtotime($dateDebut)));
                        $dateFinStr = date('Y-m-d',
                                $this->_datetime->timestamp(strtotime($dateFin))) . 'T' . date('H:i:s',
                                $this->_datetime->timestamp(strtotime($dateFin)));

                        if (!isset($params['shipperDeliverySlotClosed'])) {
                            $params['shipperDeliverySlotClosed'] = array();
                        }
                        $params['shipperDeliverySlotClosed'][] = $dateDebutStr . "/" . $dateFinStr;
                    }
                }
            }

            $webservbt = $client->searchDeliverySlot($params);
            if ($webservbt->return->code == 0) {
                return $webservbt;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param string $rdvInfo
     * @return bool
     */
    public function confirmDeliverySlot($rdvInfo = '')
    {
        try {

            $accountNumber = '';
            $accountPassword = '';
            $contract = $this->_helperData->getCarrierContract(\Chronopost\Chronorelais\Model\Carrier\ChronopostSrdv::CARRIER_CODE);
            if ($contract != null) {
                $accountNumber = $contract['number'];
                $accountPassword = $contract['pass'];
            }

            $soapHeaders = array();
            $namespace = 'http://cxf.soap.ws.creneau.chronopost.fr/';
            $soapHeaders[] = new \SoapHeader($namespace, 'password', $accountPassword);
            $soapHeaders[] = new \SoapHeader($namespace, 'accountNumber', $accountNumber);

            $client = new \SoapClient(self::WS_RDV_CRENEAUX,
                array('trace' => 1, 'connection_timeout' => 10));
            $client->__setSoapHeaders($soapHeaders);

            $params = array(
                'callerTool' => 'RDVWS',
                'productType' => 'RDV',

                'codeSlot' => $rdvInfo['deliverySlotCode'],
                'meshCode' => $rdvInfo['meshCode'],
                'transactionID' => $rdvInfo['transactionID'],
                'rank' => $rdvInfo['rank'],
                'position' => $rdvInfo['rank'],
                'dateSelected' => $rdvInfo['deliveryDate']
            );

            return $client->confirmDeliverySlotV2($params);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getEtiquetteByReservationNumber($number){

        $client = new \SoapClient(self::WS_SHIPPING_SERVICE, array('trace' => true));

        $expedition = $client->getReservedSkybill(array('reservationNumber'=> $number));


        if (!$expedition->return->errorCode) {
            return $expedition->return->skybill;
        } else {
            switch ($expedition->return->errorCode) {
                case 33:
                    $message = __('An error occured during the label creation. Please check if this contract can edit labels for this carrier.');
                    break;
                default:
                    $message = __($expedition->return->errorMessage);
                    break;
            }
            Throw new \Exception ($message);
        }

    }

    public function checkContract($contract)
    {
        $WSParams = array(
            'accountNumber' => $contract['number'],
            'password' => $contract['pass'],
            'depCountryCode' => $this->_helperData->getConfig("chronorelais/shipperinformation/country"),
            'depZipCode' => $this->_helperData->getConfig("chronorelais/shipperinformation/zipcode"),
            'arrCountryCode' => $this->_helperData->getConfig("chronorelais/shipperinformation/country"),
            'arrZipCode' => $this->_helperData->getConfig("chronorelais/shipperinformation/zipcode"),
            'arrCity' => $this->_helperData->getConfig("chronorelais/shipperinformation/city"),
            'type' => 'M',
            'weight' => 1
        );

        return $this->checkLogin($WSParams);
    }

    /**
     * @param      $shippingMethod
     * @param null $contractId
     * @param null $storeId
     * @param null $websiteId
     *
     * @return bool
     */
    public function shippingMethodEnabled($shippingMethod, $contractId = null, $storeId = null, $websiteId = null)
    {
        if($contractId != null) {
            $contract = $this->_helperData->getSpecificContract($contractId, $storeId, $websiteId);
        } else {
            $contract = $this->_helperData->getCarrierContract($shippingMethod);
        }

        if (!$contract) {
            return false;
        }

        $accountNumber = $contract["number"];
        $pass = $contract["pass"];

        $WSParams = array(
            'accountNumber' => $accountNumber,
            'password' => $pass,
            'depCountryCode' => $this->_helperData->getConfigurationShipperInfo('country'),
            'depZipCode' => $this->_helperData->getConfigurationShipperInfo('zipcode'),
            'arrCountryCode' => $this->_helperData->getConfigurationShipperInfo('country'),
            'arrZipCode' => $this->_helperData->getConfigurationShipperInfo('zipcode'),
            'arrCity' => $this->_helperData->getConfigurationShipperInfo('city'),
            'type' => 'M',
            'weight' => 1
        );

        $webservbt = $this->checkLogin($WSParams);

        if(!$webservbt->return->errorCode){

            // recupération des codes produits internationals
            $products = $webservbt->return->productList;
            $WSParams['arrCountryCode'] = 'ES';
            $WSParams['arrZipCode'] = '28013';
            $WSParams['arrCity'] = 'ES';

            $webservbt = $this->checkLogin($WSParams);

            if(!$webservbt->return->errorCode){
                if($productsInter = $webservbt->return->productList) {
                    if(is_array($products) && is_array($productsInter)) {
                        $products = array_merge($products, $productsInter);
                    } else {
                        array_push($products, $productsInter->productCode);
                    }
                }
            }

            if(is_array($products)) {
                foreach ($products as $product) {
                    if($this->_helperData->getChronoProductCode($shippingMethod) == $product->productCode){
                        return true;
                    }
                }
            } else {
                if($this->_helperData->getChronoProductCodeToShipment($shippingMethod) == $products->productCode){
                    return true;
                }
            }
        }
        return false;
    }

    public function getContractsHtml($orderId)
    {
        $order = $this->_orderRepository->get($orderId);

        if($contract = $this->_helperData->getContractByOrderId($orderId)) {
            $html  = '<select name="contract" style="display:none">';
            $html .= '<option value="-1" selected="selected">' . $contract["contract_name"] . '</option>';
            $html .= '</select>';
            $html .= '<span>' . $contract["contract_name"] . '</span>';
            return $html;
        } else {
            $html = '<select name="contract">';
            $chronoShippingMethod = strtolower(str_replace(' ', '', $order->getShippingMethod()));
            $chronoShippingMethod = preg_replace('/.*\_/', '', $chronoShippingMethod);
            $contractShippingMethod = $this->_helperData->getCarrierContract($chronoShippingMethod);
            $contracts = $this->_helperData->getConfigContracts();
            foreach ($contracts as $id => $contract) {
                $shippingMethodCode = explode("_", $chronoShippingMethod);
                $shippingMethodCode = isset($shippingMethodCode[1]) ? $shippingMethodCode[1] : $shippingMethodCode[0];
                if(!$this->shippingMethodEnabled($shippingMethodCode, $id)){
                    continue;
                }
                if($contract["number"] == $contractShippingMethod["number"]){
                    $html .= '<option value="' . $id . '" selected="selected">' . $contract["name"] . '</option>';
                } else {
                    $html .= '<option value="' . $id . '">' . $contract["name"] . '</option>';
                }
            }
            $html .= '</select>';

            return $html;
        }
    }

}
