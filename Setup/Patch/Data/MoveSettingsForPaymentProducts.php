<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Setup\Patch\Data;

use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Cache\Type\Config as CacheTypeConfig;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;

class MoveSettingsForPaymentProducts implements DataPatchInterface
{
    public const GENERAL_RP_PATH = 'payment/worldline_redirect_payment_';

    /**
     * @var Manager
     */
    private $cacheManager;

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Manager $cacheManager,
        ModuleDataSetupInterface $moduleDataSetup,
        LoggerInterface $logger
    ) {
        $this->cacheManager = $cacheManager;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->logger = $logger;
    }

    public function apply(): MoveSettingsForPaymentProducts
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        if (!$configRows = $this->getRowsToMove()) {
            $connection->endSetup();
            return $this;
        }

        $configRowsToSave = $this->getConfigRowsToSave($configRows);
        $this->saveConfigRows($configRowsToSave);

        $this->cacheManager->clean([CacheTypeConfig::TYPE_IDENTIFIER]);

        $connection->endSetup();
        return $this;
    }

    private function getRowsToMove(): array
    {
        $select = $this->moduleDataSetup->getConnection()
            ->select()
            ->from($this->moduleDataSetup->getTable('core_config_data'))
            ->where('value IS NOT NULL')
            ->where('( path = "payment/worldline_redirect_payment/min_order_total"')
            ->orWhere('path = "payment/worldline_redirect_payment/max_order_total"')
            ->orWhere('path = "payment/worldline_redirect_payment/allowspecific"')
            ->orWhere('path = "payment/worldline_redirect_payment/specificcountry"')
            ->orWhere('path = "payment/worldline_redirect_payment/allow_specific_currency"')
            ->orWhere('path = "payment/worldline_redirect_payment/currency" )');

        return $this->moduleDataSetup->getConnection()->fetchAll($select);
    }

    private function getConfigRowsToSave(array $configRows): array
    {
        $configRowsToSave = [];
        $wlPaymentIds = array_keys(PaymentProductsDetailsInterface::PAYMENT_PRODUCTS);

        foreach ($configRows as $configRow) {
            unset($configRow['config_id'], $configRow['updated_at']);
            $pathDetails = explode('/', $configRow['path']);
            $settingName = end($pathDetails);

            foreach ($wlPaymentIds as $payProductId) {
                $configRow['path'] = self::GENERAL_RP_PATH . $payProductId . '/' . $settingName;
                $configRowsToSave[] = $configRow;
            }
        }

        return $configRowsToSave;
    }

    private function saveConfigRows(array $configRowsToSave): void
    {
        try {
            $this->moduleDataSetup->getConnection()
                ->insertMultiple($this->moduleDataSetup->getTable('core_config_data'), $configRowsToSave);
        } catch (\Exception $exception) {
            $this->logger->warning($exception->getMessage());
        }
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
