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
 * Test cases for configuration "Payment from Applicable Customer Groups"
 */
class PaymentFromApplicableCustomerGroupsTest extends TestCase
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
     * Test the selected specific customer groups setting
     *
     * Steps:
     * 1) Payment from Applicable Customer Groups=Specific Customer Groups
     * 2) In multiselect choose General
     * 3) Go to checkout as Logged Customer (General)
     * Expected result: Payment Method is available
     * 4) Change your Customer Group
     * Expected result: Payment Method is NOT available
     *
     * @magentoDataFixture Magento/Customer/_files/customer_group.php
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     * @magentoConfigFixture current_store currency/options/allow EUR
     * @magentoConfigFixture current_store currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/payment_action authorize_capture
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/allow_specific_customer_group 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/allow_specific_currency 0
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/customer_group 1
     * @magentoDbIsolation disabled
     */
    public function testPaymentFromApplicableCustomerGroups(): void
    {
        $quote = $this->getQuote(); // the quote has default customer group - General

        // change customer group
        $quote->getCustomer()->setGroupId(1);

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
     * Test the selected specific customer groups setting
     *
     * Steps:
     * 1) Payment from Applicable Customer Groups=Specific Customer Groups
     * 2) In multiselect choose General
     * 3) Go to checkout as Logged Customer (General)
     * Expected result: Payment Method is available
     * 4) Change your Customer Group
     * Expected result: Payment Method is NOT available
     *
     * @magentoDataFixture Magento/Customer/_files/customer_group.php
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     * @magentoConfigFixture current_store currency/options/allow EUR
     * @magentoConfigFixture current_store currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/payment_action authorize_capture
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/allow_specific_customer_group 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/allow_specific_currency 0
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/customer_group 1
     * @magentoDbIsolation disabled
     */
    public function testPaymentFromApplicableCustomerGroups2(): void
    {
        $quote = $this->getQuote(); // the quote has default customer group - General

        // change customer group
        $quote->getCustomer()->setGroupId(55555);

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
