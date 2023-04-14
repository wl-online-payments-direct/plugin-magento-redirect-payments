<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Test\Integration\Settings;

use Magento\Quote\Api\Data\CartInterface;
use Magento\TestFramework\Helper\Bootstrap;
use OnlinePayments\Sdk\Domain\Order;
use PHPUnit\Framework\TestCase;
use Worldline\HostedCheckout\Model\Config\Source\MealvouchersProductTypes;
use Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest\OrderDataBuilder;
use Worldline\RedirectPayment\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\PaymentCore\Api\QuoteResourceInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\ServiceStubSwitcherInterface;

/**
 * Test cases for configuration "Process Mealvouchers"
 */
class ProcessMealvouchersTest extends TestCase
{
    /**
     * @var OrderDataBuilder
     */
    private $orderDataBuilder;

    /**
     * @var QuoteResourceInterface
     */
    private $quoteExtendedRepository;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->orderDataBuilder = $objectManager->get(OrderDataBuilder::class);
        $this->quoteExtendedRepository = $objectManager->get(QuoteResourceInterface::class);
        $objectManager->get(ServiceStubSwitcherInterface::class)->setEnabled(true);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDataFixture Magento/SalesRule/_files/cart_fixed_10_discount.php
     * @magentoConfigFixture default/currency/options/allow EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture default/sales_email/general/async_sending 0
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/cart_lines 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_5402/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/payment_action authorize
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/authorization_mode final
     * @magentoConfigFixture current_store worldline_connection/webhook/key test-X-Gcs-Keyid
     * @magentoConfigFixture current_store worldline_connection/webhook/secret_key test-X-Gcs-Signature
     * @magentoConfigFixture current_store worldline_payment/general_settings/enable_3d 1
     */
    public function testProcessMealvouchers(): void
    {
        $quote = $this->getQuote();
        /** @var Order $orderSpecificInput */
        $orderSpecificInput = $this->orderDataBuilder->build($quote);
        $shoppingCart = $orderSpecificInput->getShoppingCart();
        $this->assertCount(count($quote->getAllItems()) + 1, $shoppingCart->getItems());
        foreach ($shoppingCart->getItems() as $lineItem) {
            $this->assertEquals(
                MealvouchersProductTypes::FOOD_AND_DRINK,
                $lineItem->getOrderLineDetails()->getProductType()
            );
        }
    }

    private function getQuote(): CartInterface
    {
        $quote = $this->quoteExtendedRepository->getQuoteByReservedOrderId('test01');
        $quote->getPayment()->setMethod(
            ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::MEALVOUCHERS_PRODUCT_ID
        );
        $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->getShippingAddress()->collectShippingRates();
        $quote->setCustomerEmail('example@worldline.com');
        $quote->getPayment()->setAdditionalInformation('payment_id', '3254564311_0');
        $quote->getPayment()->setAdditionalInformation('token_id', 'test');
        $quote->collectTotals();
        $quote->save();

        foreach ($quote->getAllItems() as $item) {
            if ($item->getParentItem()) {
                continue;
            }
            $item->getProduct()->setData(
                MealvouchersProductTypes::MEALVOUCHERS_ATTRIBUTE_CODE,
                MealvouchersProductTypes::FOOD_AND_DRINK
            );
        }

        return $quote;
    }
}
