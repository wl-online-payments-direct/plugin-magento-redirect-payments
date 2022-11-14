<?php

declare(strict_types=1);

namespace Worldline\RedirectPayment\Service\Creator;

use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequestFactory;
use Worldline\PaymentCore\Model\Ui\PaymentProductsProvider;
use Worldline\RedirectPayment\Service\Creator\Request\CardPaymentMethodSpecificInputDataBuilder;
use Worldline\RedirectPayment\Service\Creator\Request\MobilePaymentMethodSpecificInputDataBuilder;
use Worldline\RedirectPayment\Service\Creator\Request\OrderDataBuilder;
use Worldline\RedirectPayment\Service\Creator\Request\RedirectPaymentMethodSpecificInputDataBuilder;
use Worldline\RedirectPayment\Service\Creator\Request\SpecificInputDataBuilder;
use Worldline\RedirectPayment\WebApi\RedirectManagement;

class RequestBuilder
{
    /**
     * @var CreateHostedCheckoutRequestFactory
     */
    private $createHostedCheckoutRequestFactory;

    /**
     * @var OrderDataBuilder
     */
    private $orderDataBuilder;

    /**
     * @var PaymentProductsProvider
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
     * @var CardPaymentMethodSpecificInputDataBuilder
     */
    private $cardPaymentMethodSpecificInputDataBuilder;

    /**
     * @var MobilePaymentMethodSpecificInputDataBuilder
     */
    private $mobilePaymentMethodSpecificInputDataBuilder;

    public function __construct(
        CreateHostedCheckoutRequestFactory $createHostedCheckoutRequestFactory,
        OrderDataBuilder $orderDataBuilder,
        PaymentProductsProvider $payProductsProvider,
        SpecificInputDataBuilder $specificInputDataBuilder,
        RedirectPaymentMethodSpecificInputDataBuilder $redirectPaymentMethodSpecificInputDataBuilder,
        CardPaymentMethodSpecificInputDataBuilder $cardPaymentMethodSpecificInputDataBuilder,
        CardPaymentMethodSpecificInputDataBuilder $mobilePaymentMethodSpecificInputDataBuilder
    ) {
        $this->createHostedCheckoutRequestFactory = $createHostedCheckoutRequestFactory;
        $this->orderDataBuilder = $orderDataBuilder;
        $this->payProductsProvider = $payProductsProvider;
        $this->specificInputDataBuilder = $specificInputDataBuilder;
        $this->redirectPaymentMethodSpecificInputDataBuilder = $redirectPaymentMethodSpecificInputDataBuilder;
        $this->cardPaymentMethodSpecificInputDataBuilder = $cardPaymentMethodSpecificInputDataBuilder;
        $this->mobilePaymentMethodSpecificInputDataBuilder = $mobilePaymentMethodSpecificInputDataBuilder;
    }

    /**
     * @param CartInterface $quote
     * @return CreateHostedCheckoutRequest
     */
    public function build(CartInterface $quote): CreateHostedCheckoutRequest
    {
        $payProducts = $this->payProductsProvider->getPaymentProducts((int)$quote->getStoreId());
        $payProductId = (int)$quote->getPayment()->getAdditionalInformation(RedirectManagement::PAYMENT_PRODUCT_ID);
        $payMethod = null;
        if (array_key_exists($payProductId, $payProducts)) {
            $payMethod = $payProducts[$payProductId]['method'];
        }

        $createHostedCheckoutRequest = $this->createHostedCheckoutRequestFactory->create();
        $createHostedCheckoutRequest->setOrder($this->orderDataBuilder->build($quote));
        $createHostedCheckoutRequest->setHostedCheckoutSpecificInput($this->specificInputDataBuilder->build($quote));
        if ($payMethod == null || $payMethod === 'redirect') {
            $createHostedCheckoutRequest->setRedirectPaymentMethodSpecificInput(
                $this->redirectPaymentMethodSpecificInputDataBuilder->build($quote)
            );
        }

        if ($payMethod == null || $payMethod === 'card') {
            $createHostedCheckoutRequest->setCardPaymentMethodSpecificInput(
                $this->cardPaymentMethodSpecificInputDataBuilder->build($quote)
            );
        }

        if ($payMethod === 'mobile') {
            $createHostedCheckoutRequest->setMobilePaymentMethodSpecificInput(
                $this->mobilePaymentMethodSpecificInputDataBuilder->build($quote)
            );
        }

        return $createHostedCheckoutRequest;
    }
}
