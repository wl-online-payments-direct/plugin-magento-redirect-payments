<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Payment\Model\Method\Adapter;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Worldline\PaymentCore\Api\AvailableMethodCheckerInterface;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\RedirectPayment\Service\MealvouchersAvailability;
use Worldline\RedirectPayment\Gateway\Config\Config;
use Worldline\RedirectPayment\Ui\ConfigProvider;

class PaymentMethodIsActive implements ObserverInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var AvailableMethodCheckerInterface
     */
    private $availableMethodChecker;

    /**
     * @var MealvouchersAvailability
     */
    private $mealvouchersAvailability;

    public function __construct(
        Config $config,
        AvailableMethodCheckerInterface $availableMethodChecker,
        MealvouchersAvailability $mealvouchersAvailability
    ) {
        $this->config = $config;
        $this->availableMethodChecker = $availableMethodChecker;
        $this->mealvouchersAvailability = $mealvouchersAvailability;
    }

    public function execute(Observer $observer): void
    {
        /** @var Adapter $methodInstance */
        $methodInstance = $observer->getMethodInstance();
        $quote = $observer->getQuote();
        if ($methodInstance === null
            || $quote === null
            || !$this->config->isActive()
            || !$observer->getResult()->getIsAvailable()
            || strpos($methodInstance->getCode(), ConfigProvider::CODE) === false
        ) {
            return;
        }

        if (!$this->availableMethodChecker->checkIsAvailable($this->config, $quote)) {
            $observer->getResult()->setIsAvailable(false);

            return;
        }

        if (!$this->isMealvouchersAvailable($methodInstance->getCode(), $quote)) {
            $observer->getResult()->setIsAvailable(false);
        }
    }

    /**
     * Mealvouchers carries rules that depend on the billing address, so they cannot be decided
     * in ConfigProvider - that runs when /checkout renders, before the shopper has entered
     * anything. This observer is the first point where the address is populated.
     *
     * @param string $methodCode
     * @param CartInterface $quote
     * @return bool
     */
    private function isMealvouchersAvailable(string $methodCode, CartInterface $quote): bool
    {
        $mealvouchersCode = ConfigProvider::CODE . '_'
            . PaymentProductsDetailsInterface::MEALVOUCHERS_PRODUCT_ID;
        if ($methodCode !== $mealvouchersCode) {
            return true;
        }

        if (!$quote instanceof Quote) {
            return true;
        }

        return $this->mealvouchersAvailability->isAvailable($quote);
    }
}
