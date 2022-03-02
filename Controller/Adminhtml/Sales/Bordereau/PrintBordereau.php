<?php
namespace Chronopost\Chronorelais\Controller\Adminhtml\Sales\Bordereau;

use Chronopost\Chronorelais\Controller\Adminhtml\Sales\Impression\AbstractImpression;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

use Chronopost\Chronorelais\Helper\Data as HelperData;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Shipping\Model\CarrierFactory;
use \Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Ui\Component\MassAction\Filter;
use \Chronopost\Chronorelais\Helper\Webservice as HelperWS;
use \Chronopost\Chronorelais\Model\HistoryLtFactory;

class PrintBordereau extends AbstractImpression
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
     * @var HistoryLtFactory
     */
    protected $_historyLtFactory;

    /**
     * @var $_helperWS
     */
    protected $_helperWS;
    /**
     * @var Filter
     */
    protected $_filter;

    /**
     * PrintBordereau constructor.
     * @param Context $context
     * @param DirectoryList $directoryList
     * @param PageFactory $resultPageFactory
     * @param HelperData $helperData
     * @param CollectionFactory $collectionFactory
     * @param CarrierFactory $carrierFactory
     * @param Filter $filter
     * @parm HistoryLtFactory $historyLtFactory
     */
    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        PageFactory $resultPageFactory,
        HelperData $helperData,
        CollectionFactory $collectionFactory,
        CarrierFactory $carrierFactory,
        Filter $filter,
        HelperWS $helperWS,
        HistoryLtFactory $historyLtFactory
    ) {
        parent::__construct($context,$directoryList,$resultPageFactory,$helperData);
        $this->_collectionFactory = $collectionFactory;
        $this->_carrierFactory = $carrierFactory;
        $this->_filter = $filter;
        $this->_helperWS = $helperWS;
        $this->_historyLtFactory = $historyLtFactory;
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

            $weightNational = 0;
            $nbNational = 0;
            $weightInternational = 0;
            $nbInternational = 0;

            /* Shipper */
            $shipper = array(
                'name' => $this->_helperData->getConfig("chronorelais/shipperinformation/name"),
                'address1' => $this->_helperData->getConfig("chronorelais/shipperinformation/address1"),
                'address2' => $this->_helperData->getConfig("chronorelais/shipperinformation/address2"),
                'city' => $this->_helperData->getConfig("chronorelais/shipperinformation/city"),
                'postcode' => $this->_helperData->getConfig("chronorelais/shipperinformation/zipcode"),
                'country' => $this->_helperData->getConfig("chronorelais/shipperinformation/country"),
                'phone' => $this->_helperData->getConfig("chronorelais/shipperinformation/phone")
            );

            $detail = array();

            $collection = $this->_filter->getCollection($this->_collectionFactory->create());
            foreach ($collection->getItems() as $order) {

                $shippingMethod = explode("_", $order->getShippingMethod());
                $shippingMethod = $shippingMethod[1];
                $carrier = $this->_carrierFactory->get($shippingMethod);
                $productCode = 'Chrono '.$carrier->getChronoProductCodeToShipmentStr();

                $shipments = $order->getShipmentsCollection();
                $contract = $this->_helperWS->getContractData($order);

                /** @var \Magento\Sales\Model\Order\Shipment $shipment */
                foreach($shipments as $shipment) {

                    /* Tracking Number */
                    $tracks = $shipment->getAllTracks();

                    foreach($tracks as $track) {
                        $items = $shipment->getAllItems();
                        $weightTotal = 0;
                        $weightTrack = $this->getWeightLt($track->getNumber());

                        $maxAmount = $this->_helperData->getMaxAdValoremAmount();
                        $adValoremAmount = $this->_helperData->getConfig("chronorelais/assurance/amount");
                        $adValoremEnabled = $this->_helperData->getConfig("chronorelais/assurance/enabled");
                        $totalAdValorem = 0;

                        /** @var \Magento\Sales\Model\Order\Shipment\Item $item */
                        foreach($items as $item) {
                            $weightTotal += $item->getWeight() * $item->getQty();
                            $totalAdValorem += $item->getPrice() * $item->getQty();
                        }
                        /* Si montant < au montant minimum ad valorem => pas d'assurance */
                        $totalAdValorem = 0;
                        if($adValoremEnabled)
                        {
                            $totalAdValorem = min($totalAdValorem,$maxAmount);
                            if($totalAdValorem < $adValoremAmount) {
                                $totalAdValorem = 0;
                            }
                        }

                        /* Shipping address */
                        $address =  $shipment->getShippingAddress();
                        if($address->getCountryId() == 'FR') {
                            $weightNational += $weightTotal;
                            $nbNational++;
                        } else {
                            $weightInternational += $weightTotal;
                            $nbInternational++;
                        }

                        array_push($detail, array(
                            'trackNumber' => $track->getNumber(),
                            'numContract' => $contract['number'],
                            'weight' => $weightTotal,
                            'product_code' => $productCode,
                            'postcode' => $address->getPostcode(),
                            'country' => $address->getCountryId(),
                            'assurance' => $totalAdValorem,
                            'city' => substr($address->getCity(), 0, 17),
                            'weightLt' => $weightTrack
                        )
                        );
                    }
                }
            }

            $resume = array(
                'NATIONAL' => array('unite' => $nbNational, 'poids' => $weightNational),
                'INTERNATIONAL' => array('unite' => $nbInternational, 'poids' => $weightInternational),
                'TOTAL' => array('unite' => ($nbNational+$nbInternational), 'poids' => ($weightNational+$weightInternational)),
            );

            /* Create pdf */
            $fileName = 'bordereau.pdf';
            $content = $this->getPdfFile($shipper,$detail,$resume);
            $this->prepareDownloadResponse($fileName, $content);
        } catch(\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
            $resultRedirect->setPath("chronopost_chronorelais/sales/bordereau");
            return $resultRedirect;
        }
    }

    /**
     * @param $shipper
     * @param $detail
     * @param $resume
     * @return string
     */
    protected function getPdfFile($shipper,$detail,$resume) {
        $pdf = new \Zend_Pdf();
        $page = new \Zend_Pdf_Page(\Zend_Pdf_Page::SIZE_A4);

        $weightUnit = $this->_helperData->getConfig("chronorelais/weightunit/unit");

        $minYPosToChangePage = 60;
        $xPos = 20;
        $yPos = $page->getHeight()-20;
        $lineHeight = 15;

        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_TIMES);
        $fontBold = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_TIMES_BOLD);

        /* DATE */
        $page->setFont($font, 11);
        $page->drawText(__('date').' : '.date('d/m/Y'), $page->getWidth()-100, $yPos);
        $yPos -= ($lineHeight);
        $page->setFont($font, 12);

        /* TITRE */
        $page->setFont($fontBold, 12);
        $page->drawText(__('SUMMARY SLIP'), $xPos, $yPos);
        $yPos -= ($lineHeight+20);
        $page->setFont($font, 12);


        /* EMETTEUR */
        $page->setFont($fontBold, 12);
        $page->drawText(__('TRANSMITTER'), $xPos, $yPos);
        $yPos -= ($lineHeight+5);
        $page->setFont($font, 12);

        $page->drawText(__('NAME'), $xPos, $yPos);
        $page->drawText($shipper['name'], $xPos+150, $yPos);
        $yPos -= $lineHeight;

        $page->drawText(__('ADDRESS'), $xPos, $yPos);
        $page->drawText($shipper['address1'], $xPos+150, $yPos);
        $yPos -= $lineHeight;

        $page->drawText(__('ADDITIONAL ADDRESS'), $xPos, $yPos);
        $page->drawText($shipper['address2'], $xPos+150, $yPos);
        $yPos -= $lineHeight;

        $page->drawText(__('TOWN/CITY'), $xPos, $yPos);
        $page->drawText($shipper['city'], $xPos+150, $yPos);
        $yPos -= $lineHeight;

        $page->drawText(__('POSTCODE'), $xPos, $yPos);
        $page->drawText($shipper['postcode'], $xPos+150, $yPos);
        $yPos -= $lineHeight;

        $page->drawText(__('COUNTRY'), $xPos, $yPos);
        $page->drawText($shipper['country'], $xPos+150, $yPos);
        $yPos -= $lineHeight;

        $page->drawText(__('TELEPHONE'), $xPos, $yPos);
        $page->drawText($shipper['phone'], $xPos+150, $yPos);
        $yPos -= $lineHeight;

        $page->drawText(__('ACCOUNTING POST'), $xPos, $yPos);
        $page->drawText(substr($shipper['postcode'],0,2).'999', $xPos+150, $yPos);
        $yPos -= $lineHeight;

        /* DETAIL DES ENVOIS */
        $yPos -= 50;
        $page->setFont($fontBold, 12);
        $page->drawText(__('DETAIL OF SHIPMENTS'), $xPos, $yPos);
        $yPos -= ($lineHeight+5);
        $page->setFont($font, 12);

        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.85, 0.85, 0.85));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle($xPos, $yPos, 570, $yPos -20);
        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0, 0, 0));
        $yPos -= 15;

        $page->drawText(__("TL Number"), $xPos+5, $yPos,'UTF-8');
        $page->drawText('Num contrat', $xPos+103, $yPos, 'UTF-8');
        $page->drawText(__('Weight (kg)'), $xPos+175, $yPos);
        $page->drawText(__('Product Code'), $xPos+233, $yPos);
        $page->drawText(__('Postcode'), $xPos+310, $yPos);
        $page->drawText(__('Country'), $xPos+380, $yPos);
        $page->drawText(__('Insurance'), $xPos+410, $yPos);
        $page->drawText(__('Town/City'), $xPos+470, $yPos);
        $yPos -= 5;

        foreach($detail as $line) {

            $page->setFillColor(new \Zend_Pdf_Color_Rgb(255, 255, 255));
            $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.5));
            $page->setLineWidth(0.5);
            $page->drawRectangle($xPos, $yPos, 570, $yPos -20);
            $page->setFillColor(new \Zend_Pdf_Color_Rgb(0, 0, 0));
            $yPos -= 15;

            $lineWeight = $line['weight'];
            if($weightUnit == 'g') {
                $lineWeight = $lineWeight / 1000;
            }

            $page->drawText($line['trackNumber'], $xPos+5, $yPos,'UTF-8');
            $page->drawText($line['numContract'], $xPos+103, $yPos,'UTF-8');
            $page->drawText($line['weightLt'], $xPos+175, $yPos);
            $page->drawText($line['product_code'], $xPos+233, $yPos);
            $page->drawText($line['postcode'], $xPos+310, $yPos);
            $page->drawText($line['country'], $xPos+380, $yPos);
            $page->drawText($line['assurance'], $xPos+410, $yPos);
            $page->drawText($line['city'], $xPos+470, $yPos,'UTF-8');
            $yPos -= 5;

            if($yPos <= $minYPosToChangePage) {
                $pdf->pages[] = $page;
                $page = new \Zend_Pdf_Page(\Zend_Pdf_Page::SIZE_A4);
                $page->setFont($font, 12);
                $yPos = $page->getHeight()-20;
            }
        }

        /* RESUME */
        $yPos -= 50;
        $page->setFont($fontBold, 12);
        $page->drawText(__('SUMMARY'), $xPos, $yPos);
        $yPos -= ($lineHeight+5);
        $page->setFont($font, 12);

        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.85, 0.85, 0.85));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle($xPos, $yPos, 570, $yPos -20);
        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0, 0, 0));
        $yPos -= 15;

        $page->drawText(__("DESTINATION"), $xPos+5, $yPos,'UTF-8');
        $page->drawText(__('UNIT'), $xPos+170, $yPos);
        $page->drawText(__('TOTAL WEIGHT (kg)'), $xPos+320, $yPos);
        $yPos -= 5;

        foreach($resume as $destination => $line) {

            $page->setFillColor(new \Zend_Pdf_Color_Rgb(255, 255, 255));
            $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.5));
            $page->setLineWidth(0.5);
            $page->drawRectangle($xPos, $yPos, 570, $yPos -20);
            $page->setFillColor(new \Zend_Pdf_Color_Rgb(0, 0, 0));
            $yPos -= 15;

            $lineWeight = $line['poids'];
            if($weightUnit == 'g') {
                $lineWeight = $lineWeight / 1000;
            }

            $page->drawText(__($destination), $xPos+5, $yPos,'UTF-8');
            $page->drawText($line['unite'], $xPos+180, $yPos);
            $page->drawText($lineWeight, $xPos+340, $yPos);
            $yPos -= 5;
        }

        if($yPos <= $minYPosToChangePage) {
            $pdf->pages[] = $page;
            $page = new \Zend_Pdf_Page(\Zend_Pdf_Page::SIZE_A4);
            $yPos = $page->getHeight()-20;
        }

        $yPos -= 50;
        $page->setFont($fontBold, 12);
        $page->drawText(__('Well taken care of %1 package',$resume['TOTAL']['unite']), $xPos, $yPos);

        if($yPos <= $minYPosToChangePage) {
            $pdf->pages[] = $page;
            $page = new \Zend_Pdf_Page(\Zend_Pdf_Page::SIZE_A4);
            $yPos = $page->getHeight()-20;
        }

        /* signatures */
        $yPos -= 60;
        $page->setFont($font, 12);
        $page->drawText(__("Client's signature"), $xPos, $yPos);
        $page->drawText(__('Signature of Chronopost Messenger'), 400, $yPos);

        $pdf->pages[] = $page;
        return $pdf->render();
    }

    protected function getWeightLt($number) {
        $ltHistory = $this->_historyLtFactory->create()->getCollection()
            ->addFieldToFilter('lt_number', $number)
            ->getFirstItem();

        return $ltHistory->getWeight();
    }

}