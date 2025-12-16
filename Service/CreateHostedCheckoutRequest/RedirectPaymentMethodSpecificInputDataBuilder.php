<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Service\CreateHostedCheckoutRequest;

use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoreManagerInterface;
use OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInputFactory;
use OnlinePayments\Sdk\Domain\RedirectPaymentProduct5402SpecificInputFactory;
use OnlinePayments\Sdk\Domain\RedirectPaymentProduct5408SpecificInputFactory;
use OnlinePayments\Sdk\Domain\RedirectPaymentProduct5403SpecificInputFactory;
use OnlinePayments\Sdk\Domain\RedirectPaymentProduct5300SpecificInputFactory;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\RedirectPayment\Gateway\Config\Config;
use Worldline\RedirectPayment\Ui\ConfigProvider;
use Worldline\RedirectPayment\WebApi\RedirectManagement;

class RedirectPaymentMethodSpecificInputDataBuilder
{
    public const RP_METHOD_SPECIFIC_INPUT = 'redirect_payment_method_specific_input';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var RedirectPaymentMethodSpecificInputFactory
     */
    private $redirectPaymentMethodSpecificInputFactory;

    /**
     * @var RedirectPaymentProduct5408SpecificInputFactory
     */
    private $paymentProduct5408SIFactory;

    /**
     * @var RedirectPaymentProduct5403SpecificInputFactory
     */
    private $paymentProduct5403SIFactory;

    /**
     * @var RedirectPaymentProduct5402SpecificInputFactory
     */
    private $paymentProduct5402SIFactory;

    /**
     * @var RedirectPaymentProduct5300SpecificInputFactory
     */
    private $paymentProduct5300SIFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        Config                                         $config,
        ManagerInterface                               $eventManager,
        RedirectPaymentMethodSpecificInputFactory      $redirectPaymentMethodSpecificInputFactory,
        RedirectPaymentProduct5408SpecificInputFactory $paymentProduct5408SIFactory,
        RedirectPaymentProduct5403SpecificInputFactory $paymentProduct5403SIFactory,
        RedirectPaymentProduct5402SpecificInputFactory $paymentProduct5402SIFactory,
        RedirectPaymentProduct5300SpecificInputFactory $paymentProduct5300SIFactory,
        StoreManagerInterface $storeManager
    )
    {
        $this->config = $config;
        $this->eventManager = $eventManager;
        $this->redirectPaymentMethodSpecificInputFactory = $redirectPaymentMethodSpecificInputFactory;
        $this->paymentProduct5408SIFactory = $paymentProduct5408SIFactory;
        $this->paymentProduct5403SIFactory = $paymentProduct5403SIFactory;
        $this->paymentProduct5402SIFactory = $paymentProduct5402SIFactory;
        $this->paymentProduct5300SIFactory = $paymentProduct5300SIFactory;
        $this->storeManager = $storeManager;
    }

    public function build(CartInterface $quote): RedirectPaymentMethodSpecificInput
    {
        $storeId = (int)$quote->getStoreId();
        /** @var RedirectPaymentMethodSpecificInput $redirectPaymentMethodSpecificInput */
        $redirectPaymentMethodSpecificInput = $this->redirectPaymentMethodSpecificInputFactory->create();
        $payProductId = $quote->getPayment()->getAdditionalInformation(RedirectManagement::PAYMENT_PRODUCT_ID);
        $authMode = $this->config->getAuthorizationMode();
        if ($payProductId && ($payProductId === PaymentProductsDetailsInterface::MEALVOUCHERS_PRODUCT_ID
                || $payProductId === PaymentProductsDetailsInterface::CHEQUE_VACANCES_CONNECT_PRODUCT_ID
                || $payProductId === PaymentProductsDetailsInterface::PLEDG_PRODUCT_ID)
        ) {
            $redirectPaymentMethodSpecificInput->setRequiresApproval(false);
        } else {
            $redirectPaymentMethodSpecificInput->setRequiresApproval($authMode !== Config::AUTHORIZATION_MODE_SALE);
        }
        $redirectPaymentMethodSpecificInput->setPaymentOption(
            $this->config->getOneyPaymentOption($storeId)
        );
        if ($payProductId) {
            $redirectPaymentMethodSpecificInput->setPaymentProductId((int)$payProductId);
        }

        $paymentProduct5408SI = $this->paymentProduct5408SIFactory->create();
        $paymentProduct5408SI->setInstantPaymentOnly($this->config->getBankTransferMode($storeId));
        $redirectPaymentMethodSpecificInput->setPaymentProduct5408SpecificInput($paymentProduct5408SI);

        $paymentProduct5403SI = $this->paymentProduct5403SIFactory->create();
        $paymentProduct5403SI->setCompleteRemainingPaymentAmount(true);
        $redirectPaymentMethodSpecificInput->setPaymentProduct5403SpecificInput($paymentProduct5403SI);

        $paymentProduct5402SI = $this->paymentProduct5402SIFactory->create();
        $paymentProduct5402SI->setCompleteRemainingPaymentAmount(true);
        $redirectPaymentMethodSpecificInput->setPaymentProduct5402SpecificInput($paymentProduct5402SI);

        $paymentProduct5300SI = $this->paymentProduct5300SIFactory->create();
        $redirectPaymentMethodSpecificInput->setPaymentProduct5300SpecificInput($paymentProduct5300SI);

        $args = ['quote' => $quote, self::RP_METHOD_SPECIFIC_INPUT => $redirectPaymentMethodSpecificInput];
        $this->eventManager->dispatch(ConfigProvider::CODE . '_redirect_payment_method_specific_input_builder', $args);

        return $redirectPaymentMethodSpecificInput;
    }
}
