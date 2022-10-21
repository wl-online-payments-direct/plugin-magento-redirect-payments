<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\GraphQl\Plugin\QuoteGraphQl\Model\Resolver\SelectedPaymentMethod;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Resolver\SelectedPaymentMethod;
use Worldline\PaymentCore\Model\Ui\PaymentProductsProvider;
use Worldline\RedirectPayment\UI\ConfigProvider;
use Worldline\RedirectPayment\WebApi\RedirectManagement;

class ChangePaymentMethodTitle
{
    /**
     * @param SelectedPaymentMethod $subject
     * @param array $result
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterResolve(
        SelectedPaymentMethod $subject,
        array $result,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var \Magento\Quote\Model\Quote $cart */
        $cart = $value['model'];

        $payment = $cart->getPayment();
        if (!$payment) {
            return [];
        }

        if (strpos($payment->getMethod() ?? '', ConfigProvider::CODE) !== false
            && $payment->getAdditionalInformation(RedirectManagement::PAYMENT_PRODUCT_ID)
        ) {
            $payProductId = $payment->getAdditionalInformation(RedirectManagement::PAYMENT_PRODUCT_ID);
            $result['title'] = PaymentProductsProvider::PAYMENT_PRODUCTS[$payProductId]['label'];
        }

        return $result;
    }
}
