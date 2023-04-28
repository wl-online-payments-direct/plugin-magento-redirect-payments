<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Test\Integration\Payment;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\RedirectPayment\Gateway\Config\Config;
use Worldline\RedirectPayment\Service\CreateHostedCheckoutRequest\CardPaymentMethodSIDBuilder;
use Worldline\RedirectPayment\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\PaymentRepositoryInterface;
use Worldline\PaymentCore\Api\QuoteResourceInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\ServiceStubSwitcherInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\WebhookStubSenderInterface;
use Worldline\PaymentCore\Infrastructure\StubData\Webhook\AuthorizationWithIntersolve;

/**
 * Test cases for Intersolve payment product
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PlaceOrderWithIntersolveTest extends TestCase
{
    /**
     * @var CardPaymentMethodSIDBuilder
     */
    private $cardPaymentMethodSIDBuilder;

    /**
     * @var  WebhookStubSenderInterface
     */
    private $webhookStubSender;

    /**
     * @var OrderInterfaceFactory
     */
    private $orderFactory;

    /**
     * @var QuoteResourceInterface
     */
    private $quoteExtendedRepository;

    /**
     * @var PaymentRepositoryInterface
     */
    private $paymentRepository;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->cardPaymentMethodSIDBuilder = $objectManager->get(CardPaymentMethodSIDBuilder::class);
        $this->webhookStubSender = $objectManager->get(WebhookStubSenderInterface::class);
        $this->orderFactory = $objectManager->get(OrderInterfaceFactory::class);
        $this->quoteExtendedRepository = $objectManager->get(QuoteResourceInterface::class);
        $this->paymentRepository = $objectManager->get(PaymentRepositoryInterface::class);
        $objectManager->get(ServiceStubSwitcherInterface::class)->setEnabled(true);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default/currency/options/allow EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture default/sales_email/general/async_sending 0
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_5700/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/payment_action authorize
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/authorization_mode final
     * @magentoConfigFixture current_store worldline_connection/webhook/key test-X-Gcs-Keyid
     * @magentoConfigFixture current_store worldline_connection/webhook/secret_key test-X-Gcs-Signature
     */
    public function testAuthorizeAndCaptureIntersolve(): void
    {
        $quote = $this->getQuote();
        $cardPaymentMethodSpecificInput = $this->cardPaymentMethodSIDBuilder->build($quote);
        $this->assertEquals(
            Config::AUTHORIZATION_MODE_FINAL,
            $cardPaymentMethodSpecificInput->getAuthorizationMode()
        );

        $this->paymentRepository->deleteByIncrementId('test01');

        // send the webhook and place the order
        $result = $this->webhookStubSender->sendWebhook(
            AuthorizationWithIntersolve::getData($quote->getReservedOrderId())
        );

        // validate controller result
        $reflectedResult = new \ReflectionObject($result);
        $jsonProperty = $reflectedResult->getProperty('json');
        $jsonProperty->setAccessible(true);
        $this->assertEquals('{"messages":[],"error":false}', $jsonProperty->getValue($result));

        // validate created order
        $order = $this->orderFactory->create()->loadByIncrementId($quote->getReservedOrderId());
        $this->assertTrue((bool) $order->getId());
        $this->assertEquals('processing', $order->getStatus());
        $this->assertEquals(
            ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::INTERSOLVE_PRODUCT_ID,
            $order->getPayment()->getMethod()
        );
        $this->assertCount(1, $order->getInvoiceCollection()->getItems());
    }

    private function getQuote(): CartInterface
    {
        $quote = $this->quoteExtendedRepository->getQuoteByReservedOrderId('test01');
        $quote->getPayment()->setMethod(
            ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::INTERSOLVE_PRODUCT_ID
        );
        $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->getShippingAddress()->collectShippingRates();
        $quote->setCustomerEmail('example@worldline.com');
        $quote->getPayment()->setAdditionalInformation('payment_id', '3254564310_0');
        $quote->getPayment()->setAdditionalInformation('token_id', 'test');
        $quote->collectTotals();
        $quote->save();

        return $quote;
    }
}
