<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Service\CreateHostedCheckoutRequest;

use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\MobilePaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\MobilePaymentMethodSpecificInputFactory;
use Worldline\RedirectPayment\Gateway\Config\Config;
use Worldline\RedirectPayment\WebApi\RedirectManagement;

class MobilePaymentMethodSpecificInputDataBuilder
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var MobilePaymentMethodSpecificInputFactory
     */
    private $mobilePaymentMethodSpecificInputFactory;

    public function __construct(
        Config $config,
        MobilePaymentMethodSpecificInputFactory $mobilePaymentMethodSpecificInputFactory
    ) {
        $this->config = $config;
        $this->mobilePaymentMethodSpecificInputFactory = $mobilePaymentMethodSpecificInputFactory;
    }

    public function build(CartInterface $quote): MobilePaymentMethodSpecificInput
    {
        /** @var MobilePaymentMethodSpecificInput $mobilePaymentMethodSpecificInputFactory */
        $mobilePaymentMethodSpecificInput = $this->mobilePaymentMethodSpecificInputFactory->create();
        $mobilePaymentMethodSpecificInput->setAuthorizationMode($this->config->getAuthorizationMode());
        $payProductId = $quote->getPayment()->getAdditionalInformation(RedirectManagement::PAYMENT_PRODUCT_ID);
        if ($payProductId) {
            $mobilePaymentMethodSpecificInput->setPaymentProductId((int)$payProductId);
        }

        return $mobilePaymentMethodSpecificInput;
    }
}
