<?php
namespace Chronopost\Chronorelais\Controller\Adminhtml\Sales\Export;

use Chronopost\Chronorelais\Controller\Adminhtml\Sales\Impression\AbstractImpression;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

use Chronopost\Chronorelais\Helper\Data as HelperData;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Shipping\Model\CarrierFactory;
use \Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Ui\Component\MassAction\Filter;
class Export extends AbstractImpression
{
    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var CarrierFactory
     */
    protected $_carrierFactory;

    /**
     * @var Filter
     */
    protected $_filter;

    /**
     * Export constructor.
     * @param Context $context
     * @param DirectoryList $directoryList
     * @param PageFactory $resultPageFactory
     * @param HelperData $helperData
     * @param CollectionFactory $collectionFactory
     * @param CarrierFactory $carrierFactory
     * @param Filter $filter
     */
    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        PageFactory $resultPageFactory,
        HelperData $helperData,
        CollectionFactory $collectionFactory,
        CarrierFactory $carrierFactory,
        Filter $filter
    ) {
        parent::__construct($context,$directoryList,$resultPageFactory,$helperData);
        $this->_collectionFactory = $collectionFactory;
        $this->_carrierFactory = $carrierFactory;
        $this->_filter = $filter;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Chronopost_Chronorelais::sales');
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $format = $this->getRequest()->getParam('format');

            $collection = $this->_filter->getCollection($this->_collectionFactory->create());
            $this->export($format, $collection);

        } catch(\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
            $resultRedirect->setPath("chronopost_chronorelais/sales/export");
            return $resultRedirect;
        }
    }

    public function export($format = 'css', $collection) {
        /**
         * Get configuration
         */
        $separator = $this->_helperData->getConfig("chronorelais/export_" . $format . "/field_separator");
        $delimiter = $this->_helperData->getConfig("chronorelais/export_" . $format . "/field_delimiter");

        if ($delimiter == 'simple_quote') {
            $delimiter = "'";
        } else if ($delimiter == 'double_quotes') {
            $delimiter = '"';
        } else {
            $delimiter = '';
        }
        $lineBreak = $this->_helperData->getConfig("chronorelais/export_" . $format . "/endofline_character");
        if ($lineBreak == 'lf') {
            $lineBreak = "\n";
        } else if ($lineBreak == 'cr') {
            $lineBreak = "\r";
        } else if ($lineBreak == 'crlf') {
            $lineBreak = "\r\n";
        }
        $fileExtension = $this->_helperData->getConfig("chronorelais/export_" . $format . "/file_extension");
        $fileCharset = $this->_helperData->getConfig("chronorelais/export_" . $format . "/file_charset");

        /* set the filename */
        $filename = 'orders_export'.$format.'_' . date('Ymd_His') . $fileExtension;

        /* initialize the content variable */
        $content = '';
        $weightUnit = $this->_helperData->getConfig("chronorelais/weightunit/unit");
        foreach ($collection->getItems() as $order) {

            /* get the order */
            $address = $order->getShippingAddress();
            $billingAddress = $order->getBillingAddress();

            $_shippingMethod = explode('_', $order->getShippingMethod());
            $_shippingMethod = $_shippingMethod[1];
            $carrier = $this->_carrierFactory->get($_shippingMethod);

            /* customer id */
            $content = $this->_addFieldToCsv($content, $delimiter, ($order->getCustomerId() ? $order->getCustomerId() : $address->getLastname()));
            $content .= $separator;
            /* Nom du point relais OU société si livraison à domicile */
            $content = $this->_addFieldToCsv($content, $delimiter, $address->getCompany());
            $content .= $separator;
            /* customer name */
            $content = $this->_addFieldToCsv($content, $delimiter, ($address->getFirstname() ? $address->getFirstname() : $billingAddress->getFirstname()));
            $content .= $separator;
            $content = $this->_addFieldToCsv($content, $delimiter, ($address->getLastname() ? $address->getLastname() : $billingAddress->getLastname()));
            $content .= $separator;
            /* street address */
            $content = $this->_addFieldToCsv($content, $delimiter, $this->getValue($address->getStreetLine(1)));
            $content .= $separator;
            $content = $this->_addFieldToCsv($content, $delimiter, $this->getValue($address->getStreetLine(2)));
            $content .= $separator;

            /* digicode (vide)*/
            $content = $this->_addFieldToCsv($content, $delimiter, '');
            $content .= $separator;

            /* postal code */
            $content = $this->_addFieldToCsv($content, $delimiter, $this->getValue($address->getPostcode()));
            $content .= $separator;
            /* city */
            $content = $this->_addFieldToCsv($content, $delimiter, $this->getValue($address->getCity()));
            $content .= $separator;
            /* country code */
            $content = $this->_addFieldToCsv($content, $delimiter, $this->getValue($address->getCountryId()));
            $content .= $separator;
            /* telephone */
            $telephone = trim(preg_replace("[^0-9.-]", " ", $address->getTelephone()));
            $telephone = (strlen($telephone) >= 10 ? $telephone : '');
            $content = $this->_addFieldToCsv($content, $delimiter, $telephone);
            $content .= $separator;
            /* email */
            $customer_email = ($address->getEmail()) ? $address->getEmail() : ($billingAddress->getEmail() ? $billingAddress->getEmail() : $order->getCustomerEmail());
            $content = $this->_addFieldToCsv($content, $delimiter, $this->getValue($customer_email));
            $content .= $separator;
            /* real order id */
            $content = $this->_addFieldToCsv($content, $delimiter, $this->getValue($order->getRealOrderId()));
            $content .= $separator;

            /* code barre client (vide) */
            $content = $this->_addFieldToCsv($content, $delimiter, '');
            $content .= $separator;

            /* productCode */
            $productCode = $carrier->getChronoProductCodeStr();
            $content = $this->_addFieldToCsv($content, $delimiter, $productCode);
            $content .= $separator;

            /* compte (vide) */
            $content = $this->_addFieldToCsv($content, $delimiter, '');
            $content .= $separator;

            /* sous compte (vide) */
            $content = $this->_addFieldToCsv($content, $delimiter, '');
            $content .= $separator;

            /* empty fields */
            $content = $this->_addFieldToCsv($content, $delimiter, 0);
            $content .= $separator;
            $content = $this->_addFieldToCsv($content, $delimiter, 0);
            $content .= $separator;

            /* document / marchandise (vide) */
            $content = $this->_addFieldToCsv($content, $delimiter, '');
            $content .= $separator;

            /* description contenu (vide) */
            $content = $this->_addFieldToCsv($content, $delimiter, '');
            $content .= $separator;

            /* Livraison Samedi */
            $SaturdayShipping = 'L'; //default value for the saturday shipping
            if ($carrier->canDeliverOnSaturday()) {
                if (!$_deliver_on_saturday = $this->_helperData->getLivraisonSamediStatus($order->getId())) {
                    $_deliver_on_saturday = $this->_helperData->getConfig('carriers/' . $carrier->getCarrierCode() . '/deliver_on_saturday');
                } else {
                    if ($_deliver_on_saturday == 'Yes') {
                        $_deliver_on_saturday = 1;
                    } else {
                        $_deliver_on_saturday = 0;
                    }
                }
                $is_sending_day = $this->_helperData->isSendingDay();
                if ($_deliver_on_saturday && $is_sending_day == true) {
                    $SaturdayShipping = 'S';
                } elseif (!$_deliver_on_saturday && $is_sending_day == true) {
                    $SaturdayShipping = 'L';
                }
            }
            $content = $this->_addFieldToCsv($content, $delimiter, $SaturdayShipping);
            $content .= $separator;

            /* chronorelay point */
            $content = $this->_addFieldToCsv($content, $delimiter, $this->getValue($order->getRelaisId()));
            $content .= $separator;

            /* total weight (in kg) */
            $order_weight = number_format($order->getWeight(), 2, '.', '');
            if($weightUnit == 'g') {
                $order_weight = $order_weight / 1000;
            }
            $content = $this->_addFieldToCsv($content, $delimiter, $order_weight);
            $content .= $separator;


            /* largeur (vide) */
            $content = $this->_addFieldToCsv($content, $delimiter, '');
            $content .= $separator;

            /* longueur (vide) */
            $content = $this->_addFieldToCsv($content, $delimiter, '');
            $content .= $separator;

            /* hauteur (vide) */
            $content = $this->_addFieldToCsv($content, $delimiter, '');
            $content .= $separator;

            /* avertir destinataire (vide) */
            $content = $this->_addFieldToCsv($content, $delimiter, '');
            $content .= $separator;

            /* nb colis (vide) */
            $content = $this->_addFieldToCsv($content, $delimiter, '');
            $content .= $separator;

            /* date envoi */
            $content = $this->_addFieldToCsv($content, $delimiter, date('d/m/Y'));
            $content .= $separator;

            /* a intégrer (vide) */
            $content = $this->_addFieldToCsv($content, $delimiter, '');
            $content .= $separator;

            /* avertir expéditeur (vide) */
            $content = $this->_addFieldToCsv($content, $delimiter, '');
            $content .= $separator;

            /* DLC (vide) */
            $content = $this->_addFieldToCsv($content, $delimiter, '');
            $content .= $separator;

            /* champ specifique rdv */
            $chronopostsrdv_creneaux_info = $order->getData('chronopostsrdv_creneaux_info');
            if($chronopostsrdv_creneaux_info) {
                $chronopostsrdv_creneaux_info = json_decode($chronopostsrdv_creneaux_info,true);
                $_dateRdvStart = new \DateTime($chronopostsrdv_creneaux_info['deliveryDate']);
                $_dateRdvStart->setTime($chronopostsrdv_creneaux_info['startHour'],$chronopostsrdv_creneaux_info['startMinutes']);

                $_dateRdvEnd = new \DateTime($chronopostsrdv_creneaux_info['deliveryDate']);
                $_dateRdvEnd->setTime($chronopostsrdv_creneaux_info['endHour'],$chronopostsrdv_creneaux_info['endMinutes']);

                /* date debut rdv */
                $content = $this->_addFieldToCsv($content, $delimiter, $_dateRdvStart->format("dmyHi"));
                $content .= $separator;

                /* date fin rdv */
                $content = $this->_addFieldToCsv($content, $delimiter, $_dateRdvEnd->format("dmyHi"));
                $content .= $separator;

                /* Niveau tarifaire */
                $content = $this->_addFieldToCsv($content, $delimiter, $chronopostsrdv_creneaux_info['tariffLevel']);
                $content .= $separator;

                /* code service */
                $content = $this->_addFieldToCsv($content, $delimiter, $chronopostsrdv_creneaux_info['serviceCode']);
                $content .= $separator;

            } else {
                $content = $this->_addFieldToCsv($content, $delimiter, '');
                $content .= $separator;

                $content = $this->_addFieldToCsv($content, $delimiter, '');
                $content .= $separator;

                $content = $this->_addFieldToCsv($content, $delimiter, '');
                $content .= $separator;

                /* code service */
                $content = $this->_addFieldToCsv($content, $delimiter, '');
                $content .= $separator;
            }

            $content .= $lineBreak;
        }

        /* decode the content, depending on the charset */
        if ($fileCharset == 'ISO-8859-1') {
            $content = utf8_decode($content);
        }

        /* pick file mime type, depending on the extension */
        switch ($fileExtension) {
            case '.csv':
                $fileMimeType = 'application/csv';
                break;
            case '.chr':
                $fileMimeType = 'application/chr';
                break;
            default:
                $fileMimeType = 'text/plain';
                break;
        }
        /* download the file */
        return $this->prepareDownloadResponse($filename, $content, $fileMimeType . '; charset="' . $fileCharset . '"');
    }

    /**
     * Add a new field to the csv file
     * @param csvContent : the current csv content
     * @param fieldDelimiter : the delimiter character
     * @param fieldContent : the content to add
     * @return : the concatenation of current content and content to add
     */
    private function _addFieldToCsv($csvContent, $fieldDelimiter, $fieldContent) {
        return $csvContent . $fieldDelimiter . $fieldContent . $fieldDelimiter;
    }

    public function getValue($value) {
        return ($value != '' ? $value : '');
    }

}