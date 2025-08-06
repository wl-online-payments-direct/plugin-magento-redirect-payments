<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
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

    public function getConfig(): array
    {
        try {
            $store = $this->storeManager->getStore();
            $storeId = (int)$store->getId();

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

    private function getActivePaymentProducts(int $storeId): array
    {
        $payProducts = [];
        $quote = $this->checkoutSession->getQuote();
        $paymentProductIds = array_keys(PaymentProductsDetailsInterface::PAYMENT_PRODUCTS);
        foreach ($paymentProductIds as $payProductId) {
            if (!$this->config->isPaymentProductActive($payProductId, $storeId)
                || !$this->iconProvider->getIconById($payProductId, $storeId)
            ) {
                continue;
            }

            if ($payProductId === PaymentProductsDetailsInterface::CHEQUE_VACANCES_CONNECT_PRODUCT_ID) {
                if (!$this->isCustomerDataValid()) {
                    continue;
                }
            }

            if ($payProductId === PaymentProductsDetailsInterface::MEALVOUCHERS_PRODUCT_ID) {
                if (!$this->isEligibleForMealVoucher() || !$this->isCustomerDataValid()) {
                    continue;
                }
            }

            if ($payProductId === PaymentProductsDetailsInterface::SEPA_DIRECT_DEBIT_PRODUCT_ID
                && (float)$quote->getGrandTotal() < 0.00001
            ) {
                continue;
            }

            $payProductCode = self::CODE . '_' . $payProductId;
            $payProducts[$payProductCode] = [
                'isActive' => true,
                'icon' => $this->iconProvider->getIconById($payProductId, $storeId)
            ];

            if ($this->config->isVaultActive($storeId)) {
                $payProducts[$payProductCode]['vaultCode'] = $payProductCode . '_' . 'vault';
            }
        }

        return $payProducts;
    }

    /**
     * @return bool
     */
    private function isEligibleForMealVoucher()
    {
        foreach ($this->checkoutSession->getQuote()->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $productType = $product->getData(self::PRODUCT_TYPE);
            if (in_array($productType, $this->getEligibleProductTypes())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate customer eligibility for using the Mealvoucher payment method.
     *
     * @return bool
     *
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function isCustomerDataValid()
    {
        return $this->checkoutSession->getQuote()->getData()['customer_id'] &&
            $this->checkoutSession->getQuote()->getData()['customer_email'];
    }

    /**
     * Return eligible product types for meal vouchers
     *
     * @return string[]
     */
    private function getEligibleProductTypes()
    {
        return array(
            MealvouchersProductTypes::FOOD_AND_DRINK,
            MealvouchersProductTypes::HOME_AND_GARDEN,
            MealvouchersProductTypes::GIFT_AND_FLOWERS
        );
    }
}
