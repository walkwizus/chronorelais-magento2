<?php
namespace Chronopost\Chronorelais\Controller\Ajax;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\LayoutFactory;

use Chronopost\Chronorelais\Helper\Data as HelperData;
use Chronopost\Chronorelais\Helper\Webservice as HelperWebservice;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote\AddressFactory;
use \Magento\Framework\View\Asset\Repository;
class getRelais extends \Magento\Framework\App\Action\Action
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
     * @var Repository
     */
    protected $_assetRepo;

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
        AddressFactory $addressFactory,
        Repository $repository
    )
    {
        parent::__construct($context);
        $this->_resultJsonFactory = $jsonFactory;
        $this->_layoutFactory = $layoutFactory;
        $this->_helperData = $helperData;
        $this->_helperWebservice = $helperWebservice;
        $this->_checkoutSession = $session;
        $this->_addressFactory = $addressFactory;
        $this->_assetRepo = $repository;
    }

    /**
     * recuperation des logos des modes de livraison chronopost
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $methodCode = $this->getRequest()->getParam('method_code');
        $postcode = $this->getRequest()->getParam('postcode');
        $result = $this->_resultJsonFactory->create();

        $resultData = array();

        try {

            $shippingAddressData = $this->getRequest()->getParam('shipping_address');
            $shippingAddress = $this->_addressFactory->create()->setData($shippingAddressData);
            if(!$postcode || $postcode == 'false') { /* recherche par adresse */
                $postcode = $shippingAddress->getPostcode();
            } else {
                $shippingAddress->setData('postcode', $postcode);
                $shippingAddress->setData('city', 'unknown');
                $shippingAddress->setData('country', 'unknown');
                $shippingAddress->setData('street', 'unknown');
            }
            $relaypoints = $this->_helperWebservice->getPointRelaisByAddress($methodCode,$shippingAddress);

            if($relaypoints) {
                $layout = $this->_layoutFactory->create();
                $content = $layout->createBlock("\Chronopost\Chronorelais\Block\Chronorelais")
                    ->setMethodCode($methodCode)
                    ->setCanChangePostcode($this->_helperData->getConfig('carriers/'.$methodCode.'/can_change_postcode'))
                    ->setCanShowMap($this->_helperData->getConfig('carriers/'.$methodCode.'/show_map'))
                    ->setPostcode($postcode)
                    ->setRelaypoints($relaypoints)
                    ->setTemplate("Chronopost_Chronorelais::chronorelais.phtml")
                    ->toHtml();

                $resultData['method_code'] = $methodCode;
                $resultData['content'] = $content;
                $resultData['relaypoints'] = $relaypoints;
                $resultData['chronopost_chronorelais_relais_id'] = $this->_checkoutSession->getData("chronopost_chronorelais_relais_id");

                $paramsImg = array('_secure' => $this->getRequest()->isSecure());
                $resultData['relay_icon'] = $this->_assetRepo->getUrlWithParams('Chronopost_Chronorelais::images/relay_icon.png', $paramsImg);
                $resultData['home_icon'] = $this->_assetRepo->getUrlWithParams('Chronopost_Chronorelais::images/home_icon.png', $paramsImg);

                $resultData['trads'] = array(
                    'horaires' => $this->_helperData->getLibelleGmap('horaires'),
                    'informations' => $this->_helperData->getLibelleGmap('informations'),
                    'ferme' => $this->_helperData->getLibelleGmap('ferme'),
                    'lundi' => $this->_helperData->getLibelleGmap('lundi'),
                    'mardi' => $this->_helperData->getLibelleGmap('mardi'),
                    'mercredi' => $this->_helperData->getLibelleGmap('mercredi'),
                    'jeudi' => $this->_helperData->getLibelleGmap('jeudi'),
                    'vendredi' => $this->_helperData->getLibelleGmap('vendredi'),
                    'samedi' => $this->_helperData->getLibelleGmap('samedi'),
                    'dimanche' => $this->_helperData->getLibelleGmap('dimanche')
                );
            } else {
                Throw new \Exception(__("There is no pick-up for this address"));
            }
        } catch(\Exception $e) {
            $resultData['error'] = true;
            $resultData['message'] = $e->getMessage();
        }

        $result->setData($resultData);
        return $result;
    }
}
