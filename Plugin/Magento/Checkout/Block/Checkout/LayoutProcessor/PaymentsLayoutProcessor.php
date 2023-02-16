<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Plugin\Magento\Checkout\Block\Checkout\LayoutProcessor;

use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;

class PaymentsLayoutProcessor
{
    /**
     * Add billing address to every payment product
     *
     * @param LayoutProcessor $processor
     * @param array $jsLayout
     * @return array[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeProcess(LayoutProcessor $processor, array $jsLayout): array
    {
        $layoutRoot = &$jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
        ['children']['payment']['children']['renders']['children']['worldline-redirect-payment-payment'];

        foreach (array_keys(PaymentProductsDetailsInterface::PAYMENT_PRODUCTS) as $paymentId) {
            $layoutRoot['methods']['worldline_redirect_payment_' . $paymentId]['isBillingAddressRequired'] = true;
            $layoutRoot['methods']['worldline_redirect_payment_' . $paymentId . '_vault']['isBillingAddressRequired']
                = true;
        }

        return [$jsLayout];
    }
}
