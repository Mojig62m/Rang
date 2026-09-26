<?php
/**
 * Plugin Name: Bamero Zarinpal Gateway
 * Description: Minimal, environment-configured WooCommerce gateway for Zarinpal request/verify flow.
 * Version: 1.0.0
 * Author: Bamero
 * License: MIT
 * Text Domain: bamero-zarinpal-gateway
 */
defined('ABSPATH') || exit;

add_action('plugins_loaded', 'bamero_zarinpal_bootstrap', 20);
function bamero_zarinpal_bootstrap() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    class Bamero_Zarinpal_Gateway extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = 'bamero_zarinpal';
            $this->method_title = 'زرین‌پال';
            $this->method_description = 'درگاه زرین‌پال با secret از محیط اجرا؛ بدون ذخیره credential در دیتابیس.';
            $this->has_fields = false;
            $this->supports = array('products');
            $this->title = 'پرداخت امن زرین‌پال';
            $this->description = 'پس از ثبت سفارش به درگاه زرین‌پال منتقل می‌شوید.';
            $this->enabled = (getenv('ZARINPAL_MERCHANT_ID') && getenv('ZARINPAL_API_BASE_URL')) ? 'yes' : 'no';
            $this->init_form_fields();
            add_action('woocommerce_api_bamero_zarinpal', array($this, 'handle_callback'));
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array('title' => 'فعال‌سازی', 'type' => 'checkbox', 'label' => 'استفاده از تنظیمات محیط اجرا', 'default' => 'no'),
            );
        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            if (!$order) return array('result' => 'failure');
            $merchant = getenv('ZARINPAL_MERCHANT_ID');
            $base = rtrim((string) getenv('ZARINPAL_API_BASE_URL'), '/');
            if (!$merchant || !$base) {
                wc_add_notice('درگاه پرداخت هنوز پیکربندی نشده است.', 'error');
                return array('result' => 'failure');
            }
            $amount = (int) round((float) $order->get_total());
            $response = wp_remote_post($base . '/payment/request.json', array(
                'timeout' => 15,
                'headers' => array('Content-Type' => 'application/json', 'Accept' => 'application/json'),
                'body' => wp_json_encode(array(
                    'merchant_id' => $merchant,
                    'amount' => $amount,
                    'currency' => getenv('ZARINPAL_CURRENCY') ?: 'IRT',
                    'description' => 'Bamero order #' . $order->get_id(),
                    'callback_url' => add_query_arg('wc-api', 'bamero_zarinpal', home_url('/')),
                    'metadata' => array('mobile' => (string) $order->get_billing_phone(), 'order_id' => (string) $order->get_id()),
                )),
            ));
            if (is_wp_error($response)) {
                wc_add_notice('ارتباط با درگاه پرداخت برقرار نشد.', 'error');
                return array('result' => 'failure');
            }
            $body = json_decode(wp_remote_retrieve_body($response), true);
            $authority = isset($body['data']['authority']) ? sanitize_text_field($body['data']['authority']) : '';
            $code = isset($body['data']['code']) ? (int) $body['data']['code'] : 0;
            if (100 !== $code || !$authority) {
                wc_add_notice('درخواست پرداخت رد شد. بعداً دوباره تلاش کنید.', 'error');
                return array('result' => 'failure');
            }
            $order->update_meta_data('_bamero_zarinpal_authority', $authority);
            $order->save();
            $order->update_status('pending', 'در انتظار بازگشت از زرین‌پال.');
            return array('result' => 'success', 'redirect' => 'https://payment.zarinpal.com/pg/StartPay/' . rawurlencode($authority));
        }

        public function handle_callback() {
            $authority = isset($_GET['Authority']) ? sanitize_text_field(wp_unslash($_GET['Authority'])) : '';
            $status = isset($_GET['Status']) ? sanitize_text_field(wp_unslash($_GET['Status'])) : '';
            $orders = $authority ? wc_get_orders(array('limit' => 1, 'return' => 'objects', 'meta_key' => '_bamero_zarinpal_authority', 'meta_value' => $authority)) : array();
            $order = !empty($orders) ? $orders[0] : false;
            if (!$order || 'OK' !== strtoupper($status)) {
                if ($order) $order->update_status('failed', 'بازگشت ناموفق از زرین‌پال.');
                wp_safe_redirect(wc_get_checkout_url());
                exit;
            }
            if ($order->is_paid()) {
                wp_safe_redirect($this->get_return_url($order));
                exit;
            }
            $merchant = getenv('ZARINPAL_MERCHANT_ID');
            $base = rtrim((string) getenv('ZARINPAL_API_BASE_URL'), '/');
            $response = wp_remote_post($base . '/payment/verify.json', array(
                'timeout' => 15,
                'headers' => array('Content-Type' => 'application/json', 'Accept' => 'application/json'),
                'body' => wp_json_encode(array('merchant_id' => $merchant, 'amount' => (int) round((float) $order->get_total()), 'authority' => $authority)),
            ));
            $body = is_wp_error($response) ? array() : json_decode(wp_remote_retrieve_body($response), true);
            $code = isset($body['data']['code']) ? (int) $body['data']['code'] : 0;
            if (100 === $code || 101 === $code) {
                $ref = isset($body['data']['ref_id']) ? sanitize_text_field((string) $body['data']['ref_id']) : $authority;
                $order->payment_complete($ref);
                $order->add_order_note('پرداخت زرین‌پال تأیید شد: ' . $ref);
                wp_safe_redirect($this->get_return_url($order));
                exit;
            }
            $order->update_status('failed', 'تأیید پرداخت زرین‌پال ناموفق بود.');
            wp_safe_redirect(wc_get_checkout_url());
            exit;
        }
    }

    add_filter('woocommerce_payment_gateways', function ($gateways) {
        $gateways[] = 'Bamero_Zarinpal_Gateway';
        return $gateways;
    });
}

add_filter('woocommerce_gateway_title', function ($title, $id) {
    return 'bamero_zarinpal' === $id ? 'پرداخت امن زرین‌پال' : $title;
}, 10, 2);

/* Never allow this plugin to become an email transport. */
add_filter('woocommerce_email_enabled_new_order', '__return_false', 99);
