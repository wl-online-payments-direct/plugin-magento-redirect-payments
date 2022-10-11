<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\UI;

use Exception;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Worldline\PaymentCore\Model\Ui\PaymentIconsProvider;
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
    private $paymentIconsProvider;

    public function __construct(
        LoggerInterface $logger,
        Config $config,
        StoreManagerInterface $storeManager,
        PaymentProductsConfig $paymentProductsConfig,
        PaymentIconsProvider $paymentIconsProvider
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->paymentProductsConfig = $paymentProductsConfig;
        $this->paymentIconsProvider = $paymentIconsProvider;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        try {
            $paymentProducts = [];
            $store = $this->storeManager->getStore();
            $storeId = (int)$store->getId();

            $isActive = $this->config->isActive($storeId);
            if ($isActive) {
                $scope = $store->getCode();
                $websiteId = $scope === ScopeConfigInterface::SCOPE_TYPE_DEFAULT ? 0 : (int)$store->getWebsiteId();
                $paymentProducts = $this->getSortedPaymentProducts($websiteId, $storeId);
            }

            return [
                'payment' => [
                    self::CODE => [
                        'payment_products' => $paymentProducts,
                        'isActive' => $isActive,
                        'vaultCode' => self::VAULT_CODE
                    ]
                ]
            ];
        } catch (Exception $e) {
            $this->logger->critical($e);
            return [];
        }
    }

    private function getSortedPaymentProducts(int $websiteId, int $storeId): array
    {
        $payProducts = [];
        $activePayProductIds = $this->paymentProductsConfig->getActivePayProductIds($websiteId);
        foreach ($activePayProductIds as $payProductId) {
            $icon = $this->paymentIconsProvider->getIconById($payProductId, $storeId);
            $title = $this->config->getPaymentProductTitle($payProductId);
            $payProducts[self::CODE . '_' . $payProductId] = [
                'sortOrder' => $this->config->getPaymentProductSortOrder($payProductId),
                'config' => [
                    'code' => self::CODE,
                    'title' => $title,
                    'payProduct' => $payProductId,
                    'url' => $icon['url']
                ],
            ];
        }

        uasort($payProducts, function ($a, $b) {
            return $a['sortOrder'] <=> $b['sortOrder'];
        });

        return $payProducts;
    }
}
