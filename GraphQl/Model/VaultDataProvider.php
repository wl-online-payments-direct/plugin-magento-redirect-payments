<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\GraphQl\Model;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Worldline\RedirectPayment\Ui\ConfigProvider;

class VaultDataProvider
{
    /**
     * Return Additional Data
     *
     * @param array $args
     * @return array
     * @throws GraphQlInputException
     */
    public function getData(array $args): array
    {
        if (!$args[ConfigProvider::VAULT_CODE]['public_hash']) {
            throw new GraphQlInputException(__('No public_hash provided'));
        }
        return $args[ConfigProvider::VAULT_CODE];
    }
}
