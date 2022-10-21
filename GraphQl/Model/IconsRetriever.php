<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\GraphQl\Model;

use Worldline\PaymentCore\Model\Ui\PaymentIconsProvider;
use Worldline\PaymentCore\GraphQl\Model\PaymentIcons\IconsRetrieverInterface;

class IconsRetriever implements IconsRetrieverInterface
{
    /**
     * @var PaymentIconsProvider
     */
    private $iconProvider;

    public function __construct(PaymentIconsProvider $iconProvider)
    {
        $this->iconProvider = $iconProvider;
    }

    /**
     * @param string $code
     * @param string $originalCode
     * @param int $storeId
     * @return array[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getIcons(string $code, string $originalCode, int $storeId): array
    {
        $offset = strrpos($originalCode, '_') + 1;
        $payProductId = (int)substr($originalCode, $offset);

        $icon = $this->iconProvider->getIconById($payProductId, $storeId);

        return [
            [
                IconsRetrieverInterface::ICON_TITLE => $icon['title'],
                IconsRetrieverInterface::ICON_URL => $icon['url']
            ]
        ];
    }
}
