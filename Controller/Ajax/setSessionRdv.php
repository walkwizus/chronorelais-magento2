<?php
namespace Chronopost\Chronorelais\Controller\Ajax;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Chronopost\Chronorelais\Helper\Webservice as HelperWebservice;

class setSessionRdv extends \Magento\Framework\App\Action\Action
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
     * setSessionRdv constructor.
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param CheckoutSession $checkoutSession
     * @param HelperWebservice $webservice
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
        /* set session rdv */
        $chronopostsrdv_creneaux_info = $this->getRequest()->getParam('chronopostsrdv_creneaux_info');

        try {
            $confirm = $this->_helperWebservice->confirmDeliverySlot($chronopostsrdv_creneaux_info);
            if($confirm->return->code == 0) {
                $this->_checkoutSession->setData("chronopostsrdv_creneaux_info",json_encode($chronopostsrdv_creneaux_info));

                $dateRdv = new \DateTime($chronopostsrdv_creneaux_info['deliveryDate']);
                $dateRdv = $dateRdv->format("d/m/Y");

                $heureDebut = $chronopostsrdv_creneaux_info['startHour'].":".str_pad($chronopostsrdv_creneaux_info['startMinutes'],2,'0',STR_PAD_LEFT);
                $heureFin = $chronopostsrdv_creneaux_info['endHour'].":".str_pad($chronopostsrdv_creneaux_info['endMinutes'],2,'0',STR_PAD_LEFT);

                $data = array(
                    "success" => true,
                    "rdvInfo" => " - ".__("On %1 between %2 and %3",$dateRdv,$heureDebut,$heureFin)
                );
            } else {
                $data = array("error" => true,"message" => __($confirm->return->message));
            }
        } catch(\Exception $e) {
            $data = array("error" => true,"message" => __($e->getMessage()));
        }


        $result = $this->_resultJsonFactory->create();
        $result->setData($data);
        return $result;
    }
}
