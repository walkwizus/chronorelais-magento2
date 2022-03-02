<?php

namespace Chronopost\Chronorelais\Helper;

use Chronopost\Chronorelais\Model\ResourceModel\OrderExportStatus\CollectionFactory as OrderExportStatusCollectionFactory;
use Chronopost\Chronorelais\Model\ContractsOrdersFactory;
use Magento\Shipping\Model\CarrierFactory;
use \Magento\Framework\Module\Dir\Reader;
use \Magento\Store\Model\ScopeInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const MODULE_NAME = "Chronopost_Chronorelais";


    const CHRONO_POST = '01'; // for France
    const CHRONO_POST_BAL = '58'; // For france avec option BAL
    const CHRONORELAIS = '86'; // for Chronorelais
    const CHRONO_EXPRESS = '17';  // for International
    const CHRONOPOST_C10 = '02'; // for Chronopost C10
    const CHRONOPOST_C18 = '16'; // for Chronopost C18
    const CHRONOPOST_C18_BAL = '2M'; // for Chronopost C18 avec option BAL
    const CHRONOPOST_CClassic = '44'; // for Chronopost CClassic
    const CHRONORELAISEUROPE = '49'; // for Chronorelais Europe
    const CHRONORELAISDOM = '4P'; // for Chronorelais DOM
    const CHRONOPOST_SMD = '4I'; // for Chronopost SAMEDAY
    const CHRONOPOST_SRDV = '2O'; // for Chronopost Sur Rendez-vous 'O' majuscule et non 0
    const CHRONOPOST_DIM_BAL = '5A'; // for Chronopost Dimanche BAL
    const CHRONOPOST_REVERSE_R = '4R'; // for Chronopost Reverse 9
    const CHRONOPOST_REVERSE_S = '4S'; // for Chronopost Reverse 10
    const CHRONOPOST_REVERSE_T = '4T'; // for Chronopost Reverse 13
    const CHRONOPOST_REVERSE_U = '4U'; // for Chronopost Reverse 18
    const CHRONOPOST_REVERSE_DEFAULT = '01'; // for Chronopost Reverse 18
    const CHRONOPOST_REVERSE_RELAIS_EUROPE = '3T'; // for Chronopost Reverse RelaisEurope

    const CHRONOPOST_REVERSE_R_SERVICE = '885'; // for Chronopost Reverse 9
    const CHRONOPOST_REVERSE_S_SERVICE = '180'; // for Chronopost Reverse 10
    const CHRONOPOST_REVERSE_T_SERVICE = '898'; // for Chronopost Reverse 13
    const CHRONOPOST_REVERSE_U_SERVICE = '835'; // for Chronopost Reverse 18
    const CHRONOPOST_REVERSE_DEFAULT_SERVICE = '226'; // for Chronopost Reverse 18

    /**
     * @var OrderExportStatusCollectionFactory
     */
    protected $_orderExportStatusCollectionFactory;

    /**
     * @var CarrierFactory
     */
    protected $_carrierFactory;
    protected $contractsOrdersFactory;
    protected $_orderRepository;
    protected $_moduleReader;
    /**
     * @var Webservice
     */
    private $helperWS;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        OrderExportStatusCollectionFactory $collectionFactory,
        ContractsOrdersFactory $contractsOrdersFactory,
        CarrierFactory $carrierFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        Reader $moduleReader
    )
    {
        parent::__construct($context);
        $this->_orderExportStatusCollectionFactory = $collectionFactory;
        $this->_carrierFactory = $carrierFactory;
        $this->contractsOrdersFactory = $contractsOrdersFactory;
        $this->_orderRepository = $orderRepository;
        $this->_moduleReader = $moduleReader;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $shippingMethod
     * @return bool
     */
    public function orderIsOverLimit(\Magento\Sales\Model\Order $order, $shippingMethod)
    {
        $shippingMethod = explode("_", $shippingMethod);
        $shippingMethod = isset($shippingMethod[1]) ? $shippingMethod[1] : $shippingMethod[0];
        $weight_limit = $this->getConfig('carriers/' . $shippingMethod . '/weight_limit');

        $weightShipping = 0;
        foreach ($order->getAllItems() as $item) {
            $weightShipping += $item->getWeight() * $item->getQtyOrdered();
        }
        if ($this->getConfig('chronorelais/weightunit/unit') == 'g') {
            $weightShipping = $weightShipping / 1000; // conversion g => kg
        }

        return $weightShipping > $weight_limit;
    }

    /**
     * @param $node
     * @return mixed
     */
    public function getConfig($node)
    {
        return $this->scopeConfig->getValue($node);
    }

    /**
     * return true if method is chrono
     * @param $shippingMethod
     * @return mixed
     */
    public function isChronoMethod($shippingMethod)
    {
        $carrier = $this->_carrierFactory->get($shippingMethod);
        return $carrier ? $carrier->getIsChronoMethod() : false;
    }


    /********************************** Gestion Livraison samedi ******************************/

    /**
     * @return bool
     */
    public function isSendingDay()
    {
        $shipping_days = $this->getSaturdayShippingDays();
        $current_date = $this->getCurrentTimeByZone("Europe/Paris", "Y-m-d H:i:s");

        //get timestamps
        $start_timestamp = strtotime($shipping_days['startday'] . " this week " . $shipping_days['starttime']);
        $end_timestamp = strtotime($shipping_days['endday'] . " this week " . $shipping_days['endtime']);
        $current_timestamp = strtotime($current_date);

        $sending_day = false;
        if ($current_timestamp >= $start_timestamp && $current_timestamp <= $end_timestamp) {
            $sending_day = true;
        }

        return $sending_day;
    }

    /**
     * @return array
     */
    public function getSaturdayShippingDays()
    {

        $starday = explode(":", $this->scopeConfig->getValue("chronorelais/saturday/startday"));
        $endday = explode(":", $this->scopeConfig->getValue("chronorelais/saturday/endday"));

        $saturdayDays = array();
        $saturdayDays['startday'] = (count($starday) == 3 && isset($starday[0])) ? $starday[0] : $this->SaturdayShippingDays['startday'];
        $saturdayDays['starttime'] = (count($starday) == 3 && isset($starday[1])) ? $starday[1] . ':' . $starday[2] . ':00' : $this->SaturdayShippingDays['starttime'];
        $saturdayDays['endday'] = (count($endday) == 3 && isset($endday[0])) ? $endday[0] : $this->SaturdayShippingDays['endday'];
        $saturdayDays['endtime'] = (count($endday) == 3 && isset($endday[1])) ? $endday[1] . ':' . $endday[2] . ':00' : $this->SaturdayShippingDays['endtime'];

        return $saturdayDays;
    }

    /**
     * @param string $timezone
     * @param string $format
     * @return string
     */
    public function getCurrentTimeByZone($timezone = "Europe/Paris", $format = "l H:i")
    {
        $d = new \DateTime("now", new \DateTimeZone($timezone));
        return $d->format($format);
    }

    /**
     * @param $order_id
     * @return mixed
     */
    public function getLivraisonSamediStatus($order_id)
    {

        $coll = $this->_orderExportStatusCollectionFactory->create();
        $coll->addFieldToFilter("order_id", $order_id)->addFieldToSelect("livraison_le_samedi");

        $status = $coll->getFirstItem();
        return $status->getData('livraison_le_samedi');
    }

    /**
     * @param $productCodes
     * @return array
     */
    public function getReturnProductCodesAllowed($productCodes)
    {
        $possibleReturnProductCode = array(
            static::CHRONOPOST_REVERSE_R,
            static::CHRONOPOST_REVERSE_S,
            static::CHRONOPOST_REVERSE_T,
            static::CHRONOPOST_REVERSE_U,
            static::CHRONOPOST_REVERSE_RELAIS_EUROPE,
        );
        $returnProductCode = array();
        foreach ($productCodes as $code) {
            if (in_array($code, $possibleReturnProductCode)) {
                array_push($returnProductCode, $code);
            }

            if($code == static::CHRONORELAISEUROPE){
                array_push($returnProductCode, self::CHRONOPOST_REVERSE_RELAIS_EUROPE);
            }
        }

        return (sizeof($returnProductCode) > 0) ? $returnProductCode : array(static::CHRONOPOST_REVERSE_DEFAULT);

    }


    /*** gestion retour ***/

    /**
     * @param $code
     * @return string
     */
    public function getReturnServiceCode($code)
    {
        switch ($code) {
            case static::CHRONOPOST_REVERSE_R:
                return static::CHRONOPOST_REVERSE_R_SERVICE;
                break;
            case static::CHRONOPOST_REVERSE_S:
                return static::CHRONOPOST_REVERSE_S_SERVICE;
                break;
            case static::CHRONOPOST_REVERSE_T:
                return static::CHRONOPOST_REVERSE_T_SERVICE;
                break;
            case static::CHRONOPOST_REVERSE_U:
                return static::CHRONOPOST_REVERSE_U_SERVICE;
                break;
            case static::CHRONOPOST_REVERSE_DEFAULT:
                return static::CHRONOPOST_REVERSE_DEFAULT_SERVICE;
                break;
            default :
                return static::CHRONOPOST_REVERSE_DEFAULT_SERVICE;
                break;
        }
    }

    /**
     * @return array
     */
    public function getMatriceReturnCode()
    {
        return array(
            static::CHRONOPOST_REVERSE_R => array(
                array(static::CHRONOPOST_REVERSE_R),
                array(static::CHRONOPOST_REVERSE_R, static::CHRONOPOST_REVERSE_U)
            ),
            static::CHRONOPOST_REVERSE_S => array(
                array(static::CHRONOPOST_REVERSE_S),
                array(static::CHRONOPOST_REVERSE_R, static::CHRONOPOST_REVERSE_S),
                array(static::CHRONOPOST_REVERSE_S, static::CHRONOPOST_REVERSE_U),
                array(static::CHRONOPOST_REVERSE_R, static::CHRONOPOST_REVERSE_S, static::CHRONOPOST_REVERSE_U)
            ),
            static::CHRONOPOST_REVERSE_U => array(
                array(static::CHRONOPOST_REVERSE_U)
            ),
            static::CHRONOPOST_REVERSE_RELAIS_EUROPE => array(
                array(static::CHRONOPOST_REVERSE_RELAIS_EUROPE)
            ),
            static::CHRONOPOST_REVERSE_T => array(
                array(static::CHRONOPOST_REVERSE_T),
                array(static::CHRONOPOST_REVERSE_R, static::CHRONOPOST_REVERSE_T),
                array(static::CHRONOPOST_REVERSE_S, static::CHRONOPOST_REVERSE_T),
                array(static::CHRONOPOST_REVERSE_T, static::CHRONOPOST_REVERSE_U),
                array(static::CHRONOPOST_REVERSE_R, static::CHRONOPOST_REVERSE_S, static::CHRONOPOST_REVERSE_T),
                array(static::CHRONOPOST_REVERSE_R, static::CHRONOPOST_REVERSE_T, static::CHRONOPOST_REVERSE_U),
                array(static::CHRONOPOST_REVERSE_S, static::CHRONOPOST_REVERSE_T, static::CHRONOPOST_REVERSE_U),
                array(
                    static::CHRONOPOST_REVERSE_R,
                    static::CHRONOPOST_REVERSE_S,
                    static::CHRONOPOST_REVERSE_T,
                    static::CHRONOPOST_REVERSE_U
                )
            ),
            static::CHRONOPOST_REVERSE_DEFAULT => array(
                array(static::CHRONOPOST_REVERSE_DEFAULT)
            )
        );
    }

    /**
     * @param \Magento\sales\Model\Order $_order
     * @return bool
     */
    public function hasOptionBAL($_order)
    {
        $shippingMethod = explode('_', $_order->getShippingMethod());
        if (isset($shippingMethod[1])) {
            $shippingMethod = $shippingMethod[1];
            $carrier = $this->_carrierFactory->get($shippingMethod);
            return $carrier && $carrier->getIsChronoMethod() ? $carrier->optionBalEnable() : false;
        }
        return false;
    }

    /**
     * @param $_order
     * @return int|mixed
     */
    public function getOrderAdValorem($_order)
    {
        $totalAdValorem = 0;

        if ($this->getConfig("chronorelais/assurance/enabled")) {
            $minAmount = $this->getConfig("chronorelais/assurance/amount");
            $maxAmount = $this->getMaxAdValoremAmount();

            $items = $_order->getAllItems();

            $totalAdValorem = 0;

            foreach ($items as $item) {
                $totalAdValorem += $item->getPrice() * $item->getQtyOrdered();
            }
            $totalAdValorem = min($totalAdValorem, $maxAmount);
            /* Si montant < au montant minimum ad valorem => pas d'assurance */
            if ($totalAdValorem < $minAmount) {
                $totalAdValorem = 0;
            }
        }

        return $totalAdValorem;
    }

    /**
     * @return int
     */
    public function getMaxAdValoremAmount()
    {
        return 20000;
    }

    public function getSpecificContract($id, $storeId = null, $websiteCode = null)
    {
        if ($id !== null && (int)$id >= 0) {
            $contracts = $this->getConfigContracts(null, $storeId, $websiteCode);

            if(isset($contracts[$id])){
                return $contracts[$id];
            }
        }
        return null;
    }

    public function getConfigContracts($JSONformat = false)
    {
        $config = $this->scopeConfig->getValue('chronorelais/contracts/contracts', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if ($JSONformat) {
            return $config;
        }

        return json_decode($config, true);
    }

    public function getCarrierContract($code)
    {
        $contracts = $this->getConfigContracts();
        $numContract = $this->scopeConfig->getValue('carriers/' . $code . '/contracts', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $numContract = (!$numContract || count($contracts) - 1 < $numContract) ? 0 : $numContract;
        $contracts[$numContract]['numContract'] = (int)$numContract;

        return $contracts[$numContract];
    }


    public function getContractByOrderId($orderId)
    {

        $contract = false;

        $collection = $this->contractsOrdersFactory->create()->getCollection()
            ->addFieldToFilter('order_id', $orderId);

        if(count($collection) !== 0){

            $contract = $collection->getFirstItem();

        }
        return $contract;
    }

    public function getWeightOfOrder($orderId)
    {
        $order = $this->_orderRepository->get($orderId);
        $totalWeight = 0;
        foreach ($order->getAllVisibleItems() as $item) {
            $totalWeight += $item->getWeight() * $item->getQtyOrdered();
        }

        return $totalWeight;
    }


    public function getChronoProductCode($code = '')
    {
        $code = strtolower($code);

        switch ($code) {
            case 'chronorelais' :
                $productcode = static::CHRONORELAIS;
                break;
            case 'chronopost' :
                $productcode = static::CHRONO_POST;
                break;
            case 'chronoexpress' :
                $productcode = static::CHRONO_EXPRESS;
                break;
            case 'chronopostc10' :
                $productcode = static::CHRONOPOST_C10;
                break;
            case 'chronopostc18' :
                $productcode = static::CHRONOPOST_C18;
                break;
            case 'chronocclassic' :
                $productcode = static::CHRONOPOST_CClassic;
                break;
            case 'chronorelaiseur' :
                $productcode = static::CHRONORELAISEUROPE;
                break;
            case 'chronorelaisdom' :
                $productcode = static::CHRONORELAISDOM;
                break;
            case 'chronosameday' :
                $productcode = static::CHRONOPOST_SMD;
                break;
            case 'chronopostsrdv' :
                $productcode = static::CHRONOPOST_SRDV;
                break;
            case 'chronopostdimanche' :
                $productcode = static::CHRONOPOST_DIM_BAL;
                break;
            default :
                $productcode = static::CHRONO_POST;
                break;
        }

        return $productcode;
    }

    public function getChronoProductCodeToShipment($code = '')
    {
        $code = strtolower($code);

        switch ($code) {
            case 'chronorelais' :
                $productcode = static::CHRONORELAIS;
                break;
            case 'chronopost' :
                if ($this->getConfigOptionBAL()) {
                    $productcode = static::CHRONO_POST_BAL;
                } else {
                    $productcode = static::CHRONO_POST;
                }
                break;
            case 'chronoexpress' :
                $productcode = static::CHRONO_EXPRESS;
                break;
            case 'chronopostc10' :
                $productcode = static::CHRONOPOST_C10;
                break;
            case 'chronopostc18' :
                if ($this->getConfigOptionBAL()) {
                    $productcode = static::CHRONOPOST_C18_BAL;
                } else {
                    $productcode = static::CHRONOPOST_C18;
                }
                break;
            case 'chronopostcclassic' :
                $productcode = static::CHRONOPOST_CClassic;
                break;
            case 'chronorelaiseurope' :
                $productcode = static::CHRONORELAISEUROPE;
                break;
            case 'chronorelaisdom' :
                $productcode = static::CHRONORELAISDOM;
                break;
            case 'chronopostsameday' :
                $productcode = static::CHRONOPOST_SMD;
                break;
            case 'chronopostsrdv' :
                $productcode = static::CHRONOPOST_SRDV;
                break;
            case 'chronopostdimanche' :
                $productcode = static::CHRONOPOST_DIM_BAL;
                break;
            default :
                $productcode = static::CHRONO_POST;
                break;
        }

        return $productcode;
    }

    public function getConfigOptionBAL()
    {
        return $this->getConfig('chronorelais/optionbal/enabled');
    }

    /**
     * Shipper Information
     * @param $field
     * @return String
     */
    public function getConfigurationShipperInfo($field)
    {
        $fieldValue = '';
        if ($field && $this->getConfig('chronorelais/shipperinformation/' . $field)) {
            $fieldValue = $this->getConfig('chronorelais/shipperinformation/' . $field);
        }

        return $fieldValue;
    }

    /**
     * Verifie si ghostscript est installé
     * @return bool
     */
    public function gsIsActive() {
        $cmdTestGs = $this->getConfig("chronorelais/shipping/gs_path") ." -v";
        return shell_exec($cmdTestGs) !== null;
    }

    public function getConfigFilePath($name)
    {
        $path = $this->_moduleReader->getModuleDir('', 'Chronopost_Chronorelais');
        return $path . '/config/' . $name;
    }

    public function returnAuthorized($countryId)
    {
        $csvName = $this->getConfigFilePath('countriesReturnAuthorized.csv');
        $countriesIdsAuthorized = [];

        if (($handle = fopen($csvName, "r")) !== false) {
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                foreach ($data as $id) {
                    array_push($countriesIdsAuthorized, $id);
                }
            }

            fclose($handle);
        }

        return in_array($countryId, $countriesIdsAuthorized);
    }


    /**
     * @param $label
     *
     * @return mixed
     */
    public function getLibelleGmap($label)
    {
        return $this->getStoreConfigData('chronorelais/libelles_gmap/' . $label, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $node
     * @param $store
     *
     * @return mixed
     */
    public function getStoreConfigData($node, $store)
    {
        return $this->scopeConfig->getValue($node, $store);
    }

    public function getWeightLimit($shippingMethod) {
        if ($shippingMethod === 'chronorelaiseur_chronorelaiseur' || $shippingMethod === 'chronorelais_chronorelais') {
            return 20;
        } else {
            return 30;
        }
    }

    public function getInputDimensionsLimit($shippingMethod) {
        if ($shippingMethod === 'chronorelaiseur_chronorelaiseur' || $shippingMethod === 'chronorelais_chronorelais') {
            return 100;
        } else {
            return 150;
        }
    }

    public function getGlobalDimensionsLimit($shippingMethod) {
        if ($shippingMethod === 'chronorelaiseur_chronorelaiseur' || $shippingMethod === 'chronorelais_chronorelais') {
            return 250;
        } else {
            return 300;
        }
    }

    public function getWeightUnit()
    {
        return $this->scopeConfig->getValue(
            'general/locale/weight_unit',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

}
