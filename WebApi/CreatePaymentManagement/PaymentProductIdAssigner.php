<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\WebApi\CreatePaymentManagement;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\PaymentInterface;
use Worldline\PaymentCore\Model\DataAssigner\DataAssignerInterface;

class PaymentProductIdAssigner implements DataAssignerInterface
{
    public const PAYMENT_PRODUCT_ID = 'selected_payment_product';

    /**
     * Assign payment product id
     *
     * @param PaymentInterface $payment
     * @param array $additionalInformation
     * @return void
     * @throws LocalizedException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function assign(PaymentInterface $payment, array $additionalInformation): void
    {
        $payment->setAdditionalInformation(self::PAYMENT_PRODUCT_ID, $this->extractProductId($payment->getMethod()));
    }

    private function extractProductId(string $paymentMethod): int
    {
        $offset = strrpos($paymentMethod, '_') + 1;
        return (int)substr($paymentMethod, $offset);
    }
}
