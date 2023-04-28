<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Test\Integration\Payment;

use Magento\Customer\Model\Session;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\PaymentCore\Infrastructure\ActiveVault\FakePaymentToken;
use Worldline\RedirectPayment\Ui\ConfigProvider;
use Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest\OrderDataBuilder;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\PaymentCore\Api\QuoteResourceInterface;
use Worldline\PaymentCore\Api\SurchargingQuoteRepositoryInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\ServiceStubSwitcherInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\WebhookStubSenderInterface;
use Worldline\PaymentCore\Infrastructure\StubData\Webhook\AuthorizationWithSurcharging;
use Worldline\PaymentCore\Service\CreateRequest\Order\SurchargeDataBuilder;

/**
 * Test case about place order with surcharging and saved card
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SavedCardSurchargingTest extends TestCase
{
    /**
     * @var OrderDataBuilder
     */
    private $orderDataBuilder;

    /**
     * @var QuoteResourceInterface
     */
    private $quoteExtendedRepository;

    /**
     * @var  WebhookStubSenderInterface
     */
    private $webhookStubSender;

    /**
     * @var OrderInterfaceFactory
     */
    private $orderFactory;

    /**
     * @var SurchargingQuoteRepositoryInterface
     */
    private $surchargingQuoteRepository;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->orderDataBuilder = $objectManager->get(OrderDataBuilder::class);
        $this->quoteExtendedRepository = $objectManager->get(QuoteResourceInterface::class);
        $this->webhookStubSender = $objectManager->get(WebhookStubSenderInterface::class);
        $this->orderFactory = $objectManager->get(OrderInterfaceFactory::class);
        $this->surchargingQuoteRepository = $objectManager->get(SurchargingQuoteRepositoryInterface::class);
        $objectManager->get(ServiceStubSwitcherInterface::class)->setEnabled(true);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Customer/_files/customer_address.php
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default/currency/options/allow EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture default/sales_email/general/async_sending 0
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_vault/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/payment_action authorize
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/authorization_mode final
     * @magentoConfigFixture current_store worldline_payment/general_settings/apply_surcharge 1
     * @magentoConfigFixture current_store worldline_connection/webhook/key test-X-Gcs-Keyid
     * @magentoConfigFixture current_store worldline_connection/webhook/secret_key test-X-Gcs-Signature
     */
    public function testSurchargingWithSavedCard(): void
    {
        /** @var Session $customerSession */
        $customerSession = Bootstrap::getObjectManager()->get(Session::class);
        $customerSession->loginById(1);

        Bootstrap::getObjectManager()
            ->get(FakePaymentToken::class)
            ->createVaultToken(ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID);

        $quote = $this->getQuote();
        $grandTotalBeforeCalculateSurcharging = $quote->getGrandTotal();

        $orderSpecificOutput = $this->orderDataBuilder->build($quote);
        $surchargeSpecificInput = $orderSpecificOutput->getSurchargeSpecificInput();

        // validate surcharge settings
        $this->assertNotNull($surchargeSpecificInput);
        $this->assertEquals(SurchargeDataBuilder::SURCHARGE_MODE, $surchargeSpecificInput->getMode());

        // send the webhook and place the order
        $result = $this->webhookStubSender->sendWebhook(
            AuthorizationWithSurcharging::getData($quote->getReservedOrderId())
        );

        // validate controller result
        $reflectedResult = new \ReflectionObject($result);
        $jsonProperty = $reflectedResult->getProperty('json');
        $jsonProperty->setAccessible(true);
        $this->assertEquals('{"messages":[],"error":false}', $jsonProperty->getValue($result));

        $surchargingQuote = $this->surchargingQuoteRepository->getByQuoteId((int)$quote->getId());
        $this->assertNotNull($surchargingQuote->getAmount());

        // validate created order
        $order = $this->orderFactory->create()->loadByIncrementId($quote->getReservedOrderId());
        $this->assertTrue((bool) $order->getId());
        $this->assertEquals('processing', $order->getStatus());
        $this->assertEquals(
            ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID,
            $order->getPayment()->getMethod()
        );
        $this->assertEquals($grandTotalBeforeCalculateSurcharging + 10.0, $order->getGrandTotal());
    }

    private function getQuote(): CartInterface
    {
        $quote = $this->quoteExtendedRepository->getQuoteByReservedOrderId('test01');
        $quote->getPayment()->setMethod(
            ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID . '_vault'
        );
        $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->getShippingAddress()->collectShippingRates();
        $quote->setCustomerEmail('example@worldline.com');
        $quote->getPayment()->setAdditionalInformation('payment_id', '3254564316_0');
        $quote->getPayment()->setAdditionalInformation('token_id', 'test');
        $quote->getPayment()->setAdditionalInformation('public_hash', 'fakePublicHash');
        $quote->getPayment()->setAdditionalInformation('customer_id', 1);
        $quote->collectTotals();
        $quote->save();

        return $quote;
    }
}
