<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Test\Integration\Payment;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\RedirectPayment\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\ServiceStubSwitcherInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\WebhookStubSenderInterface;
use Worldline\PaymentCore\Model\ResourceModel\Quote as QuoteExtendedRepository;
use Worldline\PaymentCore\Infrastructure\StubData\Webhook\AuthorizationWithDiscount;

/**
 * Test cases for "Place order with discount & Tax"
 */
class PlaceOrderWithDiscountAndTax extends TestCase
{
    public const SHIPPING_TOTAL_WITH_TAX = 3.5300;
    public const SHIPPING_BASE_TOTAL_WITH_TAX = 5.0000;

    /**
     * @var OrderInterfaceFactory
     */
    private $orderFactory;

    /**
     * @var WebhookStubSenderInterface
     */
    private $webhookStubSender;

    /**
     * @var QuoteExtendedRepository
     */
    private $quoteExtendedRepository;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->orderFactory = $objectManager->get(OrderInterfaceFactory::class);
        $this->webhookStubSender = $objectManager->get(WebhookStubSenderInterface::class);
        $this->quoteExtendedRepository = $objectManager->get(QuoteExtendedRepository::class);

        $objectManager->get(ServiceStubSwitcherInterface::class)->setEnabled(true);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDataFixture Magento/SalesRule/_files/cart_fixed_10_discount.php
     * @magentoConfigFixture current_store currency/options/allow EUR
     * @magentoConfigFixture current_store currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/payment_action authorize
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/authorization_mode final
     * @magentoConfigFixture current_store worldline_connection/webhook/key test-X-Gcs-Keyid
     * @magentoConfigFixture current_store worldline_connection/webhook/secret_key test-X-Gcs-Signature
     * @magentoConfigFixture current_store tax/classes/shipping_tax_class 2
     */
    public function testOrderWithDiscountAndTax()
    {
        $reservedOrderId = $this->getQuote()->getReservedOrderId();

        // send the webhook and place the order
        $result = $this->webhookStubSender->sendWebhook(AuthorizationWithDiscount::getData($reservedOrderId));

        // validate controller result
        $reflectedResult = new \ReflectionObject($result);
        $jsonProperty = $reflectedResult->getProperty('json');
        $jsonProperty->setAccessible(true);
        $this->assertEquals('{"messages":[],"error":false}', $jsonProperty->getValue($result));

        // validate created order
        $order = $this->orderFactory->create()->loadByIncrementId($reservedOrderId);
        $this->assertTrue((bool) $order->getId());
        $this->assertEquals(
            ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID,
            $order->getPayment()->getMethod()
        );
        $this->assertEquals(self::SHIPPING_TOTAL_WITH_TAX, $order->getGrandTotal());
        $this->assertEquals(self::SHIPPING_BASE_TOTAL_WITH_TAX, $order->getBaseGrandTotal());
    }

    private function getQuote(): CartInterface
    {
        // the quote has had grand total with 10 USD already
        $quote = $this->quoteExtendedRepository->getQuoteByReservedOrderId('test01');
        $quote->getPayment()->setMethod(ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID);

        // the shipping amount with tax = 3.53 USD
        $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->getShippingAddress()->collectShippingRates();

        $quote->setCustomerEmail('example@worldline.com');
        $quote->getPayment()->setAdditionalInformation('payment_id', '3254564311_0');
        $quote->getPayment()->setAdditionalInformation('token_id', 'test');

        // calculate grand total with fixed discount (10 USD)
        $quote->collectTotals();

        $quote->save();

        return $quote;
    }
}
