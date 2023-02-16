<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Setup\Patch\Data;

use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Cache\Type\Config as CacheTypeConfig;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use Worldline\RedirectPayment\Gateway\Config\Config;

class MoveActiveFieldOfPaymentProducts implements DataPatchInterface
{
    public const METHOD_CODE_KEY = 2;

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

    public function apply(): MoveActiveFieldOfPaymentProducts
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
            ->where('value = 1')
            ->where('path LIKE "worldline_payment/redirect_payment/redirect_payment_%"');

        return $this->moduleDataSetup->getConnection()->fetchAll($select);
    }

    private function getConfigRowsToSave(array $configRows): array
    {
        foreach ($configRows as &$configRow) {
            unset($configRow['config_id'], $configRow['updated_at']);
            $pathDetails = explode('/', $configRow['path']);
            $configRow['path'] = 'payment/worldline_' . $pathDetails[self::METHOD_CODE_KEY] . '/' . Config::KEY_ACTIVE;
        }

        return $configRows;
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
