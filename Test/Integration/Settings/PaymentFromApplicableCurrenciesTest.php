<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Test\Integration\Settings;

use Magento\Payment\Model\MethodList;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\RedirectPayment\Ui\ConfigProvider;

/**
 * Test cases for configuration "Payment from Applicable Currencies"
 */
class PaymentFromApplicableCurrenciesTest extends TestCase
{
    /**
     * @var MethodList
     */
    private $methodList;

    /**
     * @var QuoteCollectionFactory
     */
    private $quoteCollectionFactory;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->methodList = $objectManager->get(MethodList::class);
        $this->quoteCollectionFactory = $objectManager->get(QuoteCollectionFactory::class);
    }

    /**
     * Test the selected specific currencies setting
     *
     * Steps:
     * 1) Payment from Applicable Currencies=Specific Currencies
     * 2) In multiselect choose EUR
     * 3) Go to checkout with EUR currency
     * Expected result: Payment Method is available
     * 4) Change your currency on USD
     * Expected result: Payment Method is NOT available
     *
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/allow_specific_currency 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/currency EUR
     *
     * @magentoDbIsolation enabled
     */
    public function testPaymentFromApplicableCurrencies(): void
    {
        $quote = $this->getQuote();
        $quote->getPayment()->setMethod(ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID);

        $paymentMethods = $this->methodList->getAvailableMethods($quote);
        $paymentMethodCodes = array_map(static function ($method) {
            return $method->getCode();
        }, $paymentMethods);

        $this->assertContains(
            ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID,
            $paymentMethodCodes
        );
    }

    /**
     * Test the selected specific currencies setting
     *
     * Steps:
     * 1) Payment from Applicable Currencies=Specific Currencies
     * 2) In multiselect choose EUR
     * 3) Go to checkout with EUR currency
     * Expected result: Payment Method is available
     * 4) Change your currency on USD
     * Expected result: Payment Method is NOT available
     *
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default/currency/options/base USD
     * @magentoConfigFixture default/currency/options/default USD
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/allow_specific_currency 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/currency EUR
     *
     * @magentoDbIsolation enabled
     */
    public function testPaymentFromApplicableCurrencies2(): void
    {
        $quote = $this->getQuote();
        $quote->getPayment()->setMethod(ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID);

        $paymentMethods = $this->methodList->getAvailableMethods($quote);
        $paymentMethodCodes = array_map(static function ($method) {
            return $method->getCode();
        }, $paymentMethods);

        $this->assertNotContains(
            ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID,
            $paymentMethodCodes
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
