<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Api;

interface RedirectManagementInterface
{
    /**
     * @param int $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return string redirect url
     */
    public function processRedirectRequest(
        int $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ): string;

    /**
     * @param string $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param string $email
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return string redirect url
     */
    public function processGuestRedirectRequest(
        string $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        string $email,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ): string;
}
