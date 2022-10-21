<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Plugin\Checkout\Model\GuestPaymentInformationManagement;

use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Api\PaymentMethodListInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Store\Model\StoreManagerInterface;

class SetPaymentMethod
{
    /**
     * @var PaymentMethodListInterface
     */
    private $paymentMethodList;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        PaymentMethodListInterface $paymentMethodList,
        StoreManagerInterface $storeManager
    ) {
        $this->paymentMethodList = $paymentMethodList;
        $this->storeManager = $storeManager;
    }

    /**
     * @param GuestPaymentInformationManagementInterface $subject
     * @param string $cartId
     * @param string $email
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return void
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSavePaymentInformation(
        GuestPaymentInformationManagementInterface $subject,
        string $cartId,
        string $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ): void {
        $availableMethods = $this->paymentMethodList->getActiveList($this->storeManager->getStore()->getId());
        foreach ($availableMethods as $availableMethod) {
            if (strpos($paymentMethod->getMethod() ?? '', $availableMethod->getCode()) !== false) {
                $paymentMethod->setMethod($availableMethod->getCode());
            }
        }
    }
}
