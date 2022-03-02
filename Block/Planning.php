<?php
namespace Chronopost\Chronorelais\Block;;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Pricing\Helper\Data as HelperPricing;
use Magento\Checkout\Model\Session as CheckoutSession;
/**
 * Class Planning
 * @package Chronopost\Chronorelais\Block
 */
class Planning extends Template
{
    /**
     * @var Resolver
     */
    protected $_resolver;

    /**
     * @var DateTime
     */
    protected $_datetime;

    /**
     * @var HelperPricing
     */
    protected $_helperPricing;

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * Planning constructor.
     * @param Template\Context $context
     * @param Resolver $resolver
     * @param DateTime $dateTime
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Resolver $resolver,
        DateTime $dateTime,
        HelperPricing $helperPricing,
        CheckoutSession $checkoutSession,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_resolver = $resolver;
        $this->_datetime = $dateTime;
        $this->_helperPricing = $helperPricing;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * @return null|string
     */
    public function getLocale() {
        return $this->_resolver->getLocale();
    }

    /**
     * @param $date
     * @return int
     */
    public function getTimestamp($date) {
        return $this->_datetime->timestamp($date);
    }

    /**
     * @return mixed
     */
    public function getRdvConfig() {
        return json_decode($this->_scopeConfig->getValue("carriers/chronopostsrdv/rdv_config"),true);
    }

    /**
     * @param $price
     * @return float|string
     */
    public function currency($price) {
        return $this->_helperPricing->currency($price);
    }

    /**
     * @return mixed
     */
    public function getCarrierBasePrice() {
        $address = $this->getAddress();

        $rates = $address->setCollectShippingRates(true)->collectShippingRates()
            ->getGroupedAllShippingRates();
        $ratePrice = 0;
        foreach($rates as $carrier) {
            foreach ($carrier as $rate) {
                if(preg_match('/chronopostsrdv/',$rate->getCode(),$matches,PREG_OFFSET_CAPTURE)) {
                    $ratePrice = $rate->getPrice();
                    $_srdvConfig = json_decode($this->_scopeConfig->getValue("carriers/chronopostsrdv/rdv_config"),true);
                    if($this->_checkoutSession->getData('chronopostsrdv_creneaux_info')) {
                        $rdvInfo = json_decode($this->_checkoutSession->getData('chronopostsrdv_creneaux_info'),true);
                        $ratePrice -= $_srdvConfig[$rdvInfo['tariffLevel']."_price"];
                    } else {
                        $minimal_price = '';
                        for($i = 1; $i <= 4; $i++) {
                            if($minimal_price === '' || isset($_srdvConfig["N".$i."_price"]) && $_srdvConfig["N".$i."_price"] < $minimal_price) {
                                $minimal_price = $_srdvConfig["N".$i."_price"];
                            }
                        }
                        $ratePrice -= $minimal_price;
                    }
                }
            }
        }

        return $ratePrice;
    }
}