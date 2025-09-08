<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Service\CreateHostedCheckoutRequest;

use Magento\Framework\Locale\Resolver;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInput;
use OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInputFactory;
use OnlinePayments\Sdk\Domain\PaymentProductFilter;
use OnlinePayments\Sdk\Domain\PaymentProductFilterFactory;
use OnlinePayments\Sdk\Domain\PaymentProductFiltersHostedCheckout;
use OnlinePayments\Sdk\Domain\PaymentProductFiltersHostedCheckoutFactory;
use Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest\SpecificInputDataBuilder as HCSpecificInputDataBuilder;
use Worldline\PaymentCore\Api\Config\GeneralSettingsConfigInterface;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\RedirectPayment\Gateway\Config\Config;
use Worldline\RedirectPayment\WebApi\RedirectManagement;

class SpecificInputDataBuilder
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Resolver
     */
    private $store;

    /**
     * @var GeneralSettingsConfigInterface
     */
    private $generalSettings;

    /**
     * @var HostedCheckoutSpecificInputFactory
     */
    private $hostedCheckoutSpecificInputFactory;

    /**
     * @var PaymentProductFilterFactory
     */
    private $paymentProductFilterFactory;

    /**
     * @var PaymentProductFiltersHostedCheckoutFactory
     */
    private $paymentProductFiltersHCFactory;

    public function __construct(
        Config $config,
        Resolver $store,
        GeneralSettingsConfigInterface $generalSettings,
        HostedCheckoutSpecificInputFactory $hostedCheckoutSpecificInputFactory,
        PaymentProductFilterFactory $paymentProductFilterFactory,
        PaymentProductFiltersHostedCheckoutFactory $paymentProductFiltersHCFactory
    ) {
        $this->config = $config;
        $this->store = $store;
        $this->generalSettings = $generalSettings;
        $this->hostedCheckoutSpecificInputFactory = $hostedCheckoutSpecificInputFactory;
        $this->paymentProductFilterFactory = $paymentProductFilterFactory;
        $this->paymentProductFiltersHCFactory = $paymentProductFiltersHCFactory;
    }

    public function build(CartInterface $quote): HostedCheckoutSpecificInput
    {
        $hostedCheckoutSpecificInput = $this->hostedCheckoutSpecificInputFactory->create();
        $hostedCheckoutSpecificInput->setLocale($this->store->getLocale());
        $storeId = (int) $quote->getStoreId();

        $sessionTimeout = $this->config->getSessionTimeout($storeId);
        if ($sessionTimeout) {
            $hostedCheckoutSpecificInput->setSessionTimeout($sessionTimeout);
        }

        $hostedCheckoutSpecificInput->setReturnUrl(
            $this->generalSettings->getReturnUrl(HCSpecificInputDataBuilder::RETURN_URL, $storeId)
        );
        if ($variant = $this->config->getTemplateId($storeId)) {
            $hostedCheckoutSpecificInput->setVariant($variant);
        }

        $payProductId = (int)$quote->getPayment()->getAdditionalInformation(RedirectManagement::PAYMENT_PRODUCT_ID);
        if ($payProductId && $payProductId === PaymentProductsDetailsInterface::INTERSOLVE_PRODUCT_ID) {
            $giftCards = $this->config->getIntersolveGiftCards($storeId);
            if (!empty($giftCards) && count($giftCards) > 1) {
                /** @var PaymentProductFilter $paymentProductFilter */
                $paymentProductFilter = $this->paymentProductFilterFactory->create();
                $paymentProductFilter->setProducts($giftCards);

                /** @var PaymentProductFilter $paymentProductFilterMealVoucher */
                $paymentProductFilterMealVoucher = $this->paymentProductFilterFactory->create();
                $paymentProductFilterMealVoucher->setProducts([
                    PaymentProductsDetailsInterface::MEALVOUCHERS_PRODUCT_ID
                ]);

                /** @var PaymentProductFiltersHostedCheckout $paymentProductFiltersHC */
                $paymentProductFiltersHC = $this->paymentProductFiltersHCFactory->create();
                $paymentProductFiltersHC->setRestrictTo($paymentProductFilter);
                $paymentProductFiltersHC->setExclude($paymentProductFilterMealVoucher);

                $hostedCheckoutSpecificInput->setPaymentProductFilters($paymentProductFiltersHC);
            }
        }

        return $hostedCheckoutSpecificInput;
    }
}
