<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Service\CreateHostedCheckoutRequest;

use Magento\Framework\Locale\Resolver;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInput;
use OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInputFactory;
use Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest\SpecificInputDataBuilder as HCSpecificInputDataBuilder;
use Worldline\PaymentCore\Model\Config\GeneralSettingsConfig;
use Worldline\RedirectPayment\Gateway\Config\Config;

class SpecificInputDataBuilder
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Resolver
     */
    private $store;

    /**
     * @var GeneralSettingsConfig
     */
    private $generalSettings;

    /**
     * @var HostedCheckoutSpecificInputFactory
     */
    private $hostedCheckoutSpecificInputFactory;

    public function __construct(
        Config $config,
        Resolver $store,
        GeneralSettingsConfig $generalSettings,
        HostedCheckoutSpecificInputFactory $hostedCheckoutSpecificInputFactory
    ) {
        $this->config = $config;
        $this->store = $store;
        $this->generalSettings = $generalSettings;
        $this->hostedCheckoutSpecificInputFactory = $hostedCheckoutSpecificInputFactory;
    }

    public function build(CartInterface $quote): HostedCheckoutSpecificInput
    {
        $hostedCheckoutSpecificInput = $this->hostedCheckoutSpecificInputFactory->create();
        $hostedCheckoutSpecificInput->setLocale($this->store->getLocale());
        $storeId = (int) $quote->getStoreId();

        $hostedCheckoutSpecificInput->setReturnUrl(
            $this->generalSettings->getReturnUrl(HCSpecificInputDataBuilder::RETURN_URL, $storeId)
        );
        if ($variant = $this->config->getTemplateId($storeId)) {
            $hostedCheckoutSpecificInput->setVariant($variant);
        }

        return $hostedCheckoutSpecificInput;
    }
}
