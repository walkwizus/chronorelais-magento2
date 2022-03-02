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
class LivraisonSamedi extends Column
{

    protected $_scopeConfig;

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
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->_scopeConfig = $scopeConfig;
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
                $livraisonSamedi = $item[$this->getData('name')];
                if(!in_array(strtolower($livraisonSamedi),array('yes','no'))) {

                    $shippingMethod = explode("_",$livraisonSamedi);
                    $shippingMethod = isset($shippingMethod[1]) ? $shippingMethod[1] : $shippingMethod[0];

                    $deliver_on_saturday = $this->_scopeConfig->getValue("carriers/".$shippingMethod."/deliver_on_saturday");
                    if($deliver_on_saturday === null) {
                        $livraisonSamedi = '--';
                    } elseif($deliver_on_saturday == 1) {
                        $livraisonSamedi = 'Yes';
                    } elseif($deliver_on_saturday == 0) {
                        $livraisonSamedi = 'No';
                    }

                    $item[$this->getData('name')] = $livraisonSamedi;
                }
            }
        }

        return $dataSource;
    }
}
