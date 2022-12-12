<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Worldline\PaymentCore\Ui\PaymentIconsProvider;
use Worldline\RedirectPayment\Gateway\Config\Config;
use Worldline\RedirectPayment\Model\ResourceModel\PaymentProductsConfig;

class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'worldline_redirect_payment';
    public const VAULT_CODE = 'worldline_redirect_payment_vault';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var PaymentProductsConfig
     */
    private $paymentProductsConfig;

    /**
     * @var PaymentIconsProvider
     */
    private $iconProvider;

    public function __construct(
        LoggerInterface $logger,
        Config $config,
        StoreManagerInterface $storeManager,
        PaymentProductsConfig $paymentProductsConfig,
        PaymentIconsProvider $iconProvider
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->paymentProductsConfig = $paymentProductsConfig;
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
                'payment' => $this->getActivePaymentProducts((int)$store->getWebsiteId(), $storeId)
            ];
        } catch (NoSuchEntityException $e) {
            $this->logger->critical($e);
            return [];
        }
    }

    private function getActivePaymentProducts(int $websiteId, int $storeId): array
    {
        $payProducts = [];
        $activePayProductIds = $this->paymentProductsConfig->getActivePayProductIds($websiteId);
        foreach ($activePayProductIds as $payProductId) {
            if (!$icon = $this->iconProvider->getIconById($payProductId, $storeId)) {
                continue;
            }

            $payProducts[self::CODE . '_' . $payProductId] = [
                'isActive' => true,
                'vaultCode' => self::VAULT_CODE,
                'icon' => $icon
            ];
        }

        return $payProducts;
    }
}
