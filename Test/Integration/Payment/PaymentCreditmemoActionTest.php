<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Test\Integration\Payment;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\RedirectPayment\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\PaymentCore\Api\QuoteResourceInterface;
use Worldline\PaymentCore\Api\RefundRequestRepositoryInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\ServiceStubSwitcherInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\WebhookStubSenderInterface;
use Worldline\PaymentCore\Model\RefundRequest\CreditmemoOnlineService;
use Worldline\PaymentCore\Infrastructure\StubData\Webhook\Authorization;
use Worldline\PaymentCore\Infrastructure\StubData\Webhook\Refund;

/**
 * Test cases for "Creditmemo"
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PaymentCreditmemoActionTest extends TestCase
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
     * @var CreditmemoLoader
     */
    private $creditmemoLoader;

    /**
     * @var CreditmemoOnlineService
     */
    private $refundOnlineService;

    /**
     * @var RefundRequestRepositoryInterface
     */
    private $refundRequestRepository;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->webhookStubSender = $objectManager->get(WebhookStubSenderInterface::class);
        $this->orderFactory = $objectManager->get(OrderInterfaceFactory::class);
        $this->quoteExtendedRepository = $objectManager->get(QuoteResourceInterface::class);
        $this->creditmemoLoader = $objectManager->get(CreditmemoLoader::class);
        $this->refundOnlineService = $objectManager->get(CreditmemoOnlineService::class);
        $this->refundRequestRepository = $objectManager->get(RefundRequestRepositoryInterface::class);
        $objectManager->get(ServiceStubSwitcherInterface::class)->setEnabled(true);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default/currency/options/allow EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture default/sales_email/general/async_sending 0
     * @magentoConfigFixture default/sales_email/creditmemo/enabled 0
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/payment_action authorize_capture
     * @magentoConfigFixture current_store worldline_connection/webhook/key test-X-Gcs-Keyid
     * @magentoConfigFixture current_store worldline_connection/webhook/secret_key test-X-Gcs-Signature
     */
    public function testAuthorizeAndCapture(): void
    {
        $reservedOrderId = $this->getQuote()->getReservedOrderId();

        // send the webhook and place the order
        $result = $this->webhookStubSender->sendWebhook(Authorization::getData($reservedOrderId));

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
        $this->assertCount(1, $order->getInvoiceCollection()->getItems());

        $creditmemo = $this->loadCreditMemo($order);
        $creditmemo->getOrder()->setCustomerNoteNotify(false);
        $creditmemo = $this->refundOnlineService->refund($creditmemo);

        // send the webhook and refund the order
        $result = $this->webhookStubSender->sendWebhook(Refund::getData($reservedOrderId));

        // validate controller result
        $reflectedResult = new \ReflectionObject($result);
        $jsonProperty = $reflectedResult->getProperty('json');
        $jsonProperty->setAccessible(true);
        $this->assertEquals('{"messages":[],"error":false}', $jsonProperty->getValue($result));

        // validate created credit memo
        $refundRequest = current($this->refundRequestRepository->getListByIncrementId($reservedOrderId));
        $this->assertTrue($refundRequest->isRefunded());
        $this->assertEquals($refundRequest->getCreditMemoId(), (int)$creditmemo->getId());
    }

    private function getQuote(): CartInterface
    {
        $quote = $this->quoteExtendedRepository->getQuoteByReservedOrderId('test01');
        $quote->getPayment()->setMethod(ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID);
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

    /**
     * @param Order $order
     * @return false|Creditmemo
     */
    private function loadCreditMemo(Order $order)
    {
        $orderItem = current($order->getItems());
        $creditmemoData = [
            'items' => [
                $orderItem->getItemId() => [
                    'qty' => '1'
                ],
                'do_offline' => '0',
                'comment_text' => '',
                'shipping_amount' => $order->getBaseShippingAmount(),
                'adjustment_positive' => '0.00',
                'adjustment_negative' => '0.00'
            ]
        ];
        $invoice = $order->getInvoiceCollection()->getFirstItem();
        $this->creditmemoLoader->setOrderId($order->getId());
        $this->creditmemoLoader->setInvoiceId($invoice->getId());
        $this->creditmemoLoader->setCreditmemo($creditmemoData);

        return $this->creditmemoLoader->load();
    }
}
