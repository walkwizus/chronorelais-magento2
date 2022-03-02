<?php

namespace Chronopost\Chronorelais\Plugin;

use Magento\Framework\Exception\StateException;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;

use Chronopost\Chronorelais\Helper\Webservice as HelperWebservice;
class ShippingInformationManagement
{
    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * Quote repository.
     *
     * @var CartRepositoryInterface
     */
    protected $_quoteRepository;

    /**
     * @var HelperWebservice
     */
    protected $_helperWebservice;

    /**
     * ShippingInformationManagement constructor.
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $cartRepository
     * @param HelperWebservice $webservice
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $cartRepository,
        HelperWebservice $webservice
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_quoteRepository = $cartRepository;
        $this->_helperWebservice = $webservice;
    }

    /**
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @throws StateException
     */
    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {

        $methodCode = $addressInformation->getShippingMethodCode();

        $quote = $this->_quoteRepository->getActive($cartId);
        $quote->setRelaisId('');
        $quote->setData('chronopostsrdv_creneaux_info','');

        /* Si mode de livraison relais : vérif si point relais coché */
        if(preg_match('/chronorelais/',$methodCode,$matches,PREG_OFFSET_CAPTURE)) {
            $relaisId = $this->_checkoutSession->getData("chronopost_chronorelais_relais_id");
            if(!$relaisId) {
                throw new StateException(__('Select a pick-up point'));
            }

            $relais = $this->_helperWebservice->getDetailRelaisPoint($relaisId);
            if($relais) {
                $address = $addressInformation->getShippingAddress();
                $address->setCustomerAddressId(0);
                $address->setSaveInAddressBook(0);
                $address->setSameAsBilling(0);
                $address->setCity($relais->localite);
                $address->setPostcode($relais->codePostal);
                $address->setStreet(array($relais->adresse1,$relais->adresse2,$relais->adresse3));
                if(isset($relais->nom) ){
                    $nom = $relais->nom;
                }else{
                    $nom =  $relais->nomEnseigne;
                }
                $address->setCompany($nom);
                $addressInformation->setShippingAddress($address);

                $quote->setShippingAddress($address)->setRelaisId($relaisId);

            } else {
                throw new StateException(__("The pick-up point does not exist."));
            }
        } elseif(preg_match('/chronopostsrdv/',$methodCode,$matches,PREG_OFFSET_CAPTURE)) {
            /* Si mode de livraison RDV : vérif si Horaire selectionné */
            $rdvInfo = $this->_checkoutSession->getData("chronopostsrdv_creneaux_info");
            if(!$rdvInfo) {
                throw new StateException(__('Please select an appointment date'));
            }

            /* verification du creneaux choisi */
            $arrayRdvInfo = json_decode($rdvInfo,true);
            $confirm = $this->_helperWebservice->confirmDeliverySlot($arrayRdvInfo);
            if($confirm->return->code != 0) {
                throw new StateException(__($confirm->return->message));
            }
            $arrayRdvInfo['productCode'] = $confirm->return->productServiceV2->productCode;
            $arrayRdvInfo['serviceCode'] = $confirm->return->productServiceV2->serviceCode;
            if(isset($confirm->return->productServiceV2->asCode))
                $arrayRdvInfo['asCode'] = $confirm->return->productServiceV2->asCode;
            $quote->setData('chronopostsrdv_creneaux_info',json_encode($arrayRdvInfo));
        }

        $this->_quoteRepository->save($quote);
    }
}