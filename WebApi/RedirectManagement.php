<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\WebApi;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Worldline\PaymentCore\Api\WebApi\Checkout\BaseCreatePaymentManagementInterface;
use Worldline\RedirectPayment\Api\RedirectManagementInterface;

class RedirectManagement implements RedirectManagementInterface
{
    public const PAYMENT_PRODUCT_ID = 'selected_payment_product';

    /**
     * @var BaseCreatePaymentManagementInterface
     */
    private $baseCreatePaymentManagement;

    public function __construct(
        BaseCreatePaymentManagementInterface $baseCreatePaymentManagement
    ) {
        $this->baseCreatePaymentManagement = $baseCreatePaymentManagement;
    }

    /**
     * Retrieve redirect url
     *
     * @param int $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @throws LocalizedException
     *
     * @return string redirect url
     */
    public function createRequest(
        int $cartId,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null
    ): string {
        return $this->baseCreatePaymentManagement->createRequest($cartId, $paymentMethod, $billingAddress);
    }

    /**
     * Retrieve redirect url for quest user
     *
     * @param string $cartId
     * @param PaymentInterface $paymentMethod
     * @param string $email
     * @param AddressInterface|null $billingAddress
     * @throws LocalizedException
     *
     * @return string redirect url
     */
    public function createGuestRequest(
        string $cartId,
        PaymentInterface $paymentMethod,
        string $email,
        ?AddressInterface $billingAddress = null
    ): string {
        return $this->baseCreatePaymentManagement->createGuestRequest($cartId, $paymentMethod, $email, $billingAddress);
    }
}
