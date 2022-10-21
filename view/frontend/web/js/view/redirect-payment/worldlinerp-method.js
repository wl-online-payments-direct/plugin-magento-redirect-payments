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
    'Worldline_RedirectPayment/js/view/redirect-payment/redirect'
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
    placeOrderAction
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Worldline_RedirectPayment/payment/worldlinerp',
            imports: {
                paymentProducts: 'index = worldline-redirect-payment-payment:paymentProducts'
            }
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
         * @returns {String}
         */
        getTitle: function () {
            return this.title;
        },

        /**
         * @returns {String}
         */
        getId: function () {
            return this.index;
        },

        /**
         * @returns {String}
         */
        getCode: function () {
            return this.code;
        },

        /**
         * @returns {String}
         */
        getUrl: function () {
            return this.url;
        },

        /** @inheritdoc */
        selectPaymentMethod: function () {
            selectPaymentMethod(
                {
                    method: this.getId()
                }
            );
            checkoutData.setSelectedPaymentMethod(this.getId());

            return true;
        },

        /**
         * Return state of place order button.
         *
         * @return {Boolean}
         */
        isButtonActive: function () {
            return this.isActive() && this.isPlaceOrderActionAllowed();
        },

        /**
         * Check if payment is active.
         *
         * @return {Boolean}
         */
        isActive: function () {
            return this.isChecked() === this.getId();
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
         * Get list of available CC types
         *
         * @returns {Object}
         */
        getAvailableTypes: function () {
            return [];
        },

        /** @inheritdoc */
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
            let additionalData = deviceData.getData();
            additionalData.selected_payment_product = this.payProduct;
            data.additional_data = additionalData;

            this.vaultEnabler.visitAdditionalData(data);

            return data;
        }
    });
});
