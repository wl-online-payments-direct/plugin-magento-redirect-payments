<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Gateway\Config;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

/**
 * Resolves a payment method config value through Magento's translation dictionary.
 *
 * Used for the "title" field only, so the storefront checkout label follows the
 * shopper's store-view locale while the admin-configured value stays untranslated.
 */
class TranslatedTitleValueHandler implements ValueHandlerInterface
{
    /**
     * @var ConfigInterface
     */
    private $configInterface;

    public function __construct(ConfigInterface $configInterface)
    {
        $this->configInterface = $configInterface;
    }

    /**
     * @inheritdoc
     */
    public function handle(array $subject, $storeId = null)
    {
        $value = $this->configInterface->getValue(SubjectReader::readField($subject), $storeId);

        return (string) __($value);
    }
}
