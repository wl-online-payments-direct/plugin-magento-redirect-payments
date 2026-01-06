<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Service\HostedCheckout;

use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequestFactory;
use Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest\OrderDataBuilder;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\PaymentCore\Api\Service\CreateRequest\FeedbacksDataBuilderInterface;
use Worldline\PaymentCore\Api\Ui\PaymentProductsProviderInterface;
use Worldline\RedirectPayment\Gateway\Config\Config;
use Worldline\RedirectPayment\Service\CreateHostedCheckoutRequest\CardPaymentMethodSIDBuilder;
use Worldline\RedirectPayment\Service\CreateHostedCheckoutRequest\MobilePaymentMethodSpecificInputDataBuilder;
use Worldline\RedirectPayment\Service\CreateHostedCheckoutRequest\RedirectPaymentMethodSpecificInputDataBuilder;
use Worldline\RedirectPayment\Service\CreateHostedCheckoutRequest\SepaDirectDebitSIBuilder;
use Worldline\RedirectPayment\Service\CreateHostedCheckoutRequest\SpecificInputDataBuilder;
use Worldline\RedirectPayment\WebApi\RedirectManagement;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class CreateHostedCheckoutRequestBuilder
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var CreateHostedCheckoutRequestFactory
     */
    private $createHostedCheckoutRequestFactory;

    /**
     * @var OrderDataBuilder
     */
    private $orderDataBuilder;

    /**
     * @var PaymentProductsProviderInterface
     */
    private $payProductsProvider;

    /**
     * @var SpecificInputDataBuilder
     */
    private $specificInputDataBuilder;

    /**
     * @var RedirectPaymentMethodSpecificInputDataBuilder
     */
    private $redirectPaymentMethodSpecificInputDataBuilder;

    /**
     * @var CardPaymentMethodSIDBuilder
     */
    private $cardPaymentMethodSIDBuilder;

    /**
     * @var MobilePaymentMethodSpecificInputDataBuilder
     */
    private $mobilePaymentMethodSpecificInputDataBuilder;

    /**
     * @var SepaDirectDebitSIBuilder
     */
    private $sepaDirectDebitPaymentMethodSpecificInputBuilder;

    /**
     * @var FeedbacksDataBuilderInterface
     */
    private $feedbacksDataBuilder;

    public function __construct(
        Config $config,
        CreateHostedCheckoutRequestFactory $createHostedCheckoutRequestFactory,
        OrderDataBuilder $orderDataBuilder,
        PaymentProductsProviderInterface $payProductsProvider,
        SpecificInputDataBuilder $specificInputDataBuilder,
        RedirectPaymentMethodSpecificInputDataBuilder $redirectPaymentMethodSpecificInputDataBuilder,
        CardPaymentMethodSIDBuilder $cardPaymentMethodSIDBuilder,
        MobilePaymentMethodSpecificInputDataBuilder $mobilePaymentMethodSpecificInputDataBuilder,
        SepaDirectDebitSIBuilder $sepaDirectDebitPaymentMethodSpecificInputBuilder,
        FeedbacksDataBuilderInterface $feedbacksDataBuilder
    ) {
        $this->config = $config;
        $this->createHostedCheckoutRequestFactory = $createHostedCheckoutRequestFactory;
        $this->orderDataBuilder = $orderDataBuilder;
        $this->payProductsProvider = $payProductsProvider;
        $this->specificInputDataBuilder = $specificInputDataBuilder;
        $this->redirectPaymentMethodSpecificInputDataBuilder = $redirectPaymentMethodSpecificInputDataBuilder;
        $this->cardPaymentMethodSIDBuilder = $cardPaymentMethodSIDBuilder;
        $this->mobilePaymentMethodSpecificInputDataBuilder = $mobilePaymentMethodSpecificInputDataBuilder;
        $this->sepaDirectDebitPaymentMethodSpecificInputBuilder = $sepaDirectDebitPaymentMethodSpecificInputBuilder;
        $this->feedbacksDataBuilder = $feedbacksDataBuilder;
    }

    /**
     * @param CartInterface $quote
     * @return CreateHostedCheckoutRequest
     */
    public function build(CartInterface $quote): CreateHostedCheckoutRequest
    {
        $storeId = (int)$quote->getStoreId();
        $payProductId = (int)$quote->getPayment()->getAdditionalInformation(RedirectManagement::PAYMENT_PRODUCT_ID);

        $createHostedCheckoutRequest = $this->createHostedCheckoutRequestFactory->create();
        $createHostedCheckoutRequest->setOrder($this->orderDataBuilder->build($quote));

        $this->handleBankTransfer($createHostedCheckoutRequest, $storeId, $payProductId);
        $createHostedCheckoutRequest->setHostedCheckoutSpecificInput($this->specificInputDataBuilder->build($quote));
        $this->setPaymentMethodSpecificInput($createHostedCheckoutRequest, $quote, $storeId, $payProductId);
        $this->handleFeedbacks($createHostedCheckoutRequest, $quote);
        $createHostedCheckoutRequest->getOrder()->getCustomer()->getDevice()->setIpAddress(null);

        return $createHostedCheckoutRequest;
    }

    private function handleBankTransfer(
        CreateHostedCheckoutRequest $createHostedCheckoutRequest,
        int $storeId,
        int $payProductId
    ): void {
        $bankTransferDescriptor = $this->config->getBankTransferDescriptor($storeId);
        if ($payProductId === PaymentProductsDetailsInterface::BANK_TRANSFER_PRODUCT_ID && $bankTransferDescriptor) {
            $createHostedCheckoutRequest->getOrder()->getReferences()->setDescriptor($bankTransferDescriptor);
        }
    }

    private function getPaymentMethod(int $storeId, int $payProductId): ?string
    {
        $payProducts = $this->payProductsProvider->getPaymentProducts($storeId);

        if (array_key_exists($payProductId, $payProducts)) {
            return $payProducts[$payProductId]['method'];
        }

        return null;
    }

    private function setPaymentMethodSpecificInput(
        CreateHostedCheckoutRequest $createHostedCheckoutRequest,
        CartInterface $quote,
        int $storeId,
        int $payProductId
    ): void {
        $payMethod = $this->getPaymentMethod($storeId, $payProductId);

        if ($payMethod === null || $payMethod === 'redirect') {
            $createHostedCheckoutRequest->setRedirectPaymentMethodSpecificInput(
                $this->redirectPaymentMethodSpecificInputDataBuilder->build($quote)
            );
        }

        if ($payMethod === 'directDebit') {
            $createHostedCheckoutRequest->setSepaDirectDebitPaymentMethodSpecificInput(
                $this->sepaDirectDebitPaymentMethodSpecificInputBuilder->build($quote)
            );
        }

        if ($payMethod === 'card') {
            $createHostedCheckoutRequest->setCardPaymentMethodSpecificInput(
                $this->cardPaymentMethodSIDBuilder->build($quote)
            );
        }

        if ($payMethod === 'mobile') {
            $createHostedCheckoutRequest->setMobilePaymentMethodSpecificInput(
                $this->mobilePaymentMethodSpecificInputDataBuilder->build($quote)
            );
        }
    }

    private function handleFeedbacks(
        CreateHostedCheckoutRequest $createHostedCheckoutRequest,
        CartInterface $quote
    ): void {
        $feedbacks = $this->feedbacksDataBuilder->build($quote);
        if ($feedbacks !== null) {
            $createHostedCheckoutRequest->setFeedbacks($feedbacks);
        }
    }
}
