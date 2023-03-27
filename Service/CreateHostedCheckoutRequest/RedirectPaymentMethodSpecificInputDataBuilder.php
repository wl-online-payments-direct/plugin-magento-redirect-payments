<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Service\CreateHostedCheckoutRequest;

use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInputFactory;
use Worldline\RedirectPayment\Gateway\Config\Config;
use Worldline\RedirectPayment\Ui\ConfigProvider;
use Worldline\RedirectPayment\WebApi\RedirectManagement;

class RedirectPaymentMethodSpecificInputDataBuilder
{
    public const RP_METHOD_SPECIFIC_INPUT = 'redirect_payment_method_specific_input';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var RedirectPaymentMethodSpecificInputFactory
     */
    private $redirectPaymentMethodSpecificInputFactory;

    public function __construct(
        Config $config,
        ManagerInterface $eventManager,
        RedirectPaymentMethodSpecificInputFactory $redirectPaymentMethodSpecificInputFactory
    ) {
        $this->config = $config;
        $this->eventManager = $eventManager;
        $this->redirectPaymentMethodSpecificInputFactory = $redirectPaymentMethodSpecificInputFactory;
    }

    public function build(CartInterface $quote): RedirectPaymentMethodSpecificInput
    {
        /** @var RedirectPaymentMethodSpecificInput $redirectPaymentMethodSpecificInput */
        $redirectPaymentMethodSpecificInput = $this->redirectPaymentMethodSpecificInputFactory->create();
        $authMode = $this->config->getAuthorizationMode();
        $redirectPaymentMethodSpecificInput->setRequiresApproval($authMode !== Config::AUTHORIZATION_MODE_SALE);
        $redirectPaymentMethodSpecificInput->setPaymentOption('W3999');
        $payProductId = $quote->getPayment()->getAdditionalInformation(RedirectManagement::PAYMENT_PRODUCT_ID);
        if ($payProductId) {
            $redirectPaymentMethodSpecificInput->setPaymentProductId((int)$payProductId);
        }

        $args = ['quote' => $quote, self::RP_METHOD_SPECIFIC_INPUT => $redirectPaymentMethodSpecificInput];
        $this->eventManager->dispatch(ConfigProvider::CODE . '_redirect_payment_method_specific_input_builder', $args);

        return $redirectPaymentMethodSpecificInput;
    }
}
