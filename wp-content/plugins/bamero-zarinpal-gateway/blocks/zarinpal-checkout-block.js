/**
 * Bamero Zarinpal – WooCommerce Checkout Block payment method.
 * Plain ES5 on purpose: no build step, served as-is under the strict CSP
 * (script-src 'self' + nonce; wp_enqueue_script output is nonced by the theme).
 */
(function (wp, wc) {
    'use strict';
    if (!wp || !wc || !wc.wcBlocksRegistry || !wc.wcSettings) {
        return;
    }
    var el = wp.element.createElement;
    var settings = wc.wcSettings.getSetting('bamero_zarinpal_data', {});
    var decode = wp.htmlEntities ? wp.htmlEntities.decodeEntities : function (s) { return s; };
    var label = decode(settings.title || 'پرداخت امن زرین‌پال');
    var description = decode(settings.description || '');

    var Label = function (props) {
        var PaymentMethodLabel = props.components.PaymentMethodLabel;
        return el(PaymentMethodLabel, { text: label });
    };
    var Content = function () {
        return description ? el('p', { className: 'bamero-zarinpal-description' }, description) : null;
    };

    wc.wcBlocksRegistry.registerPaymentMethod({
        name: 'bamero_zarinpal',
        label: el(Label, null),
        content: el(Content, null),
        edit: el(Content, null),
        ariaLabel: label,
        canMakePayment: function () { return true; },
        supports: { features: (settings.supports && settings.supports.length) ? settings.supports : ['products'] }
    });
})(window.wp, window.wc);
