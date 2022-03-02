<?php
namespace Chronopost\Chronorelais\Controller\Ajax;

use Magento\Framework\App\Action\Context;
use \Magento\Framework\Controller\Result\JsonFactory;
use \Chronopost\Chronorelais\Helper\Data as HelperData;
use \Chronopost\Chronorelais\Helper\Webservice as HelperWS;

class checkCarrierConfigContract extends \Magento\Framework\App\Action\Action
{

    /**
     * @var JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var $_helperWS
     */
    protected $_helperWS;

    /**
     * getCarriersLogos constructor.
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param HelperData $_helperData
     * @param HelperWS $_helperWS
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        HelperData $_helperData,
        HelperWS $_helperWS
    )
    {
        parent::__construct($context);
        $this->_resultJsonFactory = $jsonFactory;
        $this->_helperData = $_helperData;
        $this->_helperWS = $_helperWS;
    }

    /**
     * recuperation des logos des modes de livraison chronopost
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $shippingMethod = $params['shippingMethod'];
        $contractId = $params['contractId'];

        $result = $this->_resultJsonFactory->create();

        $data = "not allowed";
        if($this->_helperWS->shippingMethodEnabled($shippingMethod, $contractId)) {
            $data = "allowed";
        }

        $result->setData($data);
        return $result;
    }
}
