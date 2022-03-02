<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Chronopost\Chronorelais\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class LivraisonSamedi
 */
class ChooseContract extends Column
{

    protected $_scopeConfig;
    protected $helper;
    protected $helperWS;

    /**
     * LivraisonSamedi constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        ScopeConfigInterface $scopeConfig,
        \Chronopost\Chronorelais\Helper\Data $helper,
        \Chronopost\Chronorelais\Helper\Webservice $helperWS,
        array $components = [],
        array $data = []
    )
    {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->_scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->helperWS = $helperWS;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {

                if (isset($item["shipment_id"])) {

                    $entity_id = $item["entity_id"];
                    $contract = $this->helper->getContractByOrderId($entity_id);

                    if (!$contract) {

                        $render = "<select data-entityid='" . $entity_id . "' id='contract-" . $entity_id . "'>";
                        $contracts = $this->helper->getConfigContracts();
                        foreach ($contracts as $key => $contract) {

                            $shippingMethod = $item['shipping_method'];
                            $shippingMethodCode = explode("_", $shippingMethod);
                            $shippingMethodCode = isset($shippingMethodCode[1]) ? $shippingMethodCode[1] : $shippingMethodCode[0];
                            if(!$this->helperWS->shippingMethodEnabled($shippingMethodCode, $key)){
                                continue;
                            }

                            $shippingMethod = $item['shipping_method'];
                            $shippingMethodCode = explode("_", $shippingMethod);
                            $shippingMethodCode = isset($shippingMethodCode[1]) ? $shippingMethodCode[1] : $shippingMethodCode[0];
                            $defaultContract = $this->helper->getCarrierContract($shippingMethodCode);
                            $selected = ($key === $defaultContract['numContract'])?'selected':'';
                            $render .= "<option value='" . $key . "' ". $selected ." >" . $contract['name'] . "</option>";
                        }
                        $render .= "<select>";

                    } else {

                        $render = $contract->getData('contract_name');

                    }

                    $item[$this->getData('name')] = $render;

                }

            }
        }

        return $dataSource;
    }
}
