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

class EnableModule extends TestCase
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
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store currency/options/allow EUR
     * @magentoConfigFixture current_store currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_863/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/payment_action authorize_capture
     */
    public function testEnableModule(): void
    {
        $quote = $this->getQuote();
        $availableMethods = $this->methodList->getAvailableMethods($quote);
        $method = end($availableMethods);
        $this->assertEquals(
            ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::WECHAT_PAY_PRODUCT_ID,
            $method->getCode()
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store currency/options/allow EUR
     * @magentoConfigFixture current_store currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_863/active 0
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/active 0
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/payment_action authorize_capture
     */
    public function testDisableModule(): void
    {
        $quote = $this->getQuote();
        $availableMethods = $this->methodList->getAvailableMethods($quote);
        $method = end($availableMethods);
        $this->assertNotEquals(
            ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::WECHAT_PAY_PRODUCT_ID,
            $method->getCode()
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
