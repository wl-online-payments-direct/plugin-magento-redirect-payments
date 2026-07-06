<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class SepaWarning extends Field
{
    public function render(AbstractElement $element): string
    {
        $message = (string) __(
            'Please ensure the mandate signing method is configured correctly 
            if you offer SEPA Direct Debit to your customers; otherwise, transactions may be refused.'
        );

        return '<tr id="row_' . $element->getHtmlId() . '">'
            . '<td class="label">&nbsp;</td>'
            . '<td class="value">'
            . '<div class="message message-warning" style="margin-top:15px;margin-bottom:5px;">'
            . '<div>' . $message . '</div>'
            . '</div>'
            . '</td>'
            . '<td class="use-default"></td>'
            . '<td class="scope-label"></td>'
            . '<td></td>'
            . '</tr>';
    }
}
