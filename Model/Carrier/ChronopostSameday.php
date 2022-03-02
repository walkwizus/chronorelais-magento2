<?php
namespace Chronopost\Chronorelais\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use \Magento\Framework\Stdlib\DateTime\DateTime;

class ChronopostSameday extends AbstractChronopost
{
    /**
     * @var string
     */
    protected $_code = 'chronosameday';

    const PRODUCT_CODE = '4I';
    const PRODUCT_CODE_STR = 'SMD';

    const CHECK_CONTRACT = true;

    /* autoriser la livraison le samedi */
    const DELIVER_ON_SATURDAY = true;

    protected $_datetime;

    /**
     * ChronopostSameday constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Chronopost\Chronorelais\Helper\Webservice $helperWebservice
     * @param \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory
     * @param \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory
     * @param DateTime $dateTime
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Chronopost\Chronorelais\Helper\Webservice $helperWebservice,
        \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
        DateTime $dateTime,
        \Chronopost\Chronorelais\Helper\Data $helperData,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $rateResultFactory, $rateMethodFactory, $helperWebservice, $trackFactory, $trackStatusFactory,$helperData, $data);
        $this->_datetime = $dateTime;
    }

    public function validateMethod(RateRequest $request)
    {
        $validate = parent::validateMethod($request);

        if ($validate === true) {

            // Check if we should auto disable the module (it's past hour)
            date_default_timezone_set($this->_scopeConfig->getValue("general/locale/timezone"));
            $deliveryTimeLimitConf = $this->getConfigData("delivery_time_limit");
            // Safe fallback
            if (!$deliveryTimeLimitConf) {
                $deliveryTimeLimitConf = '15:00';
            }
            $deliveryTimeLimit = new \DateTime(date('Y-m-d') . ' ' . $deliveryTimeLimitConf . ':00');
            $currentTime = new \DateTime('NOW');

            if ($this->_datetime->timestamp($currentTime->getTimestamp()) <= $deliveryTimeLimit->getTimestamp()) {
                $validate = true;
            } else {
                $validate = false;
                $this->_debugData['error'][] = "heure limite dépassée : heure courante : ".$currentTime->format("H:i").", heure max : ".$deliveryTimeLimit->format("H:i");
            }
        }

        return $validate;
    }
}