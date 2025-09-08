<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Worldline\HostedCheckout\Model\Config\Source\MealvouchersProductTypes;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\PaymentCore\Api\Ui\PaymentIconsProviderInterface;
use Worldline\RedirectPayment\Gateway\Config\Config;

/**
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'worldline_redirect_payment';
    public const VAULT_CODE = 'worldline_redirect_payment_vault';
    public const PRODUCT_TYPE = 'worldline_mealvouchers_product_type';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var PaymentIconsProviderInterface
     */
    private $iconProvider;

    /**
     * @param LoggerInterface $logger
     * @param Config $config
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param PaymentIconsProviderInterface $iconProvider
     */
    public function __construct(
        LoggerInterface $logger,
        Config $config,
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        PaymentIconsProviderInterface $iconProvider
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->iconProvider = $iconProvider;
    }

    /**
     * Returns configuration for active payment products.
     *
     * @return array
     */
    public function getConfig(): array
    {
        try {
            $storeId = (int)$this->storeManager->getStore()->getId();

            if (!$this->config->isActive($storeId)) {
                return [];
            }

            return [
                'payment' => $this->getActivePaymentProducts($storeId)
            ];
        } catch (NoSuchEntityException $e) {
            $this->logger->critical($e);
            return [];
        }
    }

    /**
     * Get active payment products for a store.
     *
     * @param int $storeId
     * @return array
     */
    private function getActivePaymentProducts(int $storeId): array
    {
        $payProducts = [];
        $quote = $this->checkoutSession->getQuote();

        foreach (array_keys(PaymentProductsDetailsInterface::PAYMENT_PRODUCTS) as $payProductId) {
            if (!$this->canActivateProduct($payProductId, $storeId, $quote)) {
                continue;
            }

            $code = self::CODE . '_' . $payProductId;
            $payProducts[$code] = $this->buildPaymentProductData($payProductId, $storeId, $code);
        }

        return $payProducts;
    }

    /**
     * Determine if payment product can be activated.
     *
     * @param int $payProductId
     * @param int $storeId
     * @param Quote $quote
     * @return bool
     */
    private function canActivateProduct(int $payProductId, int $storeId, Quote $quote): bool
    {
        if (!$this->validateBaseConditions($payProductId, $storeId)) {
            return false;
        }

        // Product-specific conditions
        if ($payProductId === PaymentProductsDetailsInterface::CHEQUE_VACANCES_CONNECT_PRODUCT_ID) {
            return $this->isCustomerValid($quote);
        }

        if ($payProductId === PaymentProductsDetailsInterface::MEALVOUCHERS_PRODUCT_ID) {
            return $this->isEligibleForMealVoucher($quote) && $this->isCustomerValid($quote);
        }

        if ($payProductId === PaymentProductsDetailsInterface::SEPA_DIRECT_DEBIT_PRODUCT_ID) {
            return (float)$quote->getGrandTotal() >= 0.00001;
        }

        return true;
    }

    /**
     * Validate base conditions for payment activation (active + icon exists).
     *
     * @param int $payProductId
     * @param int $storeId
     * @return bool
     */
    private function validateBaseConditions(int $payProductId, int $storeId): bool
    {
        return $this->config->isPaymentProductActive($payProductId, $storeId)
            && (bool)$this->iconProvider->getIconById($payProductId, $storeId);
    }

    /**
     * Build data array for an active payment product.
     *
     * @param int $payProductId
     * @param int $storeId
     * @param string $payProductCode
     * @return array
     */
    private function buildPaymentProductData(int $payProductId, int $storeId, string $payProductCode): array
    {
        $data = [
            'isActive' => true,
            'icon' => $this->iconProvider->getIconById($payProductId, $storeId)
        ];

        if ($this->config->isVaultActive($storeId)) {
            $data['vaultCode'] = $payProductCode . '_vault';
        }

        return $data;
    }

    /**
     * Check if customer data is valid (logged in and has email).
     *
     * @param Quote $quote
     * @return bool
     */
    private function isCustomerValid(Quote $quote): bool
    {
        return (bool)$quote->getCustomerId() && (bool)$quote->getCustomerEmail();
    }

    /**
     * Check if any visible items are eligible for mealvouchers.
     *
     * @param Quote $quote
     *
     * @return bool
     */
    private function isEligibleForMealVoucher(Quote $quote): bool
    {
        $eligibleTypes = [
            MealvouchersProductTypes::FOOD_AND_DRINK,
            MealvouchersProductTypes::HOME_AND_GARDEN,
            MealvouchersProductTypes::GIFT_AND_FLOWERS
        ];

        foreach ($quote->getAllVisibleItems() as $item) {
            if (in_array($item->getProduct()->getData(self::PRODUCT_TYPE), $eligibleTypes, true)) {
                return true;
            }
        }

        return false;
    }
}
