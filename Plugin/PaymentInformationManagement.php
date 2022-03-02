<?php

namespace Chronopost\Chronorelais\Plugin;

use Magento\Framework\Exception\StateException;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;

class PaymentInformationManagement
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
     * PaymentInformationManagement constructor.
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $cartRepository
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_quoteRepository = $cartRepository;
    }

    public function beforeSavePaymentInformationAndPlaceOrder(
        $subject,
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        $quote = $this->_quoteRepository->getActive($cartId);
        $shippingAddress = $quote->getShippingAddress();

        /* Si mode RDV et pas de session : on affiche erreur */
        if(preg_match('/chronopostsrdv/',$shippingAddress->getShippingMethod(),$matches,PREG_OFFSET_CAPTURE) && !$this->_checkoutSession->getData('chronopostsrdv_creneaux_info')) {
            throw new StateException(__('Please select an appointment date'));
        }
    }
}