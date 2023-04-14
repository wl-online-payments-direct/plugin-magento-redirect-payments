<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Test\Integration\Settings;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\RedirectPayment\Gateway\Config\Config;
use Worldline\RedirectPayment\Service\CreateHostedCheckoutRequest\CardPaymentMethodSIDBuilder;
use Worldline\RedirectPayment\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\QuoteResourceInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\ServiceStubSwitcherInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\WebhookStubSenderInterface;
use Worldline\PaymentCore\Infrastructure\StubData\Webhook\Authorization;

/**
 * Test cases for configuration "Payment Action" and "Authorization Mode"
 */
class PaymentAuthorizeActionTest extends TestCase
{
    /**
     * @var CardPaymentMethodSIDBuilder
     */
    private $cardPaymentMethodSIDBuilder;

    /**
     * @var CartInterface
     */
    private $quote;

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

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->cardPaymentMethodSIDBuilder = $objectManager->get(CardPaymentMethodSIDBuilder::class);
        $this->webhookStubSender = $objectManager->get(WebhookStubSenderInterface::class);
        $this->orderFactory = $objectManager->get(OrderInterfaceFactory::class);
        $this->quoteExtendedRepository = $objectManager->get(QuoteResourceInterface::class);
        $objectManager->get(ServiceStubSwitcherInterface::class)->setEnabled(true);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default/currency/options/allow EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture default/sales_email/general/async_sending 0
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/payment_action authorize
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/authorization_mode final
     * @magentoConfigFixture current_store worldline_connection/webhook/key test-X-Gcs-Keyid
     * @magentoConfigFixture current_store worldline_connection/webhook/secret_key test-X-Gcs-Signature
     */
    public function testAuthorizeWithFinalAuthorization(): void
    {
        $quote = $this->getQuote();
        $cardPaymentMethodSpecificInput = $this->cardPaymentMethodSIDBuilder->build($quote);
        $this->assertEquals(
            Config::AUTHORIZATION_MODE_FINAL,
            $cardPaymentMethodSpecificInput->getAuthorizationMode()
        );

        // send the webhook and place the order
        $result = $this->webhookStubSender->sendWebhook(Authorization::getData($quote->getReservedOrderId()));

        // validate controller result
        $reflectedResult = new \ReflectionObject($result);
        $jsonProperty = $reflectedResult->getProperty('json');
        $jsonProperty->setAccessible(true);
        $this->assertEquals('{"messages":[],"error":false}', $jsonProperty->getValue($result));

        // validate created order
        $order = $this->orderFactory->create()->loadByIncrementId($quote->getReservedOrderId());
        $this->assertTrue((bool) $order->getId());
        $this->assertEquals('processing', $order->getStatus());
        $this->assertCount(0, $order->getInvoiceCollection()->getItems());
        $this->assertEquals(
            ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID,
            $order->getPayment()->getMethod()
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store currency/options/allow EUR
     * @magentoConfigFixture current_store currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture default/sales_email/general/async_sending 0
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/payment_action authorize
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/authorization_mode pre
     * @magentoConfigFixture current_store worldline_connection/webhook/key test-X-Gcs-Keyid
     * @magentoConfigFixture current_store worldline_connection/webhook/secret_key test-X-Gcs-Signature
     */
    public function testAuthorizeWithPreAuthorization(): void
    {
        $quote = $this->getQuote();
        $cardPaymentMethodSpecificInput = $this->cardPaymentMethodSIDBuilder->build($quote);
        $this->assertEquals(
            Config::AUTHORIZATION_MODE_PRE,
            $cardPaymentMethodSpecificInput->getAuthorizationMode()
        );

        // send the webhook and place the order
        $result = $this->webhookStubSender->sendWebhook(Authorization::getData($quote->getReservedOrderId()));

        // validate controller result
        $reflectedResult = new \ReflectionObject($result);
        $jsonProperty = $reflectedResult->getProperty('json');
        $jsonProperty->setAccessible(true);
        $this->assertEquals('{"messages":[],"error":false}', $jsonProperty->getValue($result));

        // validate created order
        $order = $this->orderFactory->create()->loadByIncrementId($quote->getReservedOrderId());
        $this->assertTrue((bool) $order->getId());
        $this->assertEquals('processing', $order->getStatus());
        $this->assertCount(0, $order->getInvoiceCollection()->getItems());
        $this->assertEquals(
            ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID,
            $order->getPayment()->getMethod()
        );
    }

    private function getQuote(): CartInterface
    {
        if (empty($this->quote)) {
            $this->quote = $this->quoteExtendedRepository->getQuoteByReservedOrderId('test01');
            $this->quote->getPayment()->setMethod(
                ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID
            );
            $this->quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
            $this->quote->getShippingAddress()->setCollectShippingRates(true);
            $this->quote->getShippingAddress()->collectShippingRates();
            $this->quote->setCustomerEmail('example@worldline.com');
            $this->quote->getPayment()->setAdditionalInformation('payment_id', '3254564310_0');
            $this->quote->getPayment()->setAdditionalInformation('token_id', 'test');
            $this->quote->collectTotals();
            $this->quote->save();
        }

        return $this->quote;
    }
}
