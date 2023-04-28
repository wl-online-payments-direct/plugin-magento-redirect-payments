<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Payment\Model\Method\Adapter;
use Worldline\PaymentCore\Api\AvailableMethodCheckerInterface;
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

    public function __construct(
        Config $config,
        AvailableMethodCheckerInterface $availableMethodChecker
    ) {
        $this->config = $config;
        $this->availableMethodChecker = $availableMethodChecker;
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

        $observer->getResult()->setIsAvailable(
            $this->availableMethodChecker->checkIsAvailable($this->config, $quote)
        );
    }
}
