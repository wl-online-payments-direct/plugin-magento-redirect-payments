<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\WebApi\CreatePaymentManagement;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\PaymentInterface;
use Psr\Log\LoggerInterface;
use Worldline\HostedCheckout\Api\TokenManagerInterface;
use Worldline\HostedCheckout\Gateway\Request\PaymentDataBuilder;
use Worldline\HostedCheckout\Service\HostedCheckout\CreateHostedCheckoutService;
use Worldline\PaymentCore\Api\Data\QuotePaymentInterface;
use Worldline\PaymentCore\Model\DataAssigner\DataAssignerInterface;
use Worldline\RedirectPayment\Service\HostedCheckout\CreateHostedCheckoutRequestBuilder;

class CreatePaymentDataAssigner implements DataAssignerInterface
{
    /**
     * @var CreateHostedCheckoutService
     */
    private $createRequest;

    /**
     * @var CreateHostedCheckoutRequestBuilder
     */
    private $createRequestBuilder;

    /**
     * @var TokenManagerInterface
     */
    private $tokenManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        CreateHostedCheckoutService $createRequest,
        CreateHostedCheckoutRequestBuilder $createRequestBuilder,
        TokenManagerInterface $tokenManager,
        LoggerInterface $logger
    ) {
        $this->createRequest = $createRequest;
        $this->createRequestBuilder = $createRequestBuilder;
        $this->tokenManager = $tokenManager;
        $this->logger = $logger;
    }

    /**
     * Assign return and payment id and identify redirect url
     *
     * @param PaymentInterface $payment
     * @param QuotePaymentInterface $wlQuotePayment
     * @param array $additionalInformation
     * @return void
     * @throws LocalizedException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function assign(
        PaymentInterface $payment,
        QuotePaymentInterface $wlQuotePayment,
        array $additionalInformation
    ): void {
        $quote = $payment->getQuote();

        $token = $this->tokenManager->getToken($quote);
        if ($token && $this->tokenManager->isSepaToken($token)) {
            return;
        }

        $storedPayIds = $payment->getAdditionalInformation('payment_ids') ?? [];

        $request = $this->createRequestBuilder->build($quote);
        $response = $this->createRequest->execute($request, (int)$quote->getStoreId());
        $paymentId = $response->getHostedCheckoutId();
        $payment->setAdditionalInformation('return_id', $response->getRETURNMAC());
        $payment->setAdditionalInformation('payment_ids', array_merge($storedPayIds, [$paymentId]));
        $payment->setAdditionalInformation(PaymentDataBuilder::HOSTED_CHECKOUT_ID, $paymentId);
        $payment->setWlRedirectUrl($response->getRedirectUrl());
        $wlQuotePayment->setPaymentIdentifier($paymentId);
        $wlQuotePayment->setMethod($payment->getMethod());

        if ($storedPayIds) {
            $this->logger->warning(__(
                'Another payment ID for quote was noticed: payment_id - '
                . $paymentId . ', quote_id - ' . $quote->getId() . '.'
            ));
        }
    }
}
