<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Test\Integration\Payment;

use Magento\Customer\Model\Session;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Worldline\RedirectPayment\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\PaymentCore\Api\QuoteResourceInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\ServiceStubSwitcherInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\WebhookStubSenderInterface;
use Worldline\PaymentCore\Infrastructure\StubData\Webhook\AuthorizationWithBundle;
use Worldline\PaymentCore\Infrastructure\StubData\Webhook\AuthorizationWithConfigurable;
use Worldline\PaymentCore\Infrastructure\StubData\Webhook\AuthorizationWithVirtual;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PlaceOrderWithAllProductsTest extends \PHPUnit\Framework\TestCase
{
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
     * @var string[]
     */
    private $addressData = [
        'region' => 'CA',
        'region_id' => '12',
        'postcode' => '11111',
        'company' => 'Test Company',
        'lastname' => 'lastname',
        'firstname' => 'firstname',
        'street' => 'street',
        'city' => 'Los Angeles',
        'email' => 'admin@example.com',
        'telephone' => '11111111',
        'country_id' => 'US'
    ];

    public function setUp(): void
    {
        Bootstrap::getInstance()->loadArea('frontend');
        $objectManager = Bootstrap::getObjectManager();
        $this->webhookStubSender = $objectManager->get(WebhookStubSenderInterface::class);
        $this->orderFactory = $objectManager->get(OrderInterfaceFactory::class);
        $this->quoteExtendedRepository = $objectManager->get(QuoteResourceInterface::class);
        $objectManager->get(ServiceStubSwitcherInterface::class)->setEnabled(true);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote_with_bundle.php
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
    public function testPlaceOrderWithBundleProduct(): void
    {
        $reservedOrderId = $this->getQuote()->getReservedOrderId();
        // send the webhook and place the order
        $result = $this->webhookStubSender->sendWebhook(AuthorizationWithBundle::getData($reservedOrderId));

        // validate controller result
        $reflectedResult = new \ReflectionObject($result);
        $jsonProperty = $reflectedResult->getProperty('json');
        $jsonProperty->setAccessible(true);
        $this->assertEquals('{"messages":[],"error":false}', $jsonProperty->getValue($result));

        // validate created order
        $order = $this->orderFactory->create()->loadByIncrementId($reservedOrderId);
        $this->assertTrue((bool) $order->getId());
        $this->assertEquals('processing', $order->getStatus());
        $this->assertEquals(
            ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID,
            $order->getPayment()->getMethod()
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/ConfigurableProduct/_files/customer_quote_with_items_configurable_product.php
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
    public function testPlaceOrderWithConfigurableProduct(): void
    {
        $reservedOrderId = $this->getConfigurableQuote()->getReservedOrderId();
        // send the webhook and place the order
        $result = $this->webhookStubSender->sendWebhook(AuthorizationWithConfigurable::getData($reservedOrderId));

        // validate controller result
        $reflectedResult = new \ReflectionObject($result);
        $jsonProperty = $reflectedResult->getProperty('json');
        $jsonProperty->setAccessible(true);
        $this->assertEquals('{"messages":[],"error":false}', $jsonProperty->getValue($result));

        // validate created order
        $order = $this->orderFactory->create()->loadByIncrementId($reservedOrderId);
        $this->assertTrue((bool) $order->getId());
        $this->assertEquals('processing', $order->getStatus());
        $this->assertEquals(
            ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID,
            $order->getPayment()->getMethod()
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Customer/_files/customer_address.php
     * @magentoDataFixture Magento/Checkout/_files/active_quote_with_downloadable_product.php
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
    public function testPlaceOrderWithDownloadableProduct(): void
    {
        /** @var Session $customerSession */
        $customerSession = Bootstrap::getObjectManager()->get(Session::class);
        $customerSession->loginById(1);

        $reservedOrderId = $this->getVirtualQuote()->getReservedOrderId();
        // send the webhook and place the order
        $result = $this->webhookStubSender->sendWebhook(AuthorizationWithVirtual::getData($reservedOrderId));

        // validate controller result
        $reflectedResult = new \ReflectionObject($result);
        $jsonProperty = $reflectedResult->getProperty('json');
        $jsonProperty->setAccessible(true);
        $this->assertEquals('{"messages":[],"error":false}', $jsonProperty->getValue($result));

        // validate created order
        $order = $this->orderFactory->create()->loadByIncrementId($reservedOrderId);
        $this->assertTrue((bool) $order->getId());
        $this->assertEquals('processing', $order->getStatus());
        $this->assertEquals(
            ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID,
            $order->getPayment()->getMethod()
        );
    }

    private function getQuote(): CartInterface
    {
        $quote = $this->quoteExtendedRepository->getQuoteByReservedOrderId('test01');
        $quote->getPayment()->setMethod(
            ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID
        );
        $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->getShippingAddress()->collectShippingRates();
        $quote->setCustomerEmail('example@worldline.com');
        $quote->getPayment()->setAdditionalInformation('payment_id', '3254564312_0');
        $quote->getPayment()->setAdditionalInformation('token_id', 'test');
        $quote->collectTotals();
        $quote->save();

        return $quote;
    }

    private function getConfigurableQuote(): CartInterface
    {
        $quote = $this->quoteExtendedRepository->getQuoteByReservedOrderId('customer_quote_configurable_products');
        $quote->getPayment()->setMethod(
            ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID
        );
        $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->getShippingAddress()->collectShippingRates();
        $quote->getPayment()->setAdditionalInformation('payment_id', '3254564313_0');
        $quote->getPayment()->setAdditionalInformation('token_id', 'test');
        $quote->collectTotals();
        $quote->save();

        return $quote;
    }

    private function getVirtualQuote(): CartInterface
    {
        $objectManager = Bootstrap::getObjectManager();
        $billingAddress = $objectManager->create(Address::class, ['data' => $this->addressData]);
        $billingAddress->setAddressType('billing');

        $quote = $this->quoteExtendedRepository->getQuoteByReservedOrderId('test_order_1');
        $quote->getPayment()->setMethod(
            ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID
        );
        $quote->setBillingAddress($billingAddress);
        $quote->getPayment()->setAdditionalInformation('payment_id', '3254564314_0');
        $quote->getPayment()->setAdditionalInformation('token_id', 'test');
        $quote->collectTotals();
        $quote->save();

        return $quote;
    }
}
