<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\WebApi;

use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Worldline\RedirectPayment\Api\RedirectManagementInterface;
use Worldline\HostedCheckout\Service\Creator\Request;
use Worldline\RedirectPayment\Service\Creator\RequestBuilder;
use Worldline\PaymentCore\Model\DataAssigner\DataAssignerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RedirectManagement implements RedirectManagementInterface
{
    public const PAYMENT_PRODUCT_ID = 'selected_payment_product';

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var Request
     */
    private $createRequest;

    /**
     * @var RequestBuilder
     */
    private $createRequestBuilder;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var DataAssignerInterface[]
     */
    private $dataAssignerPool;

    /**
     * @var PaymentInformationManagementInterface
     */
    private $paymentInformationManagement;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        Request $createRequest,
        RequestBuilder $createRequestBuilder,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        RequestInterface $request,
        PaymentInformationManagementInterface $paymentInformationManagement,
        array $dataAssignerPool = []
    ) {
        $this->cartRepository = $cartRepository;
        $this->createRequest = $createRequest;
        $this->createRequestBuilder = $createRequestBuilder;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->request = $request;
        $this->dataAssignerPool = $dataAssignerPool;
        $this->paymentInformationManagement = $paymentInformationManagement;
    }

    /**
     * Get redirect url
     *
     * @param int $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @throws LocalizedException
     *
     * @return string redirect url
     */
    public function processRedirectRequest(
        int $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ): string {
        $quote = $this->cartRepository->get($cartId);

        return $this->process($quote, $paymentMethod, $billingAddress);
    }

    /**
     * @param string $cartId
     * @param PaymentInterface $paymentMethod
     * @param string $email
     * @param AddressInterface|null $billingAddress
     * @throws LocalizedException
     *
     * @return string redirect url
     */
    public function processGuestRedirectRequest(
        string $cartId,
        PaymentInterface $paymentMethod,
        string $email,
        AddressInterface $billingAddress = null
    ): string {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $quote = $this->cartRepository->get($quoteIdMask->getQuoteId());
        $quote->setCustomerEmail($email);

        // compatibility with magento 2.3.7
        $quote->setCustomerIsGuest(true);

        return $this->process($quote, $paymentMethod, $billingAddress);
    }

    private function process(
        CartInterface $quote,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ): string {
        $this->paymentInformationManagement->savePaymentInformation($quote->getId(), $paymentMethod, $billingAddress);
        $payment = $quote->getPayment();

        $additionalData = $paymentMethod->getAdditionalData();
        $additionalData = array_merge((array)$paymentMethod->getAdditionalInformation(), (array)$additionalData);
        $additionalData['agent'] = $this->request->getHeader('accept');
        $additionalData['user-agent'] = $this->request->getHeader('user-agent');

        foreach ($this->dataAssignerPool as $dataAssigner) {
            $dataAssigner->assign($quote->getPayment(), $additionalData);
        }

        $quote->reserveOrderId();

        $this->setToken($quote, $paymentMethod);
        $this->setPaymentProductId($quote, $paymentMethod);

        $request = $this->createRequestBuilder->build($quote);
        $response = $this->createRequest->create($request, (int)$quote->getStoreId());
        $payment->setAdditionalInformation('return_id', $response->getRETURNMAC());
        $payment->setAdditionalInformation('hosted_checkout_id', $response->getHostedCheckoutId());

        $this->cartRepository->save($quote);

        return $response->getRedirectUrl();
    }

    private function setToken(CartInterface $quote, PaymentInterface $paymentMethod): void
    {
        $payment = $quote->getPayment();
        $publicToken = $paymentMethod->getAdditionalData()['public_hash'] ?? false;
        if ($publicToken) {
            $payment->setAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH, $publicToken);
            $payment->setAdditionalInformation(PaymentTokenInterface::CUSTOMER_ID, $quote->getCustomerId());
        }
    }

    private function setPaymentProductId(CartInterface $quote, PaymentInterface $paymentMethod): void
    {
        $payment = $quote->getPayment();
        $payProductId = $paymentMethod->getAdditionalData()['selected_payment_product'] ?? false;
        if ($payProductId) {
            $payment->setAdditionalInformation(self::PAYMENT_PRODUCT_ID, (int)$payProductId);
        }
    }
}
