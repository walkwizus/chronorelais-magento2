<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Chronopost\Chronorelais\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Chronopost\Chronorelais\Helper\Data as HelperDataChronorelais;
use Chronopost\Chronorelais\Helper\Webservice as HelperWebserviceChronorelais;

class Checklogin extends Action
{

    protected $_resultJsonFactory;

    /**
     * @var HelperDataChronorelais
     */
    protected $_helperData;

    /**
     * @var HelperWebserviceChronorelais
     */
    protected $_helperWebservice;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        HelperDataChronorelais $helperData,
        HelperWebserviceChronorelais $helperWebservice
    )
    {
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_helperData = $helperData;
        $this->_helperWebservice = $helperWebservice;
        parent::__construct($context);
    }

    /**
     * Collect relations data
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $params = $this->_request->getParams();

        $account_number = $params['account_number'];
        $account_pass = $params['account_pass'];

        $result = $this->_resultJsonFactory->create();
        try {

            if(!$account_number || !$account_pass) {
                Throw new \Exception(__("Please enter your account number and password"));
            }

            if(!$this->_helperData->getConfig("chronorelais/shipperinformation/country")) {
                Throw new \Exception(__("Please enter the addresses below"));
            }

            $WSParams = array(
                'accountNumber' => $account_number,
                'password' => $account_pass,
                'depCountryCode' => $this->_helperData->getConfig("chronorelais/shipperinformation/country"),
                'depZipCode' => $this->_helperData->getConfig("chronorelais/shipperinformation/zipcode"),
                'arrCountryCode' => $this->_helperData->getConfig("chronorelais/shipperinformation/country"),
                'arrZipCode' => $this->_helperData->getConfig("chronorelais/shipperinformation/zipcode"),
                'arrCity' => $this->_helperData->getConfig("chronorelais/shipperinformation/city"),
                'type' => 'M',
                'weight' => 1
            );

            $webservbt = (array)$this->_helperWebservice->checkLogin($WSParams);

            $result->setData($webservbt);
        } catch (\Exception $e) {
            $result->setData(['return' => ['errorCode' => 1, 'message' => $e->getMessage()]]);
        }
        return $result;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Chronopost_Chronorelais::config_chronorelais');
    }
}