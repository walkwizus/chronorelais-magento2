<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Chronopost\Chronorelais\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\Data\Form\FormKey;
use Magento\Store\Model\StoreManagerInterface;
use Chronopost\Chronorelais\Helper\Data;

/**
 * Class ViewAction
 */
class PrintAction extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
    protected $_storeManager;
    protected $_formKey;
    protected $_helper;
    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param StoreManagerInterface $_storeManager
     * @param FormKey $formKey
     * @param Data $helper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        StoreManagerInterface $_storeManager,
        FormKey $formKey,
        Data $helper,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->_storeManager = $_storeManager;
        $this->_formKey = $formKey;
        $this->_helper = $helper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
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
                    $viewUrlPath = $this->getData('config/viewUrlPath') ?: '#';


                    if($item["shipment_id"] == '--') {
                        $shipmentIds = array();
                    } else {
                        $shipmentIds = explode(',',$item['shipment_id']);
                    }

                    if($item["track_number"] == '--') {
                        $tracknumbers = array();
                    } else {
                        $tracknumbers = explode(',',$item['track_number']);
                    }

                    //If no tracking number, label canceled or never genereted
                    if(!count($tracknumbers)){
                        continue;
                    }

                    $item[$this->getData('name')] = '';


                    $totalWeight = $this->_helper->getWeightOfOrder($item['entity_id']);
                    $dimensions =  '{"0":{"weight":"' . $totalWeight . '","width":"1","height":"1","length":"1"}}';

                    if(count($shipmentIds) >= count($tracknumbers) || $this->_helper->gsIsActive()){
                        $urlEntityParamName = $item["shipment_id"] == '--' ? 'order_id' : 'shipment_id';
                        $indexFieldValues = $shipmentIds;
                    }else{



                        $urlEntityParamName = 'track_number';
                        $indexFieldValues = $tracknumbers;

                    }

                    foreach($indexFieldValues as $indexFieldValue) {

                        $url = $this->urlBuilder->getUrl(
                            $viewUrlPath,
                            [
                                $urlEntityParamName => trim($indexFieldValue ?? '')
                            ]
                        );

                        if(count($indexFieldValues) === 0) {
                            $render = '';
                        } else {
                            $render = '<a class="printlink" href="'. $url .'">'.trim($indexFieldValue ?? '').'</a><br />';
                        }
                        $item[$this->getData('name')] .=  $render;
                    }
                }
            }
        }

        return $dataSource;
    }
}
