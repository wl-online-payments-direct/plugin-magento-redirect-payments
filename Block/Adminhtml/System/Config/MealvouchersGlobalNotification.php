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
    public function render(AbstractElement $element)
    {
        $notifyText = "Please note that the Mealvouchers method will work properly only if the "
            . "'Submit Customer Cart Items Data to Worldline' setting is enabled";

        return '<div class="message message-notice notice">' . __($notifyText) . '</div>';
    }
}
