<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Test\Integration\Settings;

use Magento\Customer\Model\Session;
use Magento\Payment\Model\MethodList;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\PaymentCore\Infrastructure\ActiveVault\FakePaymentToken;
use Worldline\RedirectPayment\Ui\ConfigProvider;

/**
 * Test cases for configuration "Payment enabled/disabled vault"
 */
class ActiveVaultTest extends TestCase
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
     * Steps:
     * 1) Payment enabled=yes
     * 2) Go to checkout
     * Expected result: Payment Method is available
     *
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Customer/_files/customer_address.php
     * @magentoDataFixture Magento/Sales/_files/quote.php
     *
     * @magentoConfigFixture default/currency/options/allow EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_vault/active 1
     * @magentoDbIsolation enabled
     */
    public function testEnabled(): void
    {
        /** @var Session $customerSession */
        $customerSession = Bootstrap::getObjectManager()->get(Session::class);
        $customerSession->loginById(1);

        Bootstrap::getObjectManager()
            ->get(FakePaymentToken::class)
            ->createVaultToken(ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID);
        $quote = $this->getQuote();

        $paymentMethods = $this->methodList->getAvailableMethods($quote);
        $paymentMethodCodes = array_map(static function ($method) {
            return $method->getCode();
        }, $paymentMethods);

        $this->assertContains(
            ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID . '_vault',
            $paymentMethodCodes
        );
    }

    /**
     * Steps:
     * 1) Payment enabled=no
     * 2) Go to checkout
     * Expected result: Payment Method is not available
     *
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Customer/_files/customer_address.php
     * @magentoDataFixture Magento/Sales/_files/quote.php
     *
     * @magentoConfigFixture default/currency/options/allow EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_vault/active 0
     * @magentoDbIsolation enabled
     */
    public function testDisabled(): void
    {
        /** @var Session $customerSession */
        $customerSession = Bootstrap::getObjectManager()->get(Session::class);
        $customerSession->loginById(1);

        Bootstrap::getObjectManager()
            ->get(FakePaymentToken::class)
            ->createVaultToken(ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID);
        $quote = $this->getQuote();

        $paymentMethods = $this->methodList->getAvailableMethods($quote);
        $paymentMethodCodes = array_map(static function ($method) {
            return $method->getCode();
        }, $paymentMethods);

        $this->assertNotContains(
            ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID . '_vault',
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
