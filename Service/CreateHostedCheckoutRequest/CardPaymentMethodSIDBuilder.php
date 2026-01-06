<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Service\CreateHostedCheckoutRequest;

use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInputFactory;
use OnlinePayments\Sdk\Domain\PaymentProduct130SpecificInput;
use OnlinePayments\Sdk\Domain\PaymentProduct130SpecificThreeDSecure;
use Worldline\HostedCheckout\Api\TokenManagerInterface;
use Worldline\PaymentCore\Api\Config\GeneralSettingsConfigInterface;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\PaymentCore\Api\Service\CreateRequest\ThreeDSecureDataBuilderInterface;
use Worldline\PaymentCore\Api\Service\CreateRequest\ThreeDSecureQtyCalculatorInterface;
use Worldline\PaymentCore\Model\ThreeDSecure\ParamsHandler;
use Worldline\RedirectPayment\Gateway\Config\Config;
use Worldline\RedirectPayment\Ui\ConfigProvider;
use Worldline\RedirectPayment\WebApi\RedirectManagement;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CardPaymentMethodSIDBuilder
{
    const CARTE_BANCAIRE_PAYMENT_ID = 130;
    const SINGLE_AMOUNT_USE_CASE = 'single-amount';
    const MAX_SUPPORTED_NUMBER_OF_ITEMS = 99;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CardPaymentMethodSpecificInputFactory
     */
    private $cardPaymentMethodSpecificInputFactory;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var ThreeDSecureDataBuilderInterface
     */
    private $threeDSecureDataBuilder;

    /**
     * @var ThreeDSecureQtyCalculatorInterface
     */
    private $threeDSecureQtyCalculator;

    /**
     * @var int[]
     */
    private $alwaysSaleProductIds;

    /**
     * @var TokenManagerInterface
     */
    private $tokenManager;

    /**
     * @var GeneralSettingsConfigInterface
     */
    private $generalSettings;

    public function __construct(
        Config $config,
        CardPaymentMethodSpecificInputFactory $cardPaymentMethodSpecificInputFactory,
        ManagerInterface $eventManager,
        ThreeDSecureDataBuilderInterface $threeDSecureDataBuilder,
        ThreeDSecureQtyCalculatorInterface $threeDSecureQtyCalculator,
        TokenManagerInterface $tokenManager,
        GeneralSettingsConfigInterface $generalSettings,
        array $alwaysSaleProductIds = []
    ) {
        $this->config = $config;
        $this->cardPaymentMethodSpecificInputFactory = $cardPaymentMethodSpecificInputFactory;
        $this->eventManager = $eventManager;
        $this->threeDSecureDataBuilder = $threeDSecureDataBuilder;
        $this->threeDSecureQtyCalculator = $threeDSecureQtyCalculator;
        $this->alwaysSaleProductIds = $alwaysSaleProductIds;
        $this->tokenManager = $tokenManager;
        $this->generalSettings = $generalSettings;
    }

    public function build(CartInterface $quote): ?CardPaymentMethodSpecificInput
    {
        $storeId = (int)$quote->getStoreId();
        /** @var CardPaymentMethodSpecificInput $cardPaymentMethodSpecificInput */
        $cardPaymentMethodSpecificInput = $this->cardPaymentMethodSpecificInputFactory->create();

        $payProductId = (int)$quote->getPayment()->getAdditionalInformation(RedirectManagement::PAYMENT_PRODUCT_ID);
        if ($payProductId) {
            $cardPaymentMethodSpecificInput->setPaymentProductId($payProductId);
            $this->checkIntersolveGiftCards($cardPaymentMethodSpecificInput, $payProductId, $storeId);
        }

        $cardPaymentMethodSpecificInput->setAuthorizationMode(
            $this->getAuthorizationMode($payProductId, $storeId)
        );

        $cardPaymentMethodSpecificInput->setThreeDSecure($this->threeDSecureDataBuilder->build($quote));

        if ($cardPaymentMethodSpecificInput->getPaymentProductId() === self::CARTE_BANCAIRE_PAYMENT_ID) {
            $paymentProduct130SpecificInput = $this->buildPaymentProduct130SpecificInput($quote);
            if ($paymentProduct130SpecificInput) {
                $cardPaymentMethodSpecificInput->setPaymentProduct130SpecificInput($paymentProduct130SpecificInput);
            }
        }

        if ($token = $this->tokenManager->getToken($quote)) {
            if ($this->tokenManager->isSepaToken($token)) {
                return null;
            }

            $cardPaymentMethodSpecificInput->setToken($token->getGatewayToken());
        }

        $args = ['quote' => $quote, 'card_payment_method_specific_input' => $cardPaymentMethodSpecificInput];
        $this->eventManager->dispatch(ConfigProvider::CODE . '_card_payment_method_specific_input_builder', $args);

        return $cardPaymentMethodSpecificInput;
    }

    private function buildPaymentProduct130SpecificInput(CartInterface $quote): ?PaymentProduct130SpecificInput
    {
        $storeId = (int)$quote->getStoreId();

        if (true === $this->generalSettings->isThreeDEnabled($storeId)) {
            $paymentProduct130SpecificInput = new PaymentProduct130SpecificInput();
            $paymentProduct130ThreeDSecure = new PaymentProduct130SpecificThreeDSecure();

            $paymentProduct130ThreeDSecure->setUsecase(self::SINGLE_AMOUNT_USE_CASE);

            $numberOfItems = min(
                $this->threeDSecureQtyCalculator->calculateNumberOfItems($quote),
                self::MAX_SUPPORTED_NUMBER_OF_ITEMS
            );
            $paymentProduct130ThreeDSecure->setNumberOfItems($numberOfItems);

            if (!$this->generalSettings->isAuthExemptionEnabled($storeId)) {
                $paymentProduct130ThreeDSecure->setAcquirerExemption(false);
            } elseif ($this->generalSettings->isAuthExemptionEnabled($storeId)) {
                $threeDSExemptionType = $this->generalSettings->getAuthExemptionType($storeId);
                $threeDSExemptedAmount = $threeDSExemptionType === ParamsHandler::LOW_VALUE_EXEMPTION_TYPE ?
                    $this->generalSettings->getAuthLowValueAmount($storeId) :
                    $this->generalSettings->getAuthTransactionRiskAnalysisAmount($storeId);

                if ((float)$threeDSExemptedAmount >= (float)$quote->getGrandTotal()) {
                    $paymentProduct130ThreeDSecure->setAcquirerExemption(
                        $threeDSExemptionType === ParamsHandler::TRANSACTION_RISK_ANALYSIS_EXEMPTION_TYPE
                    );
                }
            }
            $paymentProduct130SpecificInput->setThreeDSecure($paymentProduct130ThreeDSecure);

            return $paymentProduct130SpecificInput;
        }

        return null;
    }

    private function getAuthorizationMode(int $payProductId, int $storeId): string
    {
        if (in_array($payProductId, $this->alwaysSaleProductIds, true)) {
            return Config::AUTHORIZATION_MODE_SALE;
        }

        return $this->config->getAuthorizationMode($storeId);
    }

    private function checkIntersolveGiftCards(
        CardPaymentMethodSpecificInput $cardPaymentMethodSpecificInput,
        int $payProductId,
        int $storeId
    ): void {
        if ($payProductId !== PaymentProductsDetailsInterface::INTERSOLVE_PRODUCT_ID) {
            return;
        }

        $giftCards = $this->config->getIntersolveGiftCards($storeId);
        if (!empty($giftCards)) {
            if (count($giftCards) === 1) {
                $cardPaymentMethodSpecificInput->setPaymentProductId((int)$giftCards[0]);
            } else {
                $cardPaymentMethodSpecificInput->setPaymentProductId(null);
            }
        }
    }
}
