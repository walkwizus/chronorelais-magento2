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


class WeightInput extends Column
{

    protected $_scopeConfig;
    protected $helper;

    /**
     * constructor.
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
        array $components = [],
        array $data = []
    )
    {
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

                $entity_id = $item["entity_id"];
                $totalWeight = $this->helper->getWeightOfOrder($entity_id);

                if($this->helper->getWeightUnit() === 'lbs'){

                    $totalWeight = round($totalWeight/2.205, 3);

                }
                $render = '<input style="margin-bottom:5px;width:40px;text-align:center" type="text" name="weight_input" value="' . $totalWeight . '" class="input-text" data-position="1" data-orderid="' . $item['entity_id'] . '" data-shipping-method="'. $item['shipping_method'] . '"/>';

                $item[$this->getData('name')] = $render;
            }
        }
        return $dataSource;
    }
}
