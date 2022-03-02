<?php
namespace Chronopost\Chronorelais\Controller\Ajax;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Chronopost\Chronorelais\Helper\Webservice as HelperWebservice;

class setSessionRelais extends \Magento\Framework\App\Action\Action
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
     * @var HelperWebservice
     */
    protected $_helperWebservice;

    /**
     * setSessionRelais constructor.
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CheckoutSession $checkoutSession,
        HelperWebservice $webservice
    )
    {
        parent::__construct($context);
        $this->_resultJsonFactory = $jsonFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_helperWebservice = $webservice;
    }

    /**
     * Reset le point relais en session
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        /* set session relais */
        $relaisId = $this->getRequest()->getParam('relais_id');

        try {
            $relais = $this->_helperWebservice->getDetailRelaisPoint($relaisId);
            if($relais) {
                $relaisidbefore = $this->_checkoutSession->getData("chronopost_chronorelais_relais_id");
                $this->_checkoutSession->setData("chronopost_chronorelais_relais_id",$relaisId);
                $relaisidafter = $this->_checkoutSession->getData("chronopost_chronorelais_relais_id");

                if(isset($relais->nom) ){
                    $nom = $relais->nom;
                }else{
                    $nom =  $relais->nomEnseigne;
                }


                $data = array(
                    "success" => true,
                    "relais_id_before" => $relaisidbefore,
                    "relais_id_after" => $relaisidafter,
                    "relais" => array(
                        "city" => $relais->localite,
                        "postcode" => $relais->codePostal,
                        "street" => array($relais->adresse1,$relais->adresse2,$relais->adresse3),
                        "company" => $nom,
                        "saveInAddressBook" => 0,
                        "sameAsBilling" => 0
                    )
                );
            } else {
                $data = array("error" => true,"message" => __("The pick-up point does not exist."));
            }
        } catch(\Exception $e) {
            $data = array("error" => true,"message" => __($e->getMessage()));
        }


        $result = $this->_resultJsonFactory->create();
        $result->setData($data);
        return $result;
    }
}
