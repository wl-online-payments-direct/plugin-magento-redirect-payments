<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Test\Integration\Payment;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Request\HttpFactory as HttpRequestFactory;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\RedirectPayment\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\QuoteResourceInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\ServiceStubSwitcherInterface;
use Worldline\PaymentCore\Controller\Returns\PendingOrderFactory;

/**
 * Test case about place order via pending page
 */
class PendingOrderTest extends TestCase
{
    /**
     * @var PendingOrderFactory
     */
    private $pendingOrderControllerFactory;

    /**
     * @var HttpRequestFactory
     */
    private $httpRequestFactory;

    /**
     * @var QuoteResourceInterface
     */
    private $quoteExtendedRepository;

    /**
     * @var OrderInterfaceFactory
     */
    private $orderFactory;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->pendingOrderControllerFactory = $objectManager->get(PendingOrderFactory::class);
        $this->httpRequestFactory = $objectManager->get(HttpRequestFactory::class);
        $this->quoteExtendedRepository = $objectManager->get(QuoteResourceInterface::class);
        $this->orderFactory = $objectManager->get(OrderInterfaceFactory::class);
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
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/payment_action authorize
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/authorization_mode final
     * @magentoConfigFixture current_store worldline_connection/webhook/key test-X-Gcs-Keyid
     * @magentoConfigFixture current_store worldline_connection/webhook/secret_key test-X-Gcs-Signature
     */
    public function testPendingOrderController(): void
    {
        /** @var Session $customerSession */
        $customerSession = Bootstrap::getObjectManager()->get(Session::class);
        $customerSession->loginById(1);
        $this->updateQuote();

        $params = [
            'incrementId' => 'test01'
        ];

        $request = $this->httpRequestFactory->create();
        $pendingOrderController = $this->pendingOrderControllerFactory->create(['request' => $request]);

        $pendingOrderController->getRequest()->setParams($params)->setMethod(HttpRequest::METHOD_POST);
        $result = $pendingOrderController->execute();

        // validate controller result
        $reflectedResult = new \ReflectionObject($result);
        $jsonProperty = $reflectedResult->getProperty('json');
        $jsonProperty->setAccessible(true);
        $this->assertEquals('{"status":true}', $jsonProperty->getValue($result));

        // validate created order
        $order = $this->orderFactory->create()->loadByIncrementId('test01');
        $this->assertTrue((bool) $order->getId());
        $this->assertEquals('processing', $order->getStatus());
        $this->assertEquals(
            ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID,
            $order->getPayment()->getMethod()
        );
        $this->assertCount(0, $order->getInvoiceCollection()->getItems());
    }

    private function updateQuote(): void
    {
        $quote = $this->quoteExtendedRepository->getQuoteByReservedOrderId('test01');
        $quote->setCustomerId(1);
        $quote->getPayment()->setMethod(ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID);
        $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->getShippingAddress()->collectShippingRates();
        $quote->setCustomerEmail('example@worldline.com');
        $quote->getPayment()->setAdditionalInformation('payment_id', '3254564310_0');
        $quote->getPayment()->setAdditionalInformation('token_id', 'test');
        $quote->getPayment()->setAdditionalInformation('customer_id', 1);
        $quote->collectTotals();
        $quote->save();
    }
}
