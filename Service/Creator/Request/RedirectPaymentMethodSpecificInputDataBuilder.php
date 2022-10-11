<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Service\Creator\Request;

use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInputFactory;
use Worldline\RedirectPayment\Gateway\Config\Config;
use Worldline\RedirectPayment\WebApi\RedirectManagement;

class RedirectPaymentMethodSpecificInputDataBuilder
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var RedirectPaymentMethodSpecificInputFactory
     */
    private $redirectPaymentMethodSpecificInputFactory;

    public function __construct(
        Config $config,
        RedirectPaymentMethodSpecificInputFactory $redirectPaymentMethodSpecificInputFactory
    ) {
        $this->config = $config;
        $this->redirectPaymentMethodSpecificInputFactory = $redirectPaymentMethodSpecificInputFactory;
    }

    public function build(CartInterface $quote): RedirectPaymentMethodSpecificInput
    {
        $redirectPaymentMethodSpecificInput = $this->redirectPaymentMethodSpecificInputFactory->create();
        $authMode = $this->config->getAuthorizationMode();
        $redirectPaymentMethodSpecificInput->setRequiresApproval($authMode !== Config::AUTHORIZATION_MODE_SALE);
        $payProductId = $quote->getPayment()->getAdditionalInformation(RedirectManagement::PAYMENT_PRODUCT_ID);
        if ($payProductId) {
            $redirectPaymentMethodSpecificInput->setPaymentProductId((int)$payProductId);
        }

        return $redirectPaymentMethodSpecificInput;
    }
}
