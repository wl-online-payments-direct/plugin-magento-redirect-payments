<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Test\Integration\Settings;

use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Model\MethodList;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\RedirectPayment\Ui\ConfigProvider;

/**
 * Test cases for configuration "sort order"
 */
class SortTest extends TestCase
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
     * 2) Set sort order to 0
     * 3) Go to checkout
     * Expected result: Payment Method is available and sorted properly
     *
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default/currency/options/allow EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/sort_order 0
     * @magentoDbIsolation enabled
     */
    public function testFirstInOrder(): void
    {
        $quote = $this->getQuote();
        $paymentMethods = $this->methodList->getAvailableMethods($quote);
        $hcPaymentMethod = $this->getHCPaymentMethod($paymentMethods);

        $this->assertTrue($hcPaymentMethod instanceof MethodInterface);

        $valid = true;

        foreach ($paymentMethods as $paymentMethod) {
            $methodCode = $paymentMethod->getCode();
            if ($methodCode === ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID) {
                break;
            }

            if ($paymentMethod->getConfigData('sort_order') > $hcPaymentMethod->getConfigData('sort_order')) {
                $valid = false;
            }
        }

        $this->assertTrue($valid);
    }

    private function getHCPaymentMethod(array $paymentMethods): ?MethodInterface
    {
        $result = null;

        foreach ($paymentMethods as $paymentMethod) {
            $methodCode = $paymentMethod->getCode();
            if ($methodCode === ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::VISA_PRODUCT_ID) {
                $result = $paymentMethod;
                break;
            }
        }

        return $result;
    }

    private function getQuote(): CartInterface
    {
        $quoteCollection = $this->quoteCollectionFactory->create();
        $quoteCollection->setOrder(CartInterface::KEY_ENTITY_ID);
        $quoteCollection->getSelect()->limit(1);
        return $quoteCollection->getLastItem();
    }
}
