define([
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'mage/url',
    'Magento_Checkout/js/action/place-order'
], function ($,Component,url,placeOrderAction) {
    'use strict';

    return Component.extend({
        defaults: {
            redirectAfterPlaceOrder: false,
            template: 'Retroitsoln_Esewa/payment/esewa'
        },

        getInstructions: function () {
            return window.checkoutConfig.payment.instructions[this.item.method];
        },

        afterPlaceOrder: function (data, event) {
            console.log('redirecting');
            window.location.replace(url.build('esewa/request/redirect'));
        }
    });
});