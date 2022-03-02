<?php
namespace Chronopost\Chronorelais\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

use Magento\Sales\Model\OrderFactory;
use Chronopost\Chronorelais\Model\Config\Source\Retour;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class RetourAction
 */
class RetourAction extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var Retour
     */
    protected $_retourSource;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * RetourAction constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param OrderFactory $orderFactory
     * @param Retour $retour
     * @param ScopeConfigInterface $scope
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        OrderFactory $orderFactory,
        Retour $retour,
        ScopeConfigInterface $scope,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->_orderFactory = $orderFactory;
        $this->_retourSource = $retour;
        $this->_scopeConfig = $scope;
        $data['config']['label'] = $this->getLabelWithDropdown($data['config']['label']);
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    protected function getLabelWithDropdown($label) {

        $defaultAddress = $this->_scopeConfig->getValue("chronorelais/retour/defaultadress");
        $select = "<br /><select id='etiquette_retour_adresse' name='etiquette_retour_adresse'>";
            $options = $this->_retourSource->toOptionArray();
            foreach($options as $value => $option) {
                $selected = ($defaultAddress && $value == $defaultAddress) ? ' selected="selected"' : '';
                $select .= "<option value='".$value."'".$selected.">".$option."</option>";
            }
        $select .= "</select><input type='hidden' id='etiquette_retour_adresse_value' name='etiquette_retour_adresse_value' value='".$defaultAddress."'/>";

        return $label.$select;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        //$this->setData("label",$this->getLabel()."fefeezfzefzef");
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                /* si pas d'expedition : pas de retour possible */
                if(!isset($item["shipment_id"]) || empty($item["shipment_id"]) || $item["shipment_id"] == '--'
                || !isset($item["track_number"]) || empty($item["track_number"]) || $item["track_number"] == '--') {
                    $item[$this->getData('name')] = '';
                    continue;
                }

                if (isset($item["shipment_id"])) {
                    $viewUrlPath = $this->getData('config/viewUrlPath') ?: '#';

                    $urlEntityParamName = $item["shipment_id"] == '--' ? 'order_id' : 'shipment_increment_id';

                    if($item["shipment_id"] == '--') {
                        $indexFieldValues = array($item['entity_id']);
                    } else {
                        $indexFieldValues = explode(',',$item['shipment_id']);
                    }

                    $item[$this->getData('name')] = '';
                    foreach($indexFieldValues as $indexFieldValue) {

                        $url = $this->urlBuilder->getUrl(
                            $viewUrlPath,
                            [
                                $urlEntityParamName => trim($indexFieldValue)
                            ]
                        );

                        if(count($indexFieldValues) === 1) {
                            $item[$this->getData('name')] = '<a href="'.$url.'" class="etiquette_retour_link">'.__('After-sales return').'</a>';
                        } else {
                            $item[$this->getData('name')] .= '<a href="'.$url.'" class="etiquette_retour_link">'.__('After-sales return').' '.trim($indexFieldValue).'</a><br />';
                        }

                    }
                }
            }
        }

        return $dataSource;
    }
}
