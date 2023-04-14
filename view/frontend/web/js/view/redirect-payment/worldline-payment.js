define([
    'underscore',
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list',
    'uiLayout',
    'uiRegistry'
], function (_, Component, rendererList) {
    let config = window.checkoutConfig.payment,
        rpType = 'worldline_redirect_payment',
        applePayCode = 'worldline_redirect_payment_302';

    _.each(config, function (payment, key) {
        if (key === applePayCode) {
            if (!window.ApplePaySession) {
                return false;
            }
        }

        if (key.includes(rpType + '_')) {
            rendererList.push(
                {
                    type: key,
                    component: 'Worldline_RedirectPayment/js/view/redirect-payment/worldlinerp-method'
                }
            );
        }
    }.bind(this));

    return Component.extend({});
});
