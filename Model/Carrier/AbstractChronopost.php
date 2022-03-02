<?php
namespace Chronopost\Chronorelais\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;

use Chronopost\Chronorelais\Helper\Webservice as HelperWebservice;

abstract class AbstractChronopost extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
    /**
     * @var string
     */
    //protected $_code = 'chronopost';
    protected $_debugData = array();

    /* verifier le fonctionnement du WS relai */
    const CHECK_RELAI_WS = false;

    /* verifier que le mode fait partie du contrat du client */
    const CHECK_CONTRACT = false;

    const PRODUCT_CODE = '';
    const PRODUCT_CODE_STR = '';

    /* option boite au lettre disponible pour ce mode */
    const OPTION_BAL_ENABLE = false;
    const PRODUCT_CODE_BAL = '';
    const PRODUCT_CODE_BAL_STR = '';

    /* autoriser la livraison le samedi */
    const DELIVER_ON_SATURDAY = false;

    /**
     * @var HelperWebservice
     */
    protected $_helperWebservice;

    /**
     * @var $_helperData
     */
    protected $_helperData;

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * @var \Magento\Shipping\Model\Tracking\ResultFactory
     */
    protected $_trackFactory;

    /**
     * @var \Magento\Shipping\Model\Tracking\Result\StatusFactory
     */
    protected $_trackStatusFactory;


    /**
     * Chronopost constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param $
     * @param HelperWebservice $helperWebservice
     * @param HelperData $helperData
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        HelperWebservice $helperWebservice,
        \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
        \Chronopost\Chronorelais\Helper\Data $helperData,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_helperWebservice = $helperWebservice;
        $this->_trackFactory = $trackFactory;
        $this->_trackStatusFactory = $trackStatusFactory;
        $this->_helperData = $helperData;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * Check if carrier has shipping tracking option available
     *
     * @return bool
     */
    public function isTrackingAvailable()
    {
        return true;
    }

    /**
     * @param $tracking
     * @return mixed
     */
    public function getTrackingInfo($tracking)
    {
        $tracking_url = $this->_scopeConfig->getValue('chronorelais/shipping/tracking_view_url');
        $tracking_url = str_replace('{tracking_number}',$tracking,$tracking_url);

        $status = $this->_trackStatusFactory->create();
        $status->setCarrier($this->_code);
        $status->setCarrierTitle($this->getConfigData('title'));
        $status->setTracking($tracking);
        $status->setPopup(1);
        $status->setUrl($tracking_url);
        return $status;
    }

    /**
     * @return string
     */
    public function getChronoProductCode() {
        return static::PRODUCT_CODE;
    }

    /**
     * @return string
     */
    public function getChronoProductCodeStr() {
        return static::PRODUCT_CODE_STR;
    }

    /**
     * @return string
     */
    public function getChronoProductCodeToShipment() {
        if(static::OPTION_BAL_ENABLE && $this->_scopeConfig->getValue("chronorelais/optionbal/enabled")) {
            return static::PRODUCT_CODE_BAL;
        }
        return static::PRODUCT_CODE;
    }

    /**
     * @return string
     */
    public function getChronoProductCodeToShipmentStr() {
        if(static::OPTION_BAL_ENABLE && $this->_scopeConfig->getValue("chronorelais/optionbal/enabled")) {
            return static::PRODUCT_CODE_BAL_STR;
        }
        return static::PRODUCT_CODE_STR;
    }

    public function optionBalEnable() {
        return static::OPTION_BAL_ENABLE && $this->_scopeConfig->getValue("chronorelais/optionbal/enabled");
    }

    /**
     * @return bool
     */
    public function canDeliverOnSaturday() {
        return static::DELIVER_ON_SATURDAY === true;
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    public function getIsChronoMethod() {
        return true;
    }

    /**
     * @param RateRequest $request
     * @return bool|Result
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $this->_debugData = [];
        $this->_debugData['request'] = array();
        $this->_debugData['error'] = array();
        $this->_debugData['request']['code'] = $this->_code;

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->_rateResultFactory->create();

        $cartWeight = $this->checkCartWeight($request);
        $this->_debugData['request']['cart_weight'] = $cartWeight;
        if($cartWeight === false) {
            $this->_debug($this->_debugData);
            return false;
        }

        if($request->getDestCountryId() === 'FR' && $this->_code == 'chronoexpress') {
            $this->_debug($this->_debugData);
            return false;
        }

        if($request->getDestCountryId() !== 'FR' && in_array($this->_code,array('chronorelais', 'chronopostc10', 'chronopost', 'chronopostc18'))) {
            $this->_debug($this->_debugData);
            return false;
        }

        if(!$this->validateMethod($request)) {
            $this->_debug($this->_debugData);
            return false;
        }

        $shippingPrice = $this->getShippingPrice($request,$cartWeight);
        $this->_debugData['request']['shipping_price'] = $shippingPrice;
        if($shippingPrice === false) {
            $this->_debug($this->_debugData);
            return false;
        }

        /* Frais de dossier */
        $applicationFee = $this->getConfigData('application_fee');
        if($applicationFee) {
            $this->_debugData['request']['application_fee'] = $applicationFee;
            $shippingPrice += $applicationFee;
        }

        /* Frais de traitement */
        $handlingFee = $this->getConfigData('handling_fee');
        if($handlingFee) {
            $this->_debugData['request']['handling_fee'] = $handlingFee;
            $shippingPrice += $handlingFee;
        }

        $shippingPrice = $this->additionalPrice($shippingPrice);

        $this->_debugData['request']['shipping_price_total'] = $shippingPrice;

        /* Freeshipping */
        $freeShippingEnable = $this->getConfigData('free_shipping_enable');
        $freeShippingSubtotal = $this->getConfigData('free_shipping_subtotal');
        $cartTotal = $request->getBaseSubtotalInclTax();

        $this->_debugData['request']['free_shipping_enable'] = (int)$freeShippingEnable;
        $this->_debugData['request']['free_shipping_subtotal'] = (int)$freeShippingSubtotal;

        if($freeShippingEnable && $freeShippingSubtotal<=$cartTotal) {
            $shippingPrice = 0;
        }

        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = $this->_rateMethodFactory->create();

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));

        $method->setMethod($this->_code);

        $method->setMethodTitle($this->getMethodTitle());

        $method->setPrice($shippingPrice);
        $method->setCost($shippingPrice);

        $result->append($method);

        $this->_debug($this->_debugData);

        return $result;
    }

    /**
     * Methode surcahrgé dans les autres mode si besoin d'un prix supplémentaire
     * @param $price
     * @return mixed
     */
    public function additionalPrice($price) {
        return $price;
    }

    /**
     * @return false|string
     */
    public function getMethodTitle() {
        return $this->getConfigData('name');
    }

    /**
     * Verifie si tous les poids des produits sont en dessous du poids limite
     * @param RateRequest $request
     * @return bool|float|int
     */
    protected function checkCartWeight(RateRequest $request) {
        $weight_limit = $this->getConfigData('weight_limit'); /* weight_limit in kg */
        $weight_unit = $this->_scopeConfig->getValue("chronorelais/weightunit/unit");

        $cart_weight = 0;
        if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }

                $itemWeight = $item->getWeight();
                if($weight_unit == 'g')
                {
                    $itemWeight = $itemWeight / 1000; // conversion g => kg
                }
                if($itemWeight > $weight_limit) {
                    $this->_debugData['error'][] = "Poids d'un produit > au poids max";
                    return false;
                }
                $cart_weight += $itemWeight * $item->getQty();
            }
        }
        return $cart_weight;
    }

    /**
     * Additional conditions to show shipping method, each shipping method model might have their own validateMethod function
     * @return bool
     */
    public function validateMethod(RateRequest $request) {
        /* Chronorelais => test Si WS fonctionne */
        if(static::CHECK_RELAI_WS) {
            $webservice = $this->_helperWebservice->getPointsRelaisByCp($request->getDestPostcode());
            if($webservice === false) {
                $this->_debugData['error'][] = "WS chronorelais ne répond pas";
                return false;
            }
        }

        /* verifie si ce mode est present dans le contrat du client */
        if(static::CHECK_CONTRACT) {
            $isAllowed = $this->_helperWebservice->getMethodIsAllowed($this->getChronoProductCode(),$request);
            if($isAllowed === false) {
                $this->_debugData['error'][] = "Méthode ".$this->_code." (".$this->getChronoProductCode().") non présente dans le contrat";
                return false;
            } else {
                $this->_debugData['success'][] = "Méthode ".$this->_code." bien présente dans le contrat";
            }
        }
        return true;
    }

    protected function getShippingPrice(RateRequest $request, $cartWeight) {
        $quickcostEnable = $this->getConfigData('quickcost');
        $corsicaSupplement = 0;
        if($quickcostEnable) { /* récupération du prix via WS */
            $this->_debugData['request']['quickcost'] = 1;
            $quickCostValues = $this->getQuickCostValue($request,$cartWeight);
            if ($quickCostValues && $quickCostValues->errorCode == 0) {
                $quickcost_val = (float) $quickCostValues->amountTTC;

                /* Ajout marge au quickcost */
                if($quickcost_val !== false) {
                    $this->_debugData['request']['quickcost_value'] = $quickcost_val;
                    $quickcost_val = $this->addMargeToQuickcost($quickcost_val);
                    return (float) $quickcost_val;
                }
            }
        } else {
            $this->_debugData['request']['quickcost'] = 0;
            $corsicaSupplement = (float) $this->_scopeConfig->getValue("chronorelais/tarification/corsica_supplement");
        }

        /* récupération du prix via la grille de prix saisie par le client en BO */
        $config = trim($this->getConfigData('config'));
        if($config) {
            try {
                $gridPrices = $this->convertStringToArray($config);
                $price = $this->getPriceFromGrid($gridPrices,$cartWeight);
                if ($request->getDestCountryId() == 'FR' && $request->getDestPostcode() >= 20000 && $request->getDestPostcode() < 21000) {
                    $price += $corsicaSupplement;
                }
                return $price;
            } catch(\Exception $e) {
                // Silence will fall
            }
        }

        $this->_debugData['error'][] = "Pas de prix trouvé";
        return false;
    }

    /**
     * Convertit la grile poids / prix en array
     * la grille n'est pas un vrai json car les clés ne sont pas entourées par des guillemets double
     * @param $string
     * @return array
     */
    protected function convertStringToArray($string) {
        $string = str_replace(array("{","}"),'',$string);
        $array = array();
        $string = explode(",",$string);
        foreach($string as $value) {
            $value = explode(":",$value);
            if(isset($value[0]) && isset($value[1])) {
                $array[trim($value[0])] = trim($value[1]);
            }
        }
        return $array;
    }

    /**
     * Retourne prix par rapport au poids. Si poids du panier > poids max renseigné : pas de prix donc pas de mode de livraison
     * @param $gridPrices
     * @param $cartWeight
     * @return bool
     */
    protected function getPriceFromGrid($gridPrices,$cartWeight) {
        $currentPrice = false;

        $maxWeight = key(array_slice($gridPrices,-1,1,TRUE));

        /* poids du panier > poids max de la grille des prix => on retourne false pour masquer mode de livraison */
        if($cartWeight > $maxWeight) {
            $this->_debugData['error'][] = "Pas de prix trouvé dans la grille. Le poids du panier est > au poids max de la grille";
            return false;
        }

        foreach ($gridPrices as $weight => $price) {
            /*if($cartWeight > $weight ) {
                $currentPrice = (float)$price;
            } else {
                break;
            }*/
            if($cartWeight <= $weight ) {
                $currentPrice = (float)$price;
                break;
            }
        }
        return $currentPrice;
    }

    /**
     * @param RateRequest $request
     * @param $cartWeight
     * @return bool|Object
     */
    protected function getQuickCostValue(RateRequest $request, $cartWeight) {

        $accountNumber = '';
        $accountPassword = '';
        $contract = $this->_helperData->getCarrierContract($this->_code);
        if($contract != null) {
            $accountNumber = $contract['number'];
            $accountPassword = $contract['pass'];
        }

        $productCode = $this->getChronoProductCode();
        $origin_postcode = $this->_scopeConfig->getValue("chronorelais/shipperinformation/zipcode");

        //to get arrival code
        $arrCode = $request->getDestPostcode();
        if ($this->_code == 'chronoexpress' || $this->_code == 'chronocclassic') {
            $arrCode = $request->getDestCountryId();
        }

        $weight_unit = $this->_scopeConfig->getValue("chronorelais/weightunit/unit");
        if ($weight_unit == 'g') {
            $cartWeight = $cartWeight / 1000; /* conversion g => kg */
        }
        $wsParams = array(
            'accountNumber' => $accountNumber,
            'password' => $accountPassword,
            'depCode' => $origin_postcode,
            'arrCode' => $arrCode,
            'weight' => $cartWeight,
            'productCode' => $productCode,
            'type' => 'M'
        );
        $this->_debugData['request']['quickcost_params'] = $wsParams;

        $quickcost_url = $this->getConfigData("quickcost_url");

        return $this->_helperWebservice->getQuickcost($wsParams,$quickcost_url);
    }

    /**
     * @param $quickcost_val
     * @param bool $firstPassage
     * @return false|float|int
     */
    public function addMargeToQuickcost($quickcost_val, $firstPassage = true) {

        $quickcostMarge = $this->getConfigData("quickcost_marge");
        $quickcostMargeType = $this->getConfigData("quickcost_marge_type");

        if($quickcostMarge) {
            if($quickcostMargeType == 'amount') {
                $quickcost_val += $quickcostMarge;
                $this->_debugData['request']['quickcost_marge'] = $quickcostMarge;
            } elseif($quickcostMargeType == 'prcent') {
                $quickcost_val += $quickcost_val * $quickcostMarge / 100;
                $this->_debugData['request']['quickcost_marge'] = $quickcostMarge."%";
            }
        }
        return $quickcost_val;
    }
}
