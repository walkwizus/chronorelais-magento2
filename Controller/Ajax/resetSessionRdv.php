<?php
namespace Chronopost\Chronorelais\Controller\Ajax;

use Magento\Framework\App\Action\Context;
use \Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\Session as CheckoutSession;

class resetSessionRdv extends \Magento\Framework\App\Action\Action
{

    /**
     * @var JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * resetSessionRelais constructor.
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CheckoutSession $checkoutSession
    )
    {
        parent::__construct($context);
        $this->_resultJsonFactory = $jsonFactory;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * Reset le point relais en session
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        /* reset session relais */
        $this->_checkoutSession->unsetData("chronopostsrdv_creneaux_info");

        $data = array("suceess" => true);
        $result = $this->_resultJsonFactory->create();
        $result->setData($data);
        return $result;
    }
}
