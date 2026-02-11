<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Service\CreateHostedCheckoutRequest;

use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\MobilePaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\MobilePaymentMethodSpecificInputFactory;
use OnlinePayments\Sdk\Domain\GPayThreeDSecure;
use OnlinePayments\Sdk\Domain\MobilePaymentProduct320SpecificInput;
use Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest\SpecificInputDataBuilder as HCSpecificInputDataBuilder;
use Worldline\PaymentCore\Api\Config\GeneralSettingsConfigInterface;
use Worldline\PaymentCore\Model\ThreeDSecure\ParamsHandler;
use Worldline\RedirectPayment\Gateway\Config\Config;
use Worldline\RedirectPayment\WebApi\RedirectManagement;
use OnlinePayments\Sdk\Domain\RedirectionData;

class MobilePaymentMethodSpecificInputDataBuilder
{
    const CHALLENGE_INDICATOR_NO_PREFERENCE = 'no-preference';
    const CHALLENGE_INDICATOR_REQUIRED = 'challenge-required';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var MobilePaymentMethodSpecificInputFactory
     */
    private $mobilePaymentMethodSpecificInputFactory;

    /**
     * @var GeneralSettingsConfigInterface
     */
    private $generalSettings;

    /**
     * @param Config $config
     * @param MobilePaymentMethodSpecificInputFactory $mobilePaymentMethodSpecificInputFactory
     * @param GeneralSettingsConfigInterface $generalSettings
     */
    public function __construct(
        Config $config,
        MobilePaymentMethodSpecificInputFactory $mobilePaymentMethodSpecificInputFactory,
        GeneralSettingsConfigInterface $generalSettings
    ) {
        $this->config = $config;
        $this->mobilePaymentMethodSpecificInputFactory = $mobilePaymentMethodSpecificInputFactory;
        $this->generalSettings = $generalSettings;
    }

    public function build(CartInterface $quote): MobilePaymentMethodSpecificInput
    {
        /** @var MobilePaymentMethodSpecificInput $mobilePaymentMethodSpecificInputFactory */
        $mobilePaymentMethodSpecificInput = $this->mobilePaymentMethodSpecificInputFactory->create();
        $mobilePaymentMethodSpecificInput->setAuthorizationMode($this->config->getAuthorizationMode());
        $payProductId = $quote->getPayment()->getAdditionalInformation(RedirectManagement::PAYMENT_PRODUCT_ID);
        if ($payProductId) {
            $mobilePaymentMethodSpecificInput->setPaymentProductId((int)$payProductId);
        }
        $mobilePaymentMethodSpecificInput->setPaymentProduct320SpecificInput(
            $this->buildPaymentProduct320SpecificInput($quote->getStoreId(), (float)$quote->getGrandTotal())
        );

        return $mobilePaymentMethodSpecificInput;
    }

    /**
     * @param int $storeId
     * @param float $baseSubtotalAmount
     * @return MobilePaymentProduct320SpecificInput
     */
    private function buildPaymentProduct320SpecificInput(int $storeId, float $baseSubtotalAmount):
        MobilePaymentProduct320SpecificInput
    {
        $paymentProduct320SpecificInput = new MobilePaymentProduct320SpecificInput();
        $gPayThreeDSecure = new GPayThreeDSecure();

        if (!$this->generalSettings->isThreeDEnabled($storeId)) {
            $gPayThreeDSecure->setSkipAuthentication(true);
        } else {
            $this->configureThreeDSecure($gPayThreeDSecure, $storeId, $baseSubtotalAmount);
            $this->applyRedirectionData($gPayThreeDSecure, $storeId);
        }

        $paymentProduct320SpecificInput->setThreeDSecure($gPayThreeDSecure);

        return $paymentProduct320SpecificInput;
    }

    /**
     * @param GPayThreeDSecure $gPayThreeDSecure
     * @param int $storeId
     * @param float $baseSubtotalAmount
     */
    private function configureThreeDSecure(GPayThreeDSecure $gPayThreeDSecure, int $storeId, float $baseSubtotalAmount):
        void
    {
        if (!$this->generalSettings->isEnforceAuthEnabled($storeId)
            && !$this->generalSettings->isAuthExemptionEnabled($storeId)) {
            $gPayThreeDSecure->setChallengeIndicator(self::CHALLENGE_INDICATOR_NO_PREFERENCE);
            $gPayThreeDSecure->setSkipAuthentication(false);
        } elseif ($this->generalSettings->isEnforceAuthEnabled($storeId)) {
            $gPayThreeDSecure->setChallengeIndicator(self::CHALLENGE_INDICATOR_REQUIRED);
            $gPayThreeDSecure->setSkipAuthentication(false);
        } elseif ($this->generalSettings->isAuthExemptionEnabled($storeId)) {
            $threeDSExemptionType = $this->generalSettings->getAuthExemptionType($storeId);
            $threeDSExemptedAmount = $this->getExemptedAmount($threeDSExemptionType, $storeId);
            $gPayThreeDSecure->setSkipAuthentication(false);

            if ((float)$threeDSExemptedAmount >= $baseSubtotalAmount) {
                $gPayThreeDSecure->setExemptionRequest($threeDSExemptionType);
                $gPayThreeDSecure->setChallengeIndicator($this->resolveChallengeIndicator($threeDSExemptionType));
            }
        }
    }

    /**
     * @param string $type
     * @param int $storeId
     *
     * @return string
     */
    private function getExemptedAmount(string $type, int $storeId): string
    {
        switch ($type) {
            case ParamsHandler::NONE_EXEMPTION_TYPE:
                return $this->generalSettings->getAuthNoChallengeAmount($storeId);

            case ParamsHandler::LOW_VALUE_EXEMPTION_TYPE:
                return $this->generalSettings->getAuthLowValueAmount($storeId);

            case ParamsHandler::TRANSACTION_RISK_ANALYSIS_EXEMPTION_TYPE:
                return $this->generalSettings->getAuthTransactionRiskAnalysisAmount($storeId);

            default:
                return "0";
        }
    }

    /**
     * @param string $type
     *
     * @return string
     */
    private function resolveChallengeIndicator(string $type): string
    {
        return $type === ParamsHandler::TRANSACTION_RISK_ANALYSIS_EXEMPTION_TYPE
            ? ParamsHandler::ANALYSIS_PERFORMED_CHALLENGE_INDICATOR
            : ParamsHandler::NO_CHALLENGE_REQUESTED_CHALLENGE_INDICATOR;
    }

    /**
     * @param GPayThreeDSecure $gPayThreeDSecure
     * @param int $storeId
     */
    private function applyRedirectionData(GPayThreeDSecure $gPayThreeDSecure, int $storeId): void
    {
        $gPayRedirectionData = new RedirectionData();
        $gPayRedirectionData->setReturnUrl(
            $this->generalSettings->getReturnUrl(
                HCSpecificInputDataBuilder::RETURN_URL,
                $storeId
            )
        );

        $gPayThreeDSecure->setRedirectionData($gPayRedirectionData);
    }
}
