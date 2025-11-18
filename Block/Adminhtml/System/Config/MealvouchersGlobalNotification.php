<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class MealvouchersGlobalNotification extends Field
{
    /**
     * @param AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function render(AbstractElement $element): string
    {
        $notifySendCart = "Please note that the Mealvouchers method will work properly only if the "
            . "'Submit Customer Cart Items Data to Worldline' setting is enabled";
        $notifyDetails = "By enabling mealvouchers, you will be able to configure your products as 'Food and Drink', "
            . "'Home and Garden', or 'Gift and Flowers'. Simply note that the Worldline platform only allows one type "
            . "of products per basket, and the plugin will automatically update the products of the basket that are "
            . "missing a product type to the value recognized in the basket; in case of a mixed basket "
            . "all products will be set to 'Food and Drink'";

        return '<div class="message message-notice notice">' . __($notifySendCart) . '</div>'
            . '<div class="message message-notice notice">' . __($notifyDetails) . '</div>';
    }
}
