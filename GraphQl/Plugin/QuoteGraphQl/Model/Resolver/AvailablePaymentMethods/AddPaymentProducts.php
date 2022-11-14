<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\GraphQl\Plugin\QuoteGraphQl\Model\Resolver\AvailablePaymentMethods;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Resolver\AvailablePaymentMethods;
use Worldline\PaymentCore\Model\Ui\PaymentIconsProvider;
use Worldline\RedirectPayment\Gateway\Config\Config;
use Worldline\RedirectPayment\Model\ResourceModel\PaymentProductsConfig;
use Worldline\RedirectPayment\UI\ConfigProvider;

class AddPaymentProducts
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var PaymentProductsConfig
     */
    private $paymentProductsConfig;

    /**
     * @var PaymentIconsProvider
     */
    private $paymentIconsProvider;

    public function __construct(
        Config $config,
        PaymentProductsConfig $paymentProductsConfig,
        PaymentIconsProvider $paymentIconsProvider
    ) {
        $this->config = $config;
        $this->paymentProductsConfig = $paymentProductsConfig;
        $this->paymentIconsProvider = $paymentIconsProvider;
    }

    /**
     * @param AvailablePaymentMethods $subject
     * @param array $result
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterResolve(
        AvailablePaymentMethods $subject,
        array $result,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        $isRedirectPaymentExist = false;
        foreach ($result as $key => $paymentMethod) {
            $result[$key]['sortOrder'] = 0;
            if ($paymentMethod['code'] === ConfigProvider::CODE) {
                $isRedirectPaymentExist = true;
                unset($result[$key]);
            }
        }

        if ($isRedirectPaymentExist) {
            $store = $context->getExtensionAttributes()->getStore();
            $scope = $store->getCode();
            $storeId = (int)$store->getId();
            $websiteId = $scope === ScopeConfigInterface::SCOPE_TYPE_DEFAULT ? 0 : (int)$store->getWebsiteId();
            $result = $this->addPaymentProducts($result, $websiteId, $storeId);
            $result = $this->sortPaymentMethods($result);
        }

        return $result;
    }

    private function addPaymentProducts(array $result, int $websiteId, int $storeId): array
    {
        $activePayProductIds = $this->paymentProductsConfig->getActivePayProductIds($websiteId);
        foreach ($activePayProductIds as $payProductId) {
            $icon = $this->paymentIconsProvider->getIconById($payProductId, $storeId);
            if (empty($icon)) {
                continue;
            }

            $title = $this->config->getPaymentProductTitle($payProductId);
            $sortOrder = $this->config->getPaymentProductSortOrder($payProductId);
            $result[] = [
                'title' => $title,
                'sortOrder' => $sortOrder,
                'code' => ConfigProvider::CODE . '_' . $payProductId
            ];
        }

        return $result;
    }

    private function sortPaymentMethods(array $result): array
    {
        uasort($result, function ($a, $b) {
            return $a['sortOrder'] <=> $b['sortOrder'];
        });

        return $result;
    }
}
