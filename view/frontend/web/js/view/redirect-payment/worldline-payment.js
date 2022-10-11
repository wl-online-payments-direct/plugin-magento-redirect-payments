define([
    'underscore',
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list',
    'uiLayout',
    'uiRegistry'
], function (_, Component, rendererList, layout, registry) {

    const payment = window.checkoutConfig.payment['worldline_redirect_payment'];
    const groupName = 'methodGroup';

    if (payment && payment.isActive) {
        layout([ {
            name: groupName,
            component: 'Magento_Checkout/js/model/payment/method-group',
            alias: 'default',
            title: '',
            payProduct: '',
            sortOrder: 30
        } ]);

        registry.get(groupName, function (group) {
            _.each(window.checkoutConfig.payment.worldline_redirect_payment.payment_products, function (config, index) {
                rendererList.push(
                    {
                        config: config.config,
                        type: index,
                        component: 'Worldline_RedirectPayment/js/view/redirect-payment/worldlinerp-method',
                        group: group,

                        /**
                         * Custom payment method types comparator
                         * @param {String} typeA
                         * @param {String} typeB
                         * @return {Boolean}
                         */
                        typeComparatorCallback: function (typeA, typeB) {
                            // vault token items have the same name as vault payment without index
                            return typeA.substring(0, typeA.lastIndexOf('_')) === typeB;
                        }
                    }
                );
            });
        });
    }

    return Component.extend({});
});
