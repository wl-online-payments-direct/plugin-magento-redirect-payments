<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Service\Creator\Request;

use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInputFactory;
use Worldline\RedirectPayment\Gateway\Config\Config;
use Worldline\RedirectPayment\UI\ConfigProvider;
use Worldline\RedirectPayment\WebApi\RedirectManagement;

class CardPaymentMethodSpecificInputDataBuilder
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var CardPaymentMethodSpecificInputFactory
     */
    private $cardPaymentMethodSpecificInputFactory;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    public function __construct(
        Config $config,
        CardPaymentMethodSpecificInputFactory $cardPaymentMethodSpecificInputFactory,
        PaymentTokenManagementInterface $paymentTokenManagement,
        ManagerInterface $eventManager
    ) {
        $this->config = $config;
        $this->cardPaymentMethodSpecificInputFactory = $cardPaymentMethodSpecificInputFactory;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->eventManager = $eventManager;
    }

    public function build(CartInterface $quote): CardPaymentMethodSpecificInput
    {
        /** @var CardPaymentMethodSpecificInput $cardPaymentMethodSpecificInput */
        $cardPaymentMethodSpecificInput = $this->cardPaymentMethodSpecificInputFactory->create();
        $cardPaymentMethodSpecificInput->setAuthorizationMode($this->config->getAuthorizationMode());
        $payProductId = $quote->getPayment()->getAdditionalInformation(RedirectManagement::PAYMENT_PRODUCT_ID);
        if ($payProductId) {
            $cardPaymentMethodSpecificInput->setPaymentProductId((int)$payProductId);
        }

        if ($token = $this->getToken($quote)) {
            $cardPaymentMethodSpecificInput->setToken($token);
        }

        $args = ['quote' => $quote, 'card_payment_method_specific_input' => $cardPaymentMethodSpecificInput];
        $this->eventManager->dispatch(ConfigProvider::CODE . '_card_payment_method_specific_input_builder', $args);

        return $cardPaymentMethodSpecificInput;
    }

    private function getToken(CartInterface $quote): ?string
    {
        $payment = $quote->getPayment();
        if (!$publicHash = $payment->getAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH)) {
            return null;
        }

        $token = $this->paymentTokenManagement->getByPublicHash($publicHash, (int) $quote->getCustomerId());
        return $token instanceof PaymentTokenInterface ? $token->getGatewayToken() : null;
    }
}
