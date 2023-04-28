<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Config\Config as PaymentGatewayConfig;
use Magento\Store\Model\ScopeInterface;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;

class Config extends PaymentGatewayConfig
{
    public const KEY_ACTIVE = 'active';
    public const AUTHORIZATION_MODE = 'authorization_mode';
    public const PAYMENT_ACTION = 'payment_action';
    public const AUTHORIZATION_MODE_FINAL = 'FINAL_AUTHORIZATION';
    public const AUTHORIZATION_MODE_PRE = 'PRE_AUTHORIZATION';
    public const AUTHORIZATION_MODE_SALE = 'SALE';
    public const AUTHORIZE_CAPTURE = 'authorize_capture';
    public const TEMPLATE_ID = 'template_id';
    public const KEY_CART_LINES = 'cart_lines';

    public const REDIRECT_PAYMENT_PATH = "payment/worldline_redirect_payment_";
    public const VAULT_ACTIVE = 'payment/worldline_redirect_payment_vault/active';

    public const DIRECT_DEBIT_RECURRENCE_TYPE = 'payment/worldline_redirect_payment_771/direct_debit_recurrence_type';
    public const DIRECT_DEBIT_SIGNATURE_TYPE = 'payment/worldline_redirect_payment_771/direct_debit_signature_type';

    public const ONEY_PAYMENT_OPTION = 'payment/worldline_redirect_payment_5110/oney3x4x_payment_option';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $methodCode = null,
        $pathPattern = PaymentGatewayConfig::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->scopeConfig = $scopeConfig;
    }

    public function isActive(?int $storeId = null): bool
    {
        return (bool) $this->getValue(self::KEY_ACTIVE, $storeId);
    }

    public function isVaultActive(?int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::VAULT_ACTIVE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getAuthorizationMode(?int $storeId = null): string
    {
        if ($this->getValue(self::PAYMENT_ACTION, $storeId) === self::AUTHORIZE_CAPTURE) {
            return self::AUTHORIZATION_MODE_SALE;
        }

        $authorizationMode = (string) $this->getValue(self::AUTHORIZATION_MODE, $storeId);
        if ($authorizationMode === 'pre') {
            return self::AUTHORIZATION_MODE_PRE;
        }

        return self::AUTHORIZATION_MODE_FINAL;
    }

    public function getTemplateId(?int $storeId = null): string
    {
        return (string) $this->getValue(self::TEMPLATE_ID, $storeId);
    }

    public function isPaymentProductActive(int $payProductId, ?int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(
            self::REDIRECT_PAYMENT_PATH . $payProductId . '/' . self::KEY_ACTIVE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isCartLines(?int $storeId = null): bool
    {
        return (bool) $this->getValue(self::KEY_CART_LINES, $storeId);
    }

    public function isProcessMealvouchers(?int $storeId = null): bool
    {
        return $this->isPaymentProductActive(PaymentProductsDetailsInterface::MEALVOUCHERS_PRODUCT_ID, $storeId);
    }

    public function getDirectDebitRecurrenceType(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::DIRECT_DEBIT_RECURRENCE_TYPE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getDirectDebitSignatureType(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::DIRECT_DEBIT_SIGNATURE_TYPE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getOneyPaymentOption(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::ONEY_PAYMENT_OPTION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
