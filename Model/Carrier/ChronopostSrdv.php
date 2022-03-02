<?php
namespace Chronopost\Chronorelais\Model\Carrier;

use Magento\Checkout\Model\Session as CheckoutSession;
class ChronopostSrdv extends AbstractChronopost
{
    /**
     * @var string
     */
    protected $_code = 'chronopostsrdv';

    const PRODUCT_CODE = '2O';
    const CARRIER_CODE = 'chronopostsrdv';
    const PRODUCT_CODE_STR = 'SRDV';

    const CHECK_CONTRACT = true;

    /* autoriser la livraison le samedi */
    const DELIVER_ON_SATURDAY = true;

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Chronopost\Chronorelais\Helper\Webservice $helperWebservice,
        \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
        CheckoutSession $checkoutSession,
        \Chronopost\Chronorelais\Helper\Data $helperData,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $rateResultFactory, $rateMethodFactory, $helperWebservice, $trackFactory, $trackStatusFactory,$helperData, $data);
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * Ajout prix tarifLevel
     * @param $price
     * @return mixed
     */
    public function additionalPrice($price) {
        $price = parent::additionalPrice($price);

        $_srdvConfig = $this->getConfigData('rdv_config');
        $_srdvConfig = json_decode($_srdvConfig,true);

        if($this->_checkoutSession->getData('chronopostsrdv_creneaux_info')) {
            $chronopostsrdv_creneaux_info = json_decode($this->_checkoutSession->getData('chronopostsrdv_creneaux_info'),true);
            $tarifLevel = $chronopostsrdv_creneaux_info['tariffLevel'];
            $price += $_srdvConfig[$tarifLevel."_price"];
        } else {
            $minimal_price = '';
            for($i = 1; $i <= 4; $i++) {
                if($minimal_price === '' || isset($_srdvConfig["N".$i."_price"]) && $_srdvConfig["N".$i."_price"] < $minimal_price) {
                    $minimal_price = $_srdvConfig["N".$i."_price"];
                }
            }
            $price += $minimal_price;
        }
        return $price;
    }

    /**
     * Ajout date livraison dans title
     * @return false|string
     */
    public function getMethodTitle() {
        $methodTitle = parent::getMethodTitle();

        if($this->_checkoutSession->getData('chronopostsrdv_creneaux_info')) {
            $chronopostsrdv_creneaux_info = json_decode($this->_checkoutSession->getData('chronopostsrdv_creneaux_info'),true);

            $_dateRdvStart = new \DateTime($chronopostsrdv_creneaux_info['deliveryDate']);
            $_dateRdvStart->setTime($chronopostsrdv_creneaux_info['startHour'],$chronopostsrdv_creneaux_info['startMinutes']);

            $_dateRdvEnd = new \DateTime($chronopostsrdv_creneaux_info['deliveryDate']);
            $_dateRdvEnd->setTime($chronopostsrdv_creneaux_info['endHour'],$chronopostsrdv_creneaux_info['endMinutes']);

            $methodTitle .= ' - '.__('On').' '.$_dateRdvStart->format('d/m/Y');
            $methodTitle .= ' '.__('between %1 and %2',$_dateRdvStart->format('H:i'),$_dateRdvEnd->format('H:i'));
        }
        return $methodTitle;
    }
}