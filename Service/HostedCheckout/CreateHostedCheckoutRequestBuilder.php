<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Service\HostedCheckout;

use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequestFactory;
use Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest\OrderDataBuilder;
use Worldline\PaymentCore\Api\Ui\PaymentProductsProviderInterface;
use Worldline\RedirectPayment\Service\CreateHostedCheckoutRequest\CardPaymentMethodSIDBuilder;
use Worldline\RedirectPayment\Service\CreateHostedCheckoutRequest\MobilePaymentMethodSpecificInputDataBuilder;
use Worldline\RedirectPayment\Service\CreateHostedCheckoutRequest\RedirectPaymentMethodSpecificInputDataBuilder;
use Worldline\RedirectPayment\Service\CreateHostedCheckoutRequest\SepaDirectDebitSIBuilder;
use Worldline\RedirectPayment\Service\CreateHostedCheckoutRequest\SpecificInputDataBuilder;
use Worldline\RedirectPayment\WebApi\RedirectManagement;

class CreateHostedCheckoutRequestBuilder
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

    public function __construct(
        CreateHostedCheckoutRequestFactory $createHostedCheckoutRequestFactory,
        OrderDataBuilder $orderDataBuilder,
        PaymentProductsProviderInterface $payProductsProvider,
        SpecificInputDataBuilder $specificInputDataBuilder,
        RedirectPaymentMethodSpecificInputDataBuilder $redirectPaymentMethodSpecificInputDataBuilder,
        CardPaymentMethodSIDBuilder $cardPaymentMethodSIDBuilder,
        MobilePaymentMethodSpecificInputDataBuilder $mobilePaymentMethodSpecificInputDataBuilder,
        SepaDirectDebitSIBuilder $sepaDirectDebitPaymentMethodSpecificInputBuilder
    ) {
        $this->createHostedCheckoutRequestFactory = $createHostedCheckoutRequestFactory;
        $this->orderDataBuilder = $orderDataBuilder;
        $this->payProductsProvider = $payProductsProvider;
        $this->specificInputDataBuilder = $specificInputDataBuilder;
        $this->redirectPaymentMethodSpecificInputDataBuilder = $redirectPaymentMethodSpecificInputDataBuilder;
        $this->cardPaymentMethodSIDBuilder = $cardPaymentMethodSIDBuilder;
        $this->mobilePaymentMethodSpecificInputDataBuilder = $mobilePaymentMethodSpecificInputDataBuilder;
        $this->sepaDirectDebitPaymentMethodSpecificInputBuilder = $sepaDirectDebitPaymentMethodSpecificInputBuilder;
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

        return $createHostedCheckoutRequest;
    }
}
