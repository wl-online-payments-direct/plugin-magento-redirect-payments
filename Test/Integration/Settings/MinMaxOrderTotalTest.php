<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Test\Integration\Settings;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Payment\Model\MethodList;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Item\Updater;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\RedirectPayment\Ui\ConfigProvider;

/**
 * Test cases for configurations "Minimum Order Total" and "Maximum Order Total"
 */
class MinMaxOrderTotalTest extends TestCase
{
    /**
     * @var Updater
     */
    private $itemUpdater;

    /**
     * @var Quote
     */
    private $quoteResource;

    /**
     * @var MethodList
     */
    private $methodList;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var QuoteCollectionFactory
     */
    private $quoteCollectionFactory;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->itemUpdater = $objectManager->get(Updater::class);
        $this->quoteResource = $objectManager->get(Quote::class);
        $this->methodList = $objectManager->get(MethodList::class);
        $this->productRepository = $objectManager->get(ProductRepositoryInterface::class);
        $this->quoteCollectionFactory = $objectManager->get(QuoteCollectionFactory::class);
    }

    /**
     * Test the selected minimum and maximum settings
     *
     * Steps:
     * 1) Minimum Order Total = 5
     * 2) Maximum Order Total = 20
     * 3) Go to checkout with 12 order total
     * Expected result: Payment Method is available
     * 4) Change your order total on 24
     * Expected result: Payment Method is NOT available
     *
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store currency/options/allow EUR
     * @magentoConfigFixture current_store currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/payment_action authorize_capture
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/allowspecific 0
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/allow_specific_currency 0
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/min_order_total 5
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/max_order_total 20
     * @magentoDbIsolation disabled
     */
    public function testMinMaxOrderTotal(): void
    {
        $quote = $this->getQuote(); // the quote has default order total value = 10

        // change order total
        $quoteItem = $quote->getItemByProduct($this->productRepository->get('simple'));
        $this->itemUpdater->update($quoteItem, ['qty' => 1, 'custom_price' => 12]);
        $quote->collectTotals();
        $this->quoteResource->save($quote);

        $paymentMethods = $this->methodList->getAvailableMethods($quote);
        $paymentMethodCodes = array_map(static function ($method) {
            return $method->getCode();
        }, $paymentMethods);

        $this->assertTrue(
            in_array(
                ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID,
                $paymentMethodCodes
            )
        );
    }

    /**
     * Test the selected minimum and maximum settings
     *
     * Steps:
     * 1) Minimum Order Total = 5
     * 2) Maximum Order Total = 20
     * 3) Go to checkout with 12 order total
     * Expected result: Payment Method is available
     * 4) Change your order total on 24
     * Expected result: Payment Method is NOT available
     *
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store currency/options/allow EUR
     * @magentoConfigFixture current_store currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/payment_action authorize_capture
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/allowspecific 0
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/allow_specific_currency 0
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/min_order_total 5
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/max_order_total 20
     * @magentoDbIsolation disabled
     */
    public function testMinMaxOrderTotal2(): void
    {
        $quote = $this->getQuote(); // the quote has default order total value = 10

        // change order total
        $quoteItem = $quote->getItemByProduct($this->productRepository->get('simple'));
        $this->itemUpdater->update($quoteItem, ['qty' => 2, 'custom_price' => 12]);
        $quote->collectTotals();
        $this->quoteResource->save($quote);

        $paymentMethods = $this->methodList->getAvailableMethods($quote);
        $paymentMethodCodes = array_map(static function ($method) {
            return $method->getCode();
        }, $paymentMethods);

        $this->assertFalse(
            in_array(
                ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID,
                $paymentMethodCodes
            )
        );
    }

    private function getQuote(): CartInterface
    {
        $quoteCollection = $this->quoteCollectionFactory->create();
        $quoteCollection->setOrder(CartInterface::KEY_ENTITY_ID);
        $quoteCollection->getSelect()->limit(1);
        return $quoteCollection->getLastItem();
    }
}
