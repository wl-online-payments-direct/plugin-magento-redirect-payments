<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Service\Creator\Request;

use Magento\Framework\Locale\Resolver;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInput;
use OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInputFactory;
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
     * @var HostedCheckoutSpecificInputFactory
     */
    private $hostedCheckoutSpecificInputFactory;

    public function __construct(
        Config $config,
        Resolver $store,
        HostedCheckoutSpecificInputFactory $hostedCheckoutSpecificInputFactory
    ) {
        $this->config = $config;
        $this->store = $store;
        $this->hostedCheckoutSpecificInputFactory = $hostedCheckoutSpecificInputFactory;
    }

    public function build(CartInterface $quote): HostedCheckoutSpecificInput
    {
        $hostedCheckoutSpecificInput = $this->hostedCheckoutSpecificInputFactory->create();
        $hostedCheckoutSpecificInput->setLocale($this->store->getLocale());
        $storeId = (int) $quote->getStoreId();

        $hostedCheckoutSpecificInput->setReturnUrl($this->config->getReturnUrl($storeId));
        if ($variant = $this->config->getTemplateId($storeId)) {
            $hostedCheckoutSpecificInput->setVariant($variant);
        }

        return $hostedCheckoutSpecificInput;
    }
}
