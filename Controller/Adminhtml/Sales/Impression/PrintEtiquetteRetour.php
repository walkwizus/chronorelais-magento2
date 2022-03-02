<?php
namespace Chronopost\Chronorelais\Controller\Adminhtml\Sales\Impression;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\App\Filesystem\DirectoryList;

use Magento\Sales\Model\OrderFactory as OrderFactory;

use Chronopost\Chronorelais\Helper\Data as HelperData;
use Chronopost\Chronorelais\Helper\Shipment as HelperShipment;
use Chronopost\Chronorelais\Helper\Webservice as HelperWebservice;

class PrintEtiquetteRetour extends AbstractImpression
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var HelperShipment
     */
    protected $_helperShipment;

    /**
     * @var HelperWebservice
     */
    protected $_helperWebservice;

    /**
     * PrintEtiquetteRetour constructor.
     * @param Context $context
     * @param DirectoryList $directoryList
     * @param PageFactory $resultPageFactory
     * @param OrderFactory $orderFactory
     * @param HelperData $helperData
     * @param HelperShipment $helperShipment
     * @param HelperWebservice $helperWebservice
     */
    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        PageFactory $resultPageFactory,
        OrderFactory $orderFactory,
        HelperData $helperData,
        HelperShipment $helperShipment,
        HelperWebservice $helperWebservice
    ) {
        parent::__construct($context,$directoryList,$resultPageFactory,$helperData);
        $this->_helperShipment = $helperShipment;
        $this->_orderFactory = $orderFactory;
        $this->_helperWebservice = $helperWebservice;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $shipmentIncrementId = $this->getRequest()->getParam('shipment_increment_id');
        $_shipment = $this->_helperShipment->getShipmentByIncrementId($shipmentIncrementId);

        $_order = $_shipment->getOrder();
        $_shippingAddress = $_shipment->getShippingAddress();
        $_billingAddress = $_shipment->getBillingAddress();
        $_countryId = $_shippingAddress->getCountryId();

        if(!$this->_helperData->returnAuthorized($_countryId)) {
            $this->messageManager->addErrorMessage(__("Les étiquettes de retour ne sont pas disponibles pour ce pays : " . $_countryId));
            $resultRedirect->setPath("chronopost_chronorelais/sales/impression");
            return $resultRedirect;
        }

        try {
            $etiquette = $this->_helperWebservice->createEtiquette($_shipment,'retour',$this->getRequest()->getParam('recipient_address_type'));

            if($etiquette) {
                $path = $this->savePdfWithContent($etiquette->return->pdfEtiquette, $_shipment->getId());

                // send mail with pdf
                $message_email = __("Hello, <br />You will soon be using Chronopost to send an item. The person who sent you this email has already prepared the waybill that you will use. Once it has been printed, put the waybill into an adhesive pouch and affix it to your shipment. Make sure the barcode is clearly visible.<br />Kind regards,");

                $customer_email = ($_shippingAddress->getEmail()) ? $_shippingAddress->getEmail() : ($_billingAddress->getEmail() ? $_billingAddress->getEmail() : $_order->getCustomerEmail());

                $mail = new \Zend_Mail('utf-8');
                $mail->setType('multipart/alternative');
                $mail->setBodyHtml($message_email);
                $recipientEmail = $this->_helperData->getConfig("contact/email/recipient_email");
                $mail->setFrom($recipientEmail);
                $mail->setSubject($_order->getStoreName() . ' : Etiquette de retour chronopost');
                $mail->createAttachment(file_get_contents($path), \Zend_Mime::TYPE_OCTETSTREAM,
                    \Zend_Mime::DISPOSITION_ATTACHMENT, \Zend_Mime::ENCODING_BASE64, 'etiquette_retour.pdf');

                /* mail au client */
                $mail->addTo($customer_email);
                $mail->send();

                $mail->clearRecipients();

                /* copie à l'admin */
                $mail->addTo($recipientEmail);
                $mail->send();

                $this->messageManager->addSuccessMessage(__("The return label has been sent to the customer."));
            }
        } catch(\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }

        $resultRedirect->setPath("chronopost_chronorelais/sales/impression");
        return $resultRedirect;
    }

    protected function savePdfWithContent($content, $shipmentId)
    {
        $this->createMediaChronopostFolder();
        $path = $this->_directoryList->getPath('media').'/chronopost';
        $path = $path.'/etiquetteRetour-' . $shipmentId . '.pdf';
        file_put_contents($path, $content);

        return $path;
    }

}