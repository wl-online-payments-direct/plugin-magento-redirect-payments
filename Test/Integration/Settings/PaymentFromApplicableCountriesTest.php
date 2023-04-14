<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Test\Integration\Settings;

use Magento\Payment\Model\MethodList;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for configuration "Payment from Applicable Countries"
 */
class PaymentFromApplicableCountriesTest extends TestCase
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
     * Test the selected specific countries setting
     *
     * Steps:
     * 1) Payment from Applicable Countries=Specific Countries
     * 2) In multiselect choose AF
     * 3) Go to checkout with AF address
     * Expected result: Payment Method is available
     * 4) Change your address on DZ
     * Expected result: Payment Method is NOT available
     *
     * @dataProvider testPaymentFromApplicableCountriesDataProvider
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default/currency/options/allow EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/payment_action authorize_capture
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/allowspecific 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_1/specificcountry AF
     * @magentoDbIsolation enabled
     */
    public function testPaymentFromApplicableCountries(string $specificCountry, int $expectedDelta): void
    {
        $quote = $this->getQuote(); // the billing address has default country id value - US

        // count numbers of available payment methods
        $numberOfPaymentMethodsBeforeChangingCountry = count($this->methodList->getAvailableMethods($quote));

        // change country id
        $quote->getBillingAddress()->setCountryId($specificCountry);

        // count numbers of available payment methods after the country has been changed
        $numberOfPaymentMethodsAfterChangingCountry = $this->methodList->getAvailableMethods($quote);

        $this->assertCount(
            $numberOfPaymentMethodsBeforeChangingCountry + $expectedDelta,
            $numberOfPaymentMethodsAfterChangingCountry
        );
    }

    private function getQuote(): CartInterface
    {
        $quoteCollection = $this->quoteCollectionFactory->create();
        $quoteCollection->setOrder(CartInterface::KEY_ENTITY_ID);
        $quoteCollection->getSelect()->limit(1);
        return $quoteCollection->getLastItem();
    }

    public function testPaymentFromApplicableCountriesDataProvider(): array
    {
        return [
            [
                'AF',
                1
            ],
            [
                'DZ',
                0
            ]
        ];
    }
}
