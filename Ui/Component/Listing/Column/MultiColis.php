<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Chronopost\Chronorelais\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Store\Model\StoreManagerInterface;


use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class LivraisonSamedi
 */
class MultiColis extends Column
{

    protected $_scopeConfig;
    protected $helper;
    protected $urlBuilder;
    protected $_storeManager;
    protected $_formKey;

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
        UrlInterface $urlBuilder,
        StoreManagerInterface $_storeManager,
        FormKey $formKey,
        array $components = [],
        array $data = []
    )
    {

        $this->urlBuilder = $urlBuilder;
        $this->_storeManager = $_storeManager;
        $this->_formKey = $formKey;
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->_scopeConfig = $scopeConfig;
        $this->helper = $helper;
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
                    $contractNum = false;
                    if (!$contract) {
                        $shippingMethod = $item['shipping_method'];
                        $shippingMethodCode = explode("_", $shippingMethod);
                        $shippingMethodCode = isset($shippingMethodCode[1]) ? $shippingMethodCode[1] : $shippingMethodCode[0];
                        $contract = $this->helper->getCarrierContract($shippingMethodCode);
                        $contractNum = $contract['numContract'];
                    }


                    $viewUrlPath = $this->getData('config/viewUrlPathGenerate') ?: '#';

                    $urlEntityParamName = $item["shipment_id"] == '--' ? 'order_id' : 'shipment_increment_id';

                    if ($item["shipment_id"] == '--') {
                        $indexFieldValues = array($item['entity_id']);
                    } else {
                        $indexFieldValues = explode(',', $item['shipment_id']);
                    }

                    $item[$this->getData('name')] = '';
                    $url = $this->urlBuilder->getUrl(
                        $viewUrlPath
                    );
                    $totalWeight = $this->helper->getWeightOfOrder($item['entity_id']);
                    $dimensions = '{"0":{"weight":"' . $totalWeight . '","width":"1","height":"1","length":"1"}}';
                    $render = '<form class="form_' . $item['entity_id'] . '" id="form_' . $item['entity_id'] . '" action="' . $url . '" method="post">';
                    $render .= '<input type="hidden" id="order_dimensions" class="dimensions container" name="dimensions" value=' . $dimensions . ' />';
                    $render .= '<input type="hidden" name="order_id" value="' . $item['entity_id'] . '" />';
                    $render .= '<input name="form_key" type="hidden" value="' . $this->_formKey->getFormKey() . '" />';
                    $render .= "<input type='hidden' value='" . $contractNum . "' name='contract'/>";
                    $render .= "<input style='margin-bottom:5px;width:100%;text-align:center;' data-orderid='" . $item['entity_id'] . "' type='text' name='nb_colis' value='1'/>";


                    if (count($indexFieldValues) === 1) {
                        $render .= '<input name="shipment_id" type="hidden" value="' . $item['shipment_id'] . '" />';
                    } else {

                        $render .= '<select style="margin-bottom:5px;text-align:center;" name="shipment_id" required >';
                        $render .= '<option value="">' . __("Select a shipment") . '</option>';

                        foreach ($indexFieldValues as $indexFieldValue) {
                            $render .= '<option value="' . $indexFieldValue . '">' . $indexFieldValue . '</option>';
                        }

                        $render .= '</select>';

                    }
                    $render .= "<button style='width: 100%;' type='submit'>" . __('Generated') . "</button>";
                    $render .= '</form>';
                    $item[$this->getData('name')] = $render;

                }


            }
        }

        return $dataSource;
    }
}
