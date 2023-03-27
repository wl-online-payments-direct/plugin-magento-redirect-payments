/*browser:true*/
/*global define*/
define([
    'ko',
    'jquery',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Vault/js/view/payment/method-renderer/vault',
    'Worldline_RedirectPayment/js/view/redirect-payment/redirect',
    'Worldline_PaymentCore/js/model/device-data',
    'mage/url'
], function (ko, $, fullScreenLoader, VaultComponent, placeOrderAction, deviceData, urlBuilder) {
    'use strict';

    return VaultComponent.extend({
        defaults: {
            isSurchargeEnabled: ko.observable(false)
        },

        /**
         * Get last 4 digits of card
         * @returns {String}
         */
        getMaskedCard: function () {
            return this.details.maskedCC;
        },

        /**
         * Get expiration date
         * @returns {String}
         */
        getExpirationDate: function () {
            return this.details.expirationDate;
        },

        /**
         * Get card type
         * @returns {String}
         */
        getCardType: function () {
            return this.details.type;
        },

        getToken: function () {
            return this.public_hash;
        },

        getSurcharge: function (data, event) {
            this.placeOrder(data, event);
        },

        placeOrder: function (data, event) {
            let self = this;

            if (event) {
                event.preventDefault();
            }

            if (!this.validate() ||
                this.isPlaceOrderActionAllowed() !== true
            ) {
                return false;
            }

            fullScreenLoader.startLoader();

            this.isPlaceOrderActionAllowed(false);

            $.when(
                placeOrderAction(self.getData(), self.messageContainer)
            ).done(
                function (redirectUrl) {
                    if (redirectUrl) {
                        window.location.replace(redirectUrl);
                    } else {
                        fullScreenLoader.startLoader();
                        setTimeout(() => {
                            window.location.replace(urlBuilder.build("wl_hostedcheckout/returns/returnUrlForVault"));
                        }, 3000)
                    }
                }
            ).fail(
                function () {
                    self.isPlaceOrderActionAllowed(true);
                }
            ).always(
                function () {
                    fullScreenLoader.stopLoader();
                }
            );

            return true;
        },

        /**
         * @returns {Object}
         */
        getData: function () {
            let data = this._super();
            let additionalData = deviceData.getData();
            additionalData.public_hash = this.public_hash;
            data.additional_data = additionalData;

            return data;
        }
    });
});
