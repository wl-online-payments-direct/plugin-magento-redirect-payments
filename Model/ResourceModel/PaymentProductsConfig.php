<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Model\ResourceModel;

use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

class PaymentProductsConfig
{
    public const ACTIVE_KEY_LENGTH = -7;

    /**
     * @var CollectionFactory
     */
    private $configCollectionFactory;

    public function __construct(CollectionFactory $configCollectionFactory)
    {
        $this->configCollectionFactory = $configCollectionFactory;
    }

    public function getActivePayProductIds(int $websiteId): array
    {
        $payProductItems = $this->getPayProductItems($websiteId);
        if (empty($payProductItems) && $websiteId != 0) {
            $payProductItems = $this->getPayProductItems(0);
        }

        $payProductIds = [];
        foreach ($payProductItems as $item) {
            $path = $item->getData('path');
            $offset = strrpos($path, '_') + 1;
            $payProductIds[] = (int)substr($path, $offset, self::ACTIVE_KEY_LENGTH);
        }

        return $payProductIds;
    }

    private function getPayProductItems(int $scopeId): array
    {
        $configCollection = $this->configCollectionFactory->create();
        $configCollection
            ->addFieldToFilter('value', 1)
            ->addFieldToFilter('scope_id', $scopeId)
            ->addFieldToFilter('path', ['like' => 'worldline_payment/redirect_payment/redirect_payment_%/active']);

        return $configCollection->getItems();
    }
}
