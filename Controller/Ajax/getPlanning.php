<?php
namespace Chronopost\Chronorelais\Controller\Ajax;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\LayoutFactory;

use Chronopost\Chronorelais\Helper\Data as HelperData;
use Chronopost\Chronorelais\Helper\Webservice as HelperWebservice;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote\AddressFactory;
class getPlanning extends \Magento\Framework\App\Action\Action
{

    /**
     * @var JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var LayoutFactory
     */
    protected $_layoutFactory;

    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var HelperWebservice
     */
    protected $_helperWebservice;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var AddressFactory
     */
    protected $_addressFactory;

    /**
     * getRelais constructor.
     * @param Context $context
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        LayoutFactory $layoutFactory,
        HelperData $helperData,
        HelperWebservice $helperWebservice,
        Session $session,
        AddressFactory $addressFactory
    )
    {
        parent::__construct($context);
        $this->_resultJsonFactory = $jsonFactory;
        $this->_layoutFactory = $layoutFactory;
        $this->_helperData = $helperData;
        $this->_helperWebservice = $helperWebservice;
        $this->_checkoutSession = $session;
        $this->_addressFactory = $addressFactory;
    }

    /**
     * recuperation des logos des modes de livraison chronopost
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $methodCode = $this->getRequest()->getParam('method_code');
        $result = $this->_resultJsonFactory->create();

        $resultData = array();

        try {
            $shippingAddressData = $this->getRequest()->getParam('shipping_address');
            //$shippingAddress = $this->_addressFactory->create()->setData($shippingAddressData);
            $shippingAddress = $this->_checkoutSession->getQuote()->getShippingAddress();
            $shippingAddress->addData($shippingAddressData);
            //$shippingAddress->setQuote($this->_checkoutSession->getQuote());
            $creneaux = $this->_helperWebservice->getPlanning($shippingAddress);
            if($creneaux) {
                $layout = $this->_layoutFactory->create();
                $content = $layout->createBlock("\Chronopost\Chronorelais\Block\Planning")
                    ->setAddress($shippingAddress)
                    ->setMethodCode($methodCode)
                    ->setCreneaux($creneaux)
                    ->setTemplate("Chronopost_Chronorelais::chronopostsrdv_planning.phtml")
                    ->toHtml();

                $resultData['method_code'] = $methodCode;
                $resultData['content'] = $content;
                $resultData['creneaux'] = $creneaux;
            } else {
                Throw new \Exception(__("It is not yet possible to use this service for your order. We are working to make this new service available in other cities."));
            }
        } catch(\Exception $e) {
            $resultData['error'] = true;
            $resultData['message'] = $e->getMessage();
        }

        $result->setData($resultData);
        return $result;
    }
}
