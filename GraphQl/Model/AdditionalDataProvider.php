<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\GraphQl\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\QuoteGraphQl\Model\Cart\Payment\AdditionalDataProviderInterface;
use Worldline\RedirectPayment\Ui\ConfigProvider;
use Worldline\RedirectPayment\WebApi\RedirectManagement;

class AdditionalDataProvider implements AdditionalDataProviderInterface
{
    public const XML_PATH_PAYMENT_WORLDLINE_RP_VAULT_ACTIVE = "payment/worldline_redirect_payment_vault/active";

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param array $data
     * @return array
     * @throws GraphQlInputException
     */
    public function getData(array $data): array
    {
        if (!isset($data['code'])) {
            throw new GraphQlInputException(
                __('Required parameter "code" for "payment_method" is missing.')
            );
        }

        $result['code'] = $data['code'];
        $result['is_active_payment_token_enabler'] = $this->isRPVaultEnable();

        if (!empty($data[ConfigProvider::CODE][RedirectManagement::PAYMENT_PRODUCT_ID])) {
            $result[RedirectManagement::PAYMENT_PRODUCT_ID]
                = $data[ConfigProvider::CODE][RedirectManagement::PAYMENT_PRODUCT_ID];
        }

        return $result;
    }

    /**
     * @return bool
     */
    private function isRPVaultEnable(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_PAYMENT_WORLDLINE_RP_VAULT_ACTIVE);
    }
}
