<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Service\CreateHostedCheckoutRequest;

use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInputFactory;
use OnlinePayments\Sdk\Domain\RedirectionData;
use OnlinePayments\Sdk\Domain\RedirectionDataFactory;
use OnlinePayments\Sdk\Domain\ThreeDSecure;
use OnlinePayments\Sdk\Domain\ThreeDSecureFactory;
use Worldline\RedirectPayment\Gateway\Config\Config;
use Worldline\RedirectPayment\Ui\ConfigProvider;
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
     * @var ThreeDSecureFactory
     */
    private $threeDSecureFactory;

    /**
     * @var RedirectionDataFactory
     */
    private $redirectionDataFactory;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    public function __construct(
        Config $config,
        CardPaymentMethodSpecificInputFactory $cardPaymentMethodSpecificInputFactory,
        PaymentTokenManagementInterface $paymentTokenManagement,
        ThreeDSecureFactory $threeDSecureFactory,
        RedirectionDataFactory $redirectionDataFactory,
        ManagerInterface $eventManager
    ) {
        $this->config = $config;
        $this->cardPaymentMethodSpecificInputFactory = $cardPaymentMethodSpecificInputFactory;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->threeDSecureFactory = $threeDSecureFactory;
        $this->redirectionDataFactory = $redirectionDataFactory;
        $this->eventManager = $eventManager;
    }

    public function build(CartInterface $quote): CardPaymentMethodSpecificInput
    {
        $storeId = (int)$quote->getStoreId();
        /** @var CardPaymentMethodSpecificInput $cardPaymentMethodSpecificInput */
        $cardPaymentMethodSpecificInput = $this->cardPaymentMethodSpecificInputFactory->create();
        $cardPaymentMethodSpecificInput->setAuthorizationMode($this->config->getAuthorizationMode($storeId));

        $cardPaymentMethodSpecificInput->setThreeDSecure($this->getThreeDSecure($storeId));

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

    private function getThreeDSecure(int $storeId): ThreeDSecure
    {
        /** @var ThreeDSecure $threeDSecure */
        $threeDSecure = $this->threeDSecureFactory->create();
        $isSkipAuthentication = $this->config->hasSkipAuthentication($storeId);
        $threeDSecure->setSkipAuthentication($isSkipAuthentication);
        if (!$isSkipAuthentication && $this->config->isTriggerAnAuthentication($storeId)) {
            $threeDSecure->setChallengeIndicator('challenge-required');
        }
        /** @var RedirectionData $redirectionData */
        $redirectionData = $this->redirectionDataFactory->create();
        $redirectionData->setReturnUrl($this->config->getReturnUrl());
        $threeDSecure->setRedirectionData($redirectionData);

        return $threeDSecure;
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
