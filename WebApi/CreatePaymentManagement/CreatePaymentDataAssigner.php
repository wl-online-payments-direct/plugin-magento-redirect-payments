<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\WebApi\CreatePaymentManagement;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\PaymentInterface;
use Worldline\HostedCheckout\Gateway\Request\PaymentDataBuilder;
use Worldline\HostedCheckout\Service\HostedCheckout\CreateHostedCheckoutService;
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

    public function __construct(
        CreateHostedCheckoutService $createRequest,
        CreateHostedCheckoutRequestBuilder $createRequestBuilder
    ) {
        $this->createRequest = $createRequest;
        $this->createRequestBuilder = $createRequestBuilder;
    }

    /**
     * Assign return and payment id and identify redirect url
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
        $quote = $payment->getQuote();

        $request = $this->createRequestBuilder->build($quote);
        $response = $this->createRequest->execute($request, (int)$quote->getStoreId());
        $payment->setAdditionalInformation('return_id', $response->getRETURNMAC());
        $payment->setAdditionalInformation(PaymentDataBuilder::HOSTED_CHECKOUT_ID, $response->getHostedCheckoutId());
        $payment->setWlRedirectUrl($response->getRedirectUrl());
    }
}
