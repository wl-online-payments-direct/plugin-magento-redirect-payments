define([
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/place-order'
], function (quote, urlBuilder, customer, placeOrderService) {
    'use strict';

    return function (paymentData, messageContainer) {
        let serviceUrl, payload;

        payload = {
            cartId: quote.getQuoteId(),
            paymentMethod: paymentData,
            billingAddress: quote.billingAddress()
        };

        if (customer.isLoggedIn()) {
            serviceUrl = urlBuilder.createUrl('/carts/mine/worldline/redirect-payment-process-redirect', {});
        } else {
            serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/worldline/redirect-payment-process-redirect', {
                cartId: quote.getQuoteId()
            });
            payload.email = quote.guestEmail;
        }

        return placeOrderService(serviceUrl, payload, messageContainer);
    };
});
