<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Test\Integration\Settings;

use Magento\Quote\Api\Data\CartInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\RedirectPayment\Service\HostedCheckout\CreateHostedCheckoutRequestBuilder;
use Worldline\RedirectPayment\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\PaymentCore\Api\QuoteResourceInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\ServiceStubSwitcherInterface;
use Worldline\RedirectPayment\WebApi\RedirectManagement;

/**
 * Test case for configuration "Oney3x4x payment option"
 */
class Oney3x4xPaymentOptionTest extends TestCase
{
    /**
     * @var QuoteResourceInterface
     */
    private $quoteExtendedRepository;

    /**
     * @var CreateHostedCheckoutRequestBuilder
     */
    private $createRequestBuilder;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->quoteExtendedRepository = $objectManager->get(QuoteResourceInterface::class);
        $this->createRequestBuilder = $objectManager->get(CreateHostedCheckoutRequestBuilder::class);
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
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_5110/active 1
     * @magentoConfigFixture current_store payment/worldline_redirect_payment_5110/oney3x4x_payment_option W3999
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/payment_action authorize
     * @magentoConfigFixture current_store payment/worldline_redirect_payment/authorization_mode final
     * @magentoConfigFixture current_store worldline_connection/webhook/key test-X-Gcs-Keyid
     * @magentoConfigFixture current_store worldline_connection/webhook/secret_key test-X-Gcs-Signature
     */
    public function testOneyPaymentOption(): void
    {
        $quote = $this->getQuote();

        $request = $this->createRequestBuilder->build($quote);
        $redirectPaymentMethodSI = $request->getRedirectPaymentMethodSpecificInput();

        $this->assertTrue($redirectPaymentMethodSI->getRequiresApproval());
        $this->assertEquals('W3999', $redirectPaymentMethodSI->getPaymentOption());
        $this->assertEquals(
            PaymentProductsDetailsInterface::ONEY_3X_4X_PRODUCT_ID,
            $redirectPaymentMethodSI->getPaymentProductId()
        );
    }

    private function getQuote(): CartInterface
    {
        $quote = $this->quoteExtendedRepository->getQuoteByReservedOrderId('test01');
        $quote->setCustomerId(1);
        $quote->getPayment()->setMethod(
            ConfigProvider::CODE . '_' . PaymentProductsDetailsInterface::ONEY_3X_4X_PRODUCT_ID
        );
        $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->getShippingAddress()->collectShippingRates();
        $quote->setCustomerEmail('example@worldline.com');
        $quote->getPayment()->setAdditionalInformation('payment_id', '3254564310_0');
        $quote->getPayment()->setAdditionalInformation('token_id', 'test');
        $quote->getPayment()->setAdditionalInformation(
            RedirectManagement::PAYMENT_PRODUCT_ID,
            PaymentProductsDetailsInterface::ONEY_3X_4X_PRODUCT_ID
        );
        $quote->collectTotals();
        $quote->save();

        return $quote;
    }
}
