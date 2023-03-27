<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Vault;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\ConfigFactoryInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\Method\Vault as MagentoVault;
use Worldline\RedirectPayment\Gateway\Config\Config;
use Worldline\PaymentCore\Api\VaultValidationInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Vault extends MagentoVault
{
    /**
     * @var VaultValidationInterface
     */
    private $vaultValidation;

    /**
     * @var Config
     */
    private $configProvider;

    public function __construct(
        ConfigInterface $config,
        ConfigFactoryInterface $configFactory,
        ObjectManagerInterface $objectManager,
        MethodInterface $vaultProvider,
        ManagerInterface $eventManager,
        ValueHandlerPoolInterface $valueHandlerPool,
        Command\CommandManagerPoolInterface $commandManagerPool,
        PaymentTokenManagementInterface $tokenManagement,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        VaultValidationInterface $vaultValidation,
        Config $configProvider,
        string $code
    ) {
        parent::__construct(
            $config,
            $configFactory,
            $objectManager,
            $vaultProvider,
            $eventManager,
            $valueHandlerPool,
            $commandManagerPool,
            $tokenManagement,
            $paymentExtensionFactory,
            $code
        );
        $this->vaultValidation = $vaultValidation;
        $this->configProvider = $configProvider;
    }

    /**
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(CartInterface $quote = null): bool
    {
        if ($quote === null) {
            return parent::isAvailable($quote);
        }

        if ($quote->getCustomerIsGuest()) {
            return false;
        }

        if (!$this->configProvider->isVaultActive()) {
            return false;
        }

        $paymentCode = str_replace('_vault', '', $this->getCode());
        if (!$this->vaultValidation->customerHasTokensValidation($quote, $paymentCode)) {
            return false;
        }

        return parent::isAvailable($quote);
    }
}
