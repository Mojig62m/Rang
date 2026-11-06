<?php

/**
 * WooCommerce Checkout Block integration for the Zarinpal gateway.
 * Loaded by bamero_zarinpal_register_block_support() on woocommerce_blocks_loaded.
 */

defined('ABSPATH') || exit;

class Bamero_Zarinpal_Blocks_Support extends \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType
{
    /** @var string Must match Bamero_Zarinpal_Gateway::$id. */
    protected $name = 'bamero_zarinpal';

    public function initialize()
    {
        $this->settings = get_option('woocommerce_bamero_zarinpal_settings', array());
    }

    /** The gateway decides availability itself (fail-closed on missing config). */
    public function is_active()
    {
        $gateway = $this->gateway();
        return $gateway ? $gateway->is_available() : false;
    }

    public function get_payment_method_script_handles()
    {
        $handle = 'bamero-zarinpal-blocks';
        wp_register_script(
            $handle,
            plugins_url('blocks/zarinpal-checkout-block.js', __FILE__),
            array('wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n', 'wp-html-entities'),
            BAMERO_ZARINPAL_VERSION,
            true
        );
        return array($handle);
    }

    public function get_payment_method_data()
    {
        $gateway = $this->gateway();
        return array(
            'title'       => $gateway ? $gateway->get_title() : 'پرداخت امن زرین‌پال',
            'description' => $gateway ? $gateway->get_description() : '',
            'supports'    => $gateway ? array_filter($gateway->supports, array($gateway, 'supports')) : array('products'),
        );
    }

    /** @return WC_Payment_Gateway|null */
    private function gateway()
    {
        $gateways = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array();
        return isset($gateways[$this->name]) ? $gateways[$this->name] : null;
    }
}
