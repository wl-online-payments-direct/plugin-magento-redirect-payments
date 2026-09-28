<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Service;

use Magento\Quote\Model\Quote;
use Worldline\HostedCheckout\Model\Config\Source\MealvouchersProductTypes;
use Worldline\RedirectPayment\Ui\ConfigProvider;

/**
 * Mealvouchers availability, evaluated against a quote that already carries an address.
 *
 * These rules used to sit in ConfigProvider. That runs once, when /checkout renders, which is
 * before the shopper has entered anything: measured on a guest checkout, the quote has no billing
 * country at that point, so the rules were decided against empty values and the method could not
 * appear. The payment_method_is_active observer is the first point where the address is populated,
 * so the rules live here and are called from there.
 *
 * There is deliberately no email condition. merchantCustomerId is not mandatory for this method,
 * and a guest's email is not on the server at all while the payment list is being built - Magento
 * receives it as an argument of GuestPaymentInformationManagement::savePaymentInformation() and
 * only then writes it to the billing address, from where QuoteManagement copies it onto the quote.
 * The order therefore always carries an email; the payment step simply cannot see it yet.
 */
class MealvouchersAvailability
{
    /**
     * @var string[]
     */
    private const ALLOWED_COUNTRIES = ['FR', 'BE'];

    private const ALLOWED_CURRENCY = 'EUR';

    /**
     * @param Quote $quote
     * @return bool
     */
    public function isAvailable(Quote $quote): bool
    {
        if (!$this->hasEligibleItem($quote)) {
            return false;
        }

        if ($quote->getQuoteCurrencyCode() !== self::ALLOWED_CURRENCY) {
            return false;
        }

        $billing = $quote->getBillingAddress();

        return $billing && in_array($billing->getCountryId(), self::ALLOWED_COUNTRIES, true);
    }

    /**
     * Check if any visible item is of a product type payable by meal voucher.
     *
     * @param Quote $quote
     * @return bool
     */
    private function hasEligibleItem(Quote $quote): bool
    {
        $eligibleTypes = [
            MealvouchersProductTypes::FOOD_AND_DRINK,
            MealvouchersProductTypes::HOME_AND_GARDEN,
            MealvouchersProductTypes::GIFT_AND_FLOWERS
        ];

        foreach ($quote->getAllVisibleItems() as $item) {
            if (in_array($item->getProduct()->getData(ConfigProvider::PRODUCT_TYPE), $eligibleTypes, true)) {
                return true;
            }
        }

        return false;
    }
}
