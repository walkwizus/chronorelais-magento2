<?php
namespace Chronopost\Chronorelais\Controller\Ajax;

use Magento\Framework\App\Action\Context;
use \Magento\Framework\Controller\Result\JsonFactory;
use \Magento\Shipping\Model\Config;
use \Magento\Framework\View\Asset\Repository;
use \Magento\Framework\App\Filesystem\DirectoryList;
use \Magento\Store\Model\StoreManagerInterface;

class getCarriersLogos extends \Magento\Framework\App\Action\Action
{

    /**
     * @var JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var Config
     */
    protected $_shipconfig;

    /**
     * @var Repository
     */
    protected $_assetRepo;

    /**
     * @var DirectoryList
     */
    protected $_directoryList;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * getCarriersLogos constructor.
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Config $shipConfig
     * @param Repository $repository
     * @param DirectoryList $directoryList
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Config $shipConfig,
        Repository $repository,
        DirectoryList $directoryList,
        StoreManagerInterface $storeManager
    )
    {
        parent::__construct($context);
        $this->_resultJsonFactory = $jsonFactory;
        $this->_shipconfig = $shipConfig;
        $this->_assetRepo = $repository;
        $this->_directoryList = $directoryList;
        $this->_storeManager = $storeManager;
    }

    /**
     * recuperation des logos des modes de livraison chronopost
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $paramsImg = array('_secure' => $this->getRequest()->isSecure());
        $logos = array();
        $activeCarriers = $this->_shipconfig->getActiveCarriers();
        foreach($activeCarriers as $carrierCode => $carrierModel)
        {
            if($carrierMethods = $carrierModel->getAllowedMethods() )
            {
                foreach ($carrierMethods as $methodCode => $method)
                {
                    if(!$carrierModel->getIsChronoMethod()) { /* methode NON chronopost  $methodCode != 'chronopost' */
                        continue;
                    }
                    if($carrierModel->getConfigData('logo_url')) {

                        /* verification si image pas surchargé par client dans dossier pub/media/chronorelais */
                        $logoMediaPath = $this->_directoryList->getPath("media")."/chronopost/".$carrierModel->getConfigData('logo_url');
                        if(file_exists($logoMediaPath)) {
                            $currentStore = $this->_storeManager->getStore();
                            $logos[$carrierCode] = $currentStore->getBaseurl('media')."/chronopost/".$carrierModel->getConfigData('logo_url');
                        } else {
                            $logos[$carrierCode] = $this->_assetRepo->getUrlWithParams('Chronopost_Chronorelais::images/'.$carrierModel->getConfigData('logo_url'), $paramsImg);
                        }
                    }
                }
            }
        }
        $result = $this->_resultJsonFactory->create();
        $result->setData($logos);
        return $result;
    }
}
