define([
    'ko',
    'jquery',
    'underscore',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/action/select-payment-method',
    'Magento_Checkout/js/checkout-data',
    'Magento_Vault/js/view/payment/vault-enabler',
    'Magento_Checkout/js/model/full-screen-loader',
    'Worldline_PaymentCore/js/model/device-data',
    'Worldline_RedirectPayment/js/view/redirect-payment/redirect',
    'Magento_Checkout/js/model/payment/additional-validators'
], function (
    ko,
    $,
    _,
    quote,
    Component,
    selectPaymentMethod,
    checkoutData,
    VaultEnabler,
    fullScreenLoader,
    deviceData,
    placeOrderAction,
    additionalValidators
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Worldline_RedirectPayment/payment/worldlinerp'
        },

        /**
         * @returns {exports.initialize}
         */
        initialize: function () {
            this._super();
            this.vaultEnabler = new VaultEnabler();
            this.vaultEnabler.setPaymentCode(this.getVaultCode());

            return this;
        },

        /**
         * @returns {Boolean}
         */
        isVaultEnabled: function () {
            return this.vaultEnabler.isVaultEnabled();
        },

        /**
         * @returns {String}
         */
        getVaultCode: function () {
            return window.checkoutConfig.payment[this.getCode()].vaultCode;
        },

        /**
         * Get payment icon
         * @returns {Boolean}
         */
        getIcon: function () {
            return window.checkoutConfig.payment[this.getCode()].icon ?
                window.checkoutConfig.payment[this.getCode()].icon
                : false;
        },

        /** @inheritdoc */
        placeOrder: function (data, event) {
            let self = this;

            if (event) {
                event.preventDefault();
            }

            if (!this.validate() ||
                (this.isPlaceOrderActionAllowed() !== true) ||
                !additionalValidators.validate()
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
         * @returns {object}
         */
        getData: function () {
            let data = this._super();
            data.additional_data = deviceData.getData();

            this.vaultEnabler.visitAdditionalData(data);

            return data;
        }
    });
});
