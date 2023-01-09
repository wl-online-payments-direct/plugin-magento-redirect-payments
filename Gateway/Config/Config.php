<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Gateway\Config;

use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Config\Config as PaymentGatewayConfig;
use Magento\Store\Model\ScopeInterface;

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
    public const PWA_ROUTE = 'pwa_route';
    public const KEY_CART_LINES = 'cart_lines';
    public const SKIP_3D = 'skip_3d';
    public const AUTHENTICATION_TRIGGER = 'authentication_trigger';
    public const THREE_D_EXEMPTION = '3d_exemption';

    public const TITLE = "title";
    public const SORT = "sort_order";
    public const REDIRECT_PAYMENT_PATH = "payment/worldline_redirect_payment_";
    public const REDIRECT_PAYMENT_PRODUCT_PATH = "worldline_payment/redirect_payment/redirect_payment_";

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        UrlInterface $urlBuilder,
        State $appState,
        $methodCode = null,
        $pathPattern = PaymentGatewayConfig::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->urlBuilder = $urlBuilder;
        $this->appState = $appState;
        $this->scopeConfig = $scopeConfig;
    }

    public function isActive(?int $storeId = null): bool
    {
        return (bool) $this->getValue(self::KEY_ACTIVE, $storeId);
    }

    public function getAuthorizationMode(?int $storeId = null): string
    {
        if ($this->getValue(self::PAYMENT_ACTION, $storeId) === self::AUTHORIZE_CAPTURE) {
            return self::AUTHORIZATION_MODE_SALE;
        }

        $authorizationMode = (string) $this->getValue(self::AUTHORIZATION_MODE, $storeId);
        switch ($authorizationMode) {
            case 'pre':
                return self::AUTHORIZATION_MODE_PRE;
            default:
                return self::AUTHORIZATION_MODE_FINAL;
        }
    }

    public function getTemplateId(?int $storeId = null): string
    {
        return (string) $this->getValue(self::TEMPLATE_ID, $storeId);
    }

    public function getReturnUrl(?int $storeId = null): string
    {
        $pwaRoute = (string) $this->getValue(self::PWA_ROUTE, $storeId);
        if ($pwaRoute && $this->appState->getAreaCode() === Area::AREA_GRAPHQL) {
            return $pwaRoute;
        }

        return $this->urlBuilder->getUrl('wl_hostedcheckout/returns/returnUrl');
    }

    public function isPaymentProductActive(int $payProductId, ?int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(
            self::REDIRECT_PAYMENT_PRODUCT_PATH . $payProductId . '/' . self::KEY_ACTIVE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getPaymentProductTitle(int $payProductId, ?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::REDIRECT_PAYMENT_PATH . $payProductId . '/' . self::TITLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getPaymentProductSortOrder(int $payProductId, ?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::REDIRECT_PAYMENT_PATH . $payProductId . '/' . self::SORT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isCartLines(?int $storeId = null): bool
    {
        return (bool) $this->getValue(self::KEY_CART_LINES, $storeId);
    }

    public function hasSkipAuthentication(?int $storeId = null): bool
    {
        return (bool) $this->getValue(self::SKIP_3D, $storeId);
    }

    public function isTriggerAnAuthentication(?int $storeId = null): bool
    {
        return (bool) $this->getValue(self::AUTHENTICATION_TRIGGER, $storeId);
    }

    public function isThreeDExemptionEnabled(?int $storeId = null): bool
    {
        return (bool) $this->getValue(self::THREE_D_EXEMPTION, $storeId);
    }
}
