<?php

/**
 * Plugin Name: Bamero Production Core
 * Description: Production controls for Bamero: fail-closed security headers (CSP), rate limiting, session hardening, SMS notification outbox, health endpoints and structured telemetry.
 * Version: 1.0.0
 * Author: Bamero
 * License: MIT
 * Text Domain: bamero-production-core
 */

defined('ABSPATH') || exit;

const BAMERO_PRODUCTION_CORE_VERSION = '1.0.0';
const BAMERO_DATETIME_FORMAT = 'Y-m-d H:i:s';

function bamero_request_id()
{
    static $request_id = null;
    if (null !== $request_id) {
        return $request_id;
    }
    $incoming = isset($_SERVER['HTTP_X_REQUEST_ID']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_REQUEST_ID'])) : '';
    $request_id = preg_match('/^[A-Za-z0-9._:-]{8,64}$/', $incoming) ? $incoming : wp_generate_uuid4();
    return $request_id;
}

/**
 * Log structured events with redaction of sensitive fields.
 */
function bamero_log_event($event, array $context = array())
{
    $redact = array(
        'phone', 'mobile', 'email', 'order_total', 'authorization', 'signature',
        'otp', 'code', 'token', 'password', 'secret', 'ref_id', 'authority',
        'national_id', 'card', 'cvv', 'iban', 'api_key', 'merchant_id',
    );
    $safe = array('event' => sanitize_key($event), 'timestamp' => gmdate('c'), 'request_id' => bamero_request_id(), 'correlation_id' => wp_generate_uuid4());
    foreach ($context as $key => $value) {
        if (in_array($key, $redact, true)) {
            continue;
        }
        $safe[sanitize_key($key)] = is_scalar($value) ? sanitize_text_field((string) $value) : '[redacted]';
    }
    error_log(wp_json_encode($safe, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    return $safe['correlation_id'];
}

function bamero_rate_limit($bucket, $limit, $window)
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
    $key = 'bamero_rl_' . md5($bucket . '|' . $ip);
    // H4: serialize the read-modify-write counter with a MySQL named lock so
    // concurrent requests cannot slip past the limit (fail-closed on lock miss).
    global $wpdb;
    $rl_lock = 'bamero_rl_l_' . md5($key);
    $got_lock = (int) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 1)', $rl_lock));
    if (1 !== $got_lock) {
        return false;
    }
    try {
        $state = get_transient($key);
        $state = is_array($state) ? $state : array('count' => 0, 'started' => time());
        if ((time() - (int) $state['started']) >= $window) {
            $state = array('count' => 0, 'started' => time());
        }
        $state['count']++;
        set_transient($key, $state, $window);
        return (int) $state['count'] <= (int) $limit;
    } finally {
        $wpdb->query($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $rl_lock));
    }
}

function bamero_rate_limit_login($user, $username)
{
    if (!bamero_rate_limit('login', 5, MINUTE_IN_SECONDS)) {
        return new WP_Error('too_many_attempts', __('تعداد تلاش‌ها بیش از حد مجاز است. بعداً دوباره تلاش کنید.', 'bamero-production-core'), array('status' => 429));
    }
    return $user;
}
add_filter('authenticate', 'bamero_rate_limit_login', 30, 2);

// Canonical owner of bamero_csp_nonce() (plugin loads before theme; theme functions.php keeps a guarded fallback).
if (! function_exists('bamero_csp_nonce')) {
    function bamero_csp_nonce()
    {
        static $nonce = null;
        if (null === $nonce) {
            $nonce = base64_encode(random_bytes(16));
        }
        return $nonce;
    }
}
add_filter('wp_inline_script_attributes', function ($attributes) {
    $attributes['nonce'] = bamero_csp_nonce();
    return $attributes;
});

function bamero_rate_limit_account_page()
{
    if (function_exists('is_account_page') && is_account_page() && !bamero_rate_limit('my_account', 5, MINUTE_IN_SECONDS)) {
        wp_die(esc_html__('تعداد درخواست‌ها بیش از حد مجاز است. بعداً دوباره تلاش کنید.', 'bamero-production-core'), '', array('response' => 429));
    }
}
add_action('template_redirect', 'bamero_rate_limit_account_page', 2);

/**
 * Security hardening: XML-RPC off (brute-force / pingback amplification
 * vector) and no outgoing pingbacks. Head-bloat removal (RSD, WLW, emoji,
 * oEmbed discovery) is owned by the theme's performance module.
 */
function bamero_disable_unused_features()
{
    add_filter('xmlrpc_enabled', '__return_false');
    add_filter('pre_ping', '__return_empty_array');
}
add_action('init', 'bamero_disable_unused_features', 1);

// Canonical owner of bamero_security_headers() (guarded; theme keeps a fallback).
if (! function_exists('bamero_security_headers')) {
    function bamero_security_headers()
    {
        if (headers_sent()) {
            return;
        }
        header('X-Request-ID: ' . bamero_request_id());
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), usb=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('X-Permitted-Cross-Domain-Policies: none');
        $csp = 'default-src \'self\'; base-uri \'self\'; object-src \'none\'; frame-src \'none\'; frame-ancestors \'self\'; form-action \'self\'; img-src \'self\' data: https:; style-src \'self\' \'unsafe-inline\'; font-src \'self\'; script-src \'self\' \'nonce-' . bamero_csp_nonce() . '\'; connect-src \'self\' https:;';
        // HTTPS-only directives: never sent over plain http (local/staging stay usable).
        if (is_ssl()) {
            $csp .= ' upgrade-insecure-requests;';
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        header('Content-Security-Policy: ' . $csp);
    }
}
add_action('send_headers', 'bamero_security_headers', 1);
function bamero_private_cache_headers()
{
    $account = function_exists('is_account_page') && is_account_page();
    $private = (function_exists('is_cart') && (is_cart() || is_checkout() || $account)) || is_user_logged_in();
    if (!$private) {
        return;
    }
    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }
    nocache_headers();
}
add_action('template_redirect', 'bamero_private_cache_headers', 1);

function bamero_declare_hpos_compatibility()
{
    if (class_exists('Automattic\\WooCommerce\\Utilities\\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
}
add_action('before_woocommerce_init', 'bamero_declare_hpos_compatibility');

function bamero_health_db_probe()
{
    global $wpdb;
    return '1' === (string) $wpdb->get_var('SELECT 1');
}

function bamero_health_readiness_checks()
{
    global $wpdb;
    $table = bamero_notification_table_name();
    $sms_templates = array('LOGIN_OTP', 'ORDER_PROCESSING', 'ORDER_DELIVERED', 'ORDER_CANCELLED', 'PAYMENT_FAILED', 'REFUND_COMPLETED');
    $sms_configured = (bool) getenv('SMS_IR_API_KEY') && (bool) getenv('SMS_IR_API_BASE_URL');
    foreach ($sms_templates as $template) {
        if (!getenv('SMS_IR_TEMPLATE_' . $template)) {
            $sms_configured = false;
            break;
        }
    }
    return array(
        'wordpress' => function_exists('wp_get_environment_type'),
        'database' => bamero_health_db_probe(),
        'woocommerce' => class_exists('WooCommerce'),
        'outbox_table' => $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table,
        'sms_provider_configured' => (bool) has_filter('bamero_sms_provider_send') && $sms_configured,
        'zarinpal_secret' => (bool) getenv('ZARINPAL_MERCHANT_ID'),
    );
}

function bamero_health_rest_routes()
{
    register_rest_route('bamero/v1', '/health/live', array(
        'methods' => WP_REST_Server::READABLE,
        'permission_callback' => '__return_true',
        'callback' => function () {
            return new WP_REST_Response(array('status' => 'ok', 'service' => 'bamero-wordpress', 'version' => BAMERO_PRODUCTION_CORE_VERSION), 200);
        },
    ));
    register_rest_route('bamero/v1', '/health/ready', array(
        'methods' => WP_REST_Server::READABLE,
        'permission_callback' => '__return_true',
        'callback' => function () {
            // R5: readiness details are internal environment state. Fail-closed:
            // without a configured BAMERO_HEALTH_TOKEN the endpoint stays closed;
            // with one, callers must present it via X-Bamero-Health-Token header.
            $token = (string) getenv('BAMERO_HEALTH_TOKEN');
            $given = isset($_SERVER['HTTP_X_BAMERO_HEALTH_TOKEN']) ? (string) sanitize_text_field(wp_unslash($_SERVER['HTTP_X_BAMERO_HEALTH_TOKEN'])) : '';
            if ('' === $token || !hash_equals($token, $given)) {
                return new WP_REST_Response(array('status' => 'forbidden'), 403);
            }
            $checks = bamero_health_readiness_checks();
            $ready = !in_array(false, $checks, true);
            return new WP_REST_Response(array('status' => $ready ? 'ready' : 'not_ready', 'checks' => $checks), $ready ? 200 : 503);
        },
    ));
}
add_action('rest_api_init', 'bamero_health_rest_routes');

function bamero_observe_order($order_id)
{
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }
    bamero_log_event('order_status_changed', array('order_id' => $order_id, 'status' => $order->get_status(), 'currency' => $order->get_currency()));
}
add_action('woocommerce_order_status_changed', 'bamero_observe_order');

/**
 * Session hardening: idle + absolute timeouts.
 * Admins get short windows; customers keep persistent login but with a safety net.
 */
function bamero_session_limits($user_id)
{
    if (user_can($user_id, 'manage_options')) {
        return array('idle' => 2 * HOUR_IN_SECONDS, 'absolute' => 12 * HOUR_IN_SECONDS);
    }
    return array(
        'idle'     => (int) apply_filters('bamero_session_idle_timeout', 14 * DAY_IN_SECONDS),
        'absolute' => (int) apply_filters('bamero_session_absolute_timeout', 90 * DAY_IN_SECONDS),
    );
}

function bamero_mark_session_start($user_login, $user = null)
{
    if ($user instanceof WP_User) {
        update_user_meta($user->ID, 'bamero_session_start', time());
        update_user_meta($user->ID, 'bamero_last_activity', time());
    }
}
add_action('wp_login', 'bamero_mark_session_start', 10, 2);

function bamero_enforce_session_timeout()
{
    if (!is_user_logged_in() || is_admin()) {
        return;
    }
    if ((defined('DOING_AJAX') && DOING_AJAX) || (defined('DOING_CRON') && DOING_CRON) || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }
    if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
        return;
    }

    $user_id = get_current_user_id();
    $now     = time();
    $limits  = bamero_session_limits($user_id);
    $start   = (int) get_user_meta($user_id, 'bamero_session_start', true);
    $last    = (int) get_user_meta($user_id, 'bamero_last_activity', true);

    if (!$start) {
        update_user_meta($user_id, 'bamero_session_start', $now);
        $start = $now;
    }

    if (($last && ($now - $last) > $limits['idle']) || ($now - $start) > $limits['absolute']) {
        bamero_log_event('session_expired', array('user_id' => (int) $user_id, 'reason' => 'timeout'));
        wp_logout();
        update_user_meta($user_id, 'bamero_session_start', $now);
        wp_safe_redirect(add_query_arg('bamero_auth', 'session_expired', home_url('/')));
        exit;
    }

    // H5: throttle activity persistence — at most one meta write per minute.
    // Use transient to avoid database write on every request.
    $activity_key = 'bamero_last_activity_cache_' . $user_id;
    $cached_last = (int) get_transient($activity_key);
    if (($now - $last) > 60) {
        update_user_meta($user_id, 'bamero_last_activity', $now);
        set_transient($activity_key, $now, 60);
    } elseif ($cached_last === 0 && $last > 0) {
        set_transient($activity_key, $last, 60 - ($now - $last));
    }
}
add_action('init', 'bamero_enforce_session_timeout', 20);


/**
 * Bamero mobile-only runtime controls.
 * SMS delivery is asynchronous; the request thread never calls a provider.
 */
function bamero_notification_table_name()
{
    global $wpdb;
    return $wpdb->prefix . 'bamero_notification_outbox';
}

function bamero_seal_notification_payload(array $payload)
{
    if (!function_exists('openssl_encrypt') || !defined('AUTH_KEY') || !AUTH_KEY) {
        return new WP_Error('payload_crypto_unavailable', 'Notification payload encryption is unavailable.');
    }
    $iv = random_bytes(12);
    $tag = '';
    $key = hash('sha256', AUTH_KEY, true);
    $cipher = openssl_encrypt(wp_json_encode($payload), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if (false === $cipher || '' === $tag) {
        return new WP_Error('payload_crypto_failed', 'Notification payload encryption failed.');
    }
    return base64_encode($iv . $tag . $cipher);
}

function bamero_open_notification_payload($sealed)
{
    if (!function_exists('openssl_decrypt') || !defined('AUTH_KEY') || !AUTH_KEY) {
        return false;
    }
    $raw = base64_decode((string) $sealed, true);
    if (false === $raw || strlen($raw) < 28) {
        return false;
    }
    $iv = substr($raw, 0, 12);
    $tag = substr($raw, 12, 16);
    $cipher = substr($raw, 28);
    $plain = openssl_decrypt($cipher, 'aes-256-gcm', hash('sha256', AUTH_KEY, true), OPENSSL_RAW_DATA, $iv, $tag);
    $payload = false === $plain ? null : json_decode($plain, true);
    return is_array($payload) ? $payload : false;
}

function bamero_notification_schema_validate($template_key, array $payload)
{
    $template = sanitize_key($template_key);
    $mobile = preg_replace('/\D+/', '', (string) ($payload['mobile'] ?? ''));
    $allowed = array('login_otp', 'order_processing', 'order_delivered', 'order_cancelled', 'payment_failed', 'refund_completed', 'contact_request', 'color_consultation');
    if (!in_array($template, $allowed, true) || strlen($mobile) < 10 || strlen($mobile) > 15) {
        return new WP_Error('notification_schema_invalid', 'Notification schema validation failed before database write.');
    }
    if ('login_otp' === $template && !preg_match('/^\d{6}$/', (string) ($payload['otp'] ?? ''))) {
        return new WP_Error('notification_schema_invalid', 'OTP payload schema validation failed before database write.');
    }
    return array('template_key' => $template, 'mobile' => $mobile);
}

/** Outbox schema version: bump whenever the CREATE TABLE below changes. */
if (!defined('BAMERO_OUTBOX_DB_VERSION')) {
    define('BAMERO_OUTBOX_DB_VERSION', '2');
}

/**
 * Create/upgrade the notification outbox table.
 * dbDelta() formatting rules are respected so upgrades actually apply: one
 * column/key per line, two spaces after PRIMARY KEY, KEY instead of INDEX,
 * and lowercase column types exactly as WordPress' own schema.
 */
function bamero_install_notification_outbox()
{
    global $wpdb;
    $table = bamero_notification_table_name();
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $sql = "CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  order_id bigint(20) unsigned NOT NULL DEFAULT 0,
  user_id bigint(20) unsigned NOT NULL DEFAULT 0,
  template_key varchar(80) NOT NULL,
  payload_hash char(64) NOT NULL,
  status varchar(20) NOT NULL DEFAULT 'pending',
  retry_count smallint(5) unsigned NOT NULL DEFAULT 0,
  max_retries smallint(5) unsigned NOT NULL DEFAULT 5,
  provider_message_id varchar(191) NOT NULL DEFAULT '',
  idempotency_key char(64) NOT NULL,
  payload_ciphertext longtext NOT NULL,
  payload_expires_at datetime NULL,
  last_error_code varchar(80) NOT NULL DEFAULT '',
  last_error_at datetime NULL,
  correlation_id char(36) NOT NULL,
  scheduled_at datetime NOT NULL,
  locked_at datetime NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY idem (idempotency_key),
  KEY status_schedule (status,scheduled_at),
  KEY status_updated (status,updated_at),
  KEY order_id (order_id),
  KEY created_at (created_at)
) {$charset};";
    dbDelta($sql);
    update_option('bamero_notification_outbox_installed', 'yes', true);
    update_option('bamero_notification_outbox_db_version', BAMERO_OUTBOX_DB_VERSION, true);
}
register_activation_hook(__FILE__, 'bamero_install_notification_outbox');

/**
 * Deploy-order safety net: guarantee the outbox table exists no matter in
 * which order theme/plugins were activated, and run dbDelta() once per schema
 * version bump (never on every request). Cheap autoloaded option check; runs
 * on admin requests and before every worker run.
 */
function bamero_ensure_notification_outbox()
{
    $installed = get_option('bamero_notification_outbox_installed');
    $version = (string) get_option('bamero_notification_outbox_db_version');
    if ($installed !== 'yes' || $version !== BAMERO_OUTBOX_DB_VERSION) {
        bamero_install_notification_outbox();
    }
}
add_action('admin_init', 'bamero_ensure_notification_outbox');

function bamero_queue_notification($order_id, $user_id, $template_key, array $payload, $max_retries = 5)
{
    global $wpdb;
    $table = bamero_notification_table_name();
    $schema = bamero_notification_schema_validate($template_key, $payload);
    if (is_wp_error($schema)) {
        return $schema;
    }
    $clean = array('mobile' => isset($payload['mobile']) ? (string) $payload['mobile'] : '', 'order_id' => (int) $order_id, 'template_key' => sanitize_key($template_key));
    // Fields that make a notification distinct: the OTP value, and for
    // owner-facing form requests (order_id = 0, always the owner's mobile)
    // the visitor's identity and message, so two visitors never share a row.
    foreach (array('otp', 'customer_mobile', 'name', 'message') as $field) {
        if (isset($payload[$field])) {
            $clean[$field] = (string) $payload[$field];
        }
    }
    $payload_hash = hash('sha256', wp_json_encode($clean));
    $idem = hash('sha256', $template_key . '|' . (int) $order_id . '|' . $payload_hash);
    $now = current_time('mysql', true);
    $correlation = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : uniqid('bamero_', true);
    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE idempotency_key = %s LIMIT 1", $idem));
    if ($exists) {
        return (int) $exists;
    }
    $sealed = bamero_seal_notification_payload($payload);
    if (is_wp_error($sealed)) {
        return $sealed;
    }
    $expires = false !== stripos($template_key, 'otp') ? gmdate(BAMERO_DATETIME_FORMAT, time() + 600) : gmdate(BAMERO_DATETIME_FORMAT, time() + DAY_IN_SECONDS);
    $ok = $wpdb->insert($table, array(
        'order_id' => (int) $order_id, 'user_id' => (int) $user_id, 'template_key' => sanitize_key($template_key),
        'payload_hash' => $payload_hash, 'status' => 'pending', 'max_retries' => max(1, (int) $max_retries),
        'idempotency_key' => $idem, 'payload_ciphertext' => $sealed, 'payload_expires_at' => $expires,
        'correlation_id' => $correlation, 'scheduled_at' => $now,
        'created_at' => $now, 'updated_at' => $now,
    ), array('%d','%d','%s','%s','%s','%d','%s','%s','%s','%s','%s','%s','%s'));
    if (!$ok) {
        $duplicate = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE idempotency_key = %s LIMIT 1", $idem));
        return $duplicate ? (int) $duplicate : new WP_Error('outbox_insert_failed', 'Notification queue unavailable.');
    }
    $id = (int) $wpdb->insert_id;
    bamero_log_event('notification_queued', array('notification_id' => $id, 'template_key' => sanitize_key($template_key), 'correlation_id' => $correlation));
    return $id;
}

function bamero_sms_provider_send($mobile, $template_key, array $payload, $idempotency_key)
{
    $result = apply_filters('bamero_sms_provider_send', null, $mobile, $template_key, $payload, $idempotency_key);
    if (null === $result) {
        return new WP_Error('sms_provider_not_configured', 'SMS provider is not configured.');
    }
    if (is_wp_error($result)) {
        return $result;
    }
    if (true === $result) {
        return array('provider_message_id' => 'provider_ack');
    }
    return is_array($result) ? $result : new WP_Error('sms_provider_failed', 'SMS provider rejected the request.');
}

/** Native SMS.ir adapter; credentials and template IDs stay in the environment. */
function bamero_sms_ir_provider_send($result, $mobile, $template_key, array $payload, $idempotency_key)
{
    if (null !== $result || 'sms_ir' !== strtolower((string) getenv('SMS_PROVIDER'))) {
        return $result;
    }
    // Production-only flag: SMS is disabled in staging unless explicitly enabled
    if (getenv('BAMERO_ENVIRONMENT') === 'staging' && getenv('BAMERO_ENABLE_SMS_IN_STAGING') !== 'true') {
        bamero_log_event('sms_blocked_staging', array('template_key' => sanitize_key($template_key)));
        return new WP_Error('sms_disabled_staging', 'SMS delivery is disabled in staging environment.');
    }
    $api_key = (string) getenv('SMS_IR_API_KEY');
    $template_env = 'SMS_IR_TEMPLATE_' . strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', $template_key));
    $template_id = (string) getenv($template_env);
    if (!$api_key || !$template_id) {
        return new WP_Error('sms_provider_not_configured', 'SMS.ir API key or template is missing.');
    }
    $base = rtrim((string) (getenv('SMS_IR_API_BASE_URL') ?: 'https://api.sms.ir/v1'), '/');
    $parameters = array();
    if (isset($payload['otp'])) {
        $parameters[] = array('name' => (string) (getenv('SMS_IR_OTP_PARAMETER') ?: 'Code'), 'value' => (string) $payload['otp']);
    }
    if (isset($payload['order_id'])) {
        $parameters[] = array('name' => (string) (getenv('SMS_IR_ORDER_PARAMETER') ?: 'OrderId'), 'value' => (string) $payload['order_id']);
    }
    // Owner-facing templates (contact_request / color_consultation): who asked and how to call back.
    if (isset($payload['name'])) {
        $parameters[] = array('name' => (string) (getenv('SMS_IR_NAME_PARAMETER') ?: 'Name'), 'value' => mb_substr((string) $payload['name'], 0, 25));
    }
    if (isset($payload['customer_mobile'])) {
        $parameters[] = array('name' => (string) (getenv('SMS_IR_MOBILE_PARAMETER') ?: 'Mobile'), 'value' => preg_replace('/\D+/', '', (string) $payload['customer_mobile']));
    }
    $response = wp_remote_post($base . '/send/verify', array(
        'timeout' => max(3, (int) (getenv('SMS_TIMEOUT') ?: 5)),
        'headers' => array('X-API-KEY' => $api_key, 'Accept' => 'application/json', 'Content-Type' => 'application/json'),
        'body' => wp_json_encode(array('mobile' => preg_replace('/\D+/', '', (string) $mobile), 'templateId' => (int) $template_id, 'parameters' => $parameters)),
    ));
    if (is_wp_error($response)) {
        return new WP_Error('sms_provider_http_error', 'SMS.ir request failed.');
    }
    $status = (int) wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($status < 200 || $status >= 300 || empty($body['status'])) {
        return new WP_Error('sms_provider_rejected', 'SMS.ir rejected the message.');
    }
    $message_id = isset($body['data']['messageId']) ? (string) $body['data']['messageId'] : hash('sha256', $idempotency_key);
    return array('provider_message_id' => sanitize_text_field($message_id));
}
add_filter('bamero_sms_provider_send', 'bamero_sms_ir_provider_send', 5, 5);

function bamero_process_notification_outbox($limit = 20)
{
    global $wpdb;
    bamero_ensure_notification_outbox();
    $table = bamero_notification_table_name();
    $now = current_time('mysql', true);
    $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE status = 'pending' AND scheduled_at <= %s AND (locked_at IS NULL OR locked_at < UTC_TIMESTAMP() - INTERVAL 5 MINUTE) ORDER BY id ASC LIMIT %d", $now, max(1, (int) $limit)));
    foreach ($rows as $row) {
        $locked = $wpdb->query($wpdb->prepare("UPDATE {$table} SET locked_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP() WHERE id = %d AND status = 'pending' AND (locked_at IS NULL OR locked_at < UTC_TIMESTAMP() - INTERVAL 5 MINUTE)", $row->id));
        if (1 !== $locked) {
            continue;
        }
        if (!empty($row->payload_expires_at) && strtotime($row->payload_expires_at . ' UTC') < time()) {
            $wpdb->update($table, array('status' => 'failed', 'last_error_code' => 'payload_expired', 'locked_at' => null, 'updated_at' => $now), array('id' => $row->id));
            continue;
        }
        $payload = bamero_open_notification_payload($row->payload_ciphertext);
        if (!is_array($payload) || empty($payload['mobile'])) {
            $wpdb->update($table, array('status' => 'failed', 'last_error_code' => 'payload_missing', 'locked_at' => null, 'updated_at' => $now), array('id' => $row->id));
            continue;
        }
        $result = bamero_sms_provider_send($payload['mobile'], $row->template_key, $payload, $row->idempotency_key);
        if (!is_wp_error($result)) {
            $wpdb->update($table, array('status' => 'sent', 'provider_message_id' => sanitize_text_field($result['provider_message_id'] ?? ''), 'locked_at' => null, 'updated_at' => $now), array('id' => $row->id));
            bamero_log_event('notification_sent', array('notification_id' => (int) $row->id, 'correlation_id' => $row->correlation_id));
            continue;
        }
        $retry = (int) $row->retry_count + 1;
        $status = $retry >= (int) $row->max_retries ? 'failed' : 'pending';
        $wpdb->update($table, array('status' => $status, 'retry_count' => $retry, 'last_error_code' => sanitize_key($result->get_error_code()), 'last_error_at' => $now, 'locked_at' => null, 'scheduled_at' => gmdate(BAMERO_DATETIME_FORMAT, time() + min(900, 15 * (2 ** min($retry, 6)))), 'updated_at' => $now), array('id' => $row->id));
    }
}
add_action('bamero_notification_worker', 'bamero_process_notification_outbox');

/**
 * Data retention: at most once a day, drop finalized (sent/failed) rows older
 * than 30 days and blank the encrypted payload of finalized rows (never
 * re-sent). Keeps the table small and limits stored PII.
 */
function bamero_purge_notification_outbox()
{
    if (get_transient('bamero_outbox_purged')) {
        return;
    }
    set_transient('bamero_outbox_purged', 1, DAY_IN_SECONDS);
    global $wpdb;
    $table = bamero_notification_table_name();
    $cutoff = gmdate(BAMERO_DATETIME_FORMAT, time() - 30 * DAY_IN_SECONDS);
    // Finalized rows are never re-sent, so their sealed payload is no longer needed.
    $wpdb->query($wpdb->prepare("UPDATE {$table} SET payload_ciphertext = %s WHERE status IN ('sent','failed') AND payload_ciphertext <> %s LIMIT %d", '', '', 1000));
    $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE status IN ('sent','failed') AND updated_at < %s LIMIT %d", $cutoff, 1000));
}
add_action('bamero_notification_worker', 'bamero_purge_notification_outbox', 20);
add_filter('cron_schedules', function ($schedules) {
    $schedules['minute'] = array('interval' => 60, 'display' => 'Every minute');
    return $schedules;
});
function bamero_schedule_notification_worker()
{
    if (!wp_next_scheduled('bamero_notification_worker')) {
        wp_schedule_event(time() + 60, 'minute', 'bamero_notification_worker');
    }
}
add_action('init', 'bamero_schedule_notification_worker', 20);

function bamero_queue_order_sms($order_id, $old_status, $new_status, $order)
{
    if (!$order || !function_exists('wc_get_order')) {
        return;
    }
    $mobile = $order->get_billing_phone();
    if (!$mobile) {
        return;
    }
    $map = array('processing' => 'order_processing', 'completed' => 'order_delivered', 'cancelled' => 'order_cancelled', 'failed' => 'payment_failed', 'refunded' => 'refund_completed');
    if (!isset($map[$new_status])) {
        return;
    }
    bamero_queue_notification($order_id, (int) $order->get_user_id(), $map[$new_status], array('mobile' => $mobile, 'order_id' => (int) $order_id));
}
add_action('woocommerce_order_status_changed', 'bamero_queue_order_sms', 20, 4);

// Mobile-only policy: block platform-generated CUSTOMER mail, but keep the
// store owner's own notifications (admin_email) and staff account mail
// (password recovery for shop managers / administrators, who are excluded
// from OTP-only login) alive. Everything else is refused at the boundary.
/**
 * True when ONE e-mail address may receive platform mail: the store owner's
 * admin_email, or a staff account (manage_woocommerce) — e.g. password
 * recovery for administrators / shop managers who are excluded from OTP login.
 */
function bamero_mail_recipient_allowed($address)
{
    $address = strtolower(trim((string) $address));
    if ('' === $address || !is_email($address)) {
        return false;
    }
    if ($address === strtolower(trim((string) get_option('admin_email')))) {
        return true;
    }
    $user = get_user_by('email', $address);
    return $user && user_can($user, 'manage_woocommerce');
}

/** Split "A <a@x>, b@y" strings / arrays into bare addresses. */
function bamero_mail_addresses($value)
{
    $items = is_array($value) ? $value : explode(',', (string) $value);
    $out = array();
    foreach ($items as $item) {
        $item = trim((string) $item);
        if ('' === $item) {
            continue;
        }
        $out[] = preg_match('/<([^>]+)>/', $item, $m) ? trim($m[1]) : $item;
    }
    return $out;
}

add_filter('pre_wp_' . 'mail', function ($null, $atts) {
    $recipients = bamero_mail_addresses(isset($atts['to']) ? $atts['to'] : '');
    $headers = isset($atts['headers']) ? (is_array($atts['headers']) ? $atts['headers'] : explode("\n", (string) $atts['headers'])) : array();
    foreach ($headers as $header) {
        if (preg_match('/^\s*(cc|bcc)\s*:\s*(.+)$/i', (string) $header, $m)) {
            $recipients = array_merge($recipients, bamero_mail_addresses($m[2]));
        }
    }
    if (!$recipients) {
        bamero_log_event('mail_blocked', array('reason' => 'mobile_only_policy'));
        return false;
    }
    foreach ($recipients as $address) {
        if (!bamero_mail_recipient_allowed($address)) {
            bamero_log_event('mail_blocked', array('reason' => 'mobile_only_policy'));
            return false;
        }
    }
    bamero_log_event('mail_allowed', array('reason' => 'owner_or_staff_notification'));
    return null; // fall through to normal wp_mail()
}, 10, 2);

/** Register Iranian currencies (IRR primary, IRT legacy) as first-class WooCommerce currencies with proper symbols. */
function bamero_register_ir_currencies($currencies)
{
    if (!isset($currencies['IRT'])) {
        $currencies['IRT'] = __('تومان ایران', 'bamero-production-core');
    }
    if (!isset($currencies['IRR'])) {
        $currencies['IRR'] = __('ریال ایران', 'bamero-production-core');
    }
    return $currencies;
}
add_filter('woocommerce_currencies', 'bamero_register_ir_currencies');

function bamero_register_ir_currency_symbol($symbol, $currency_code)
{
    if ('IRT' === $currency_code) {
        return 'تومان';
    }
    if ('IRR' === $currency_code) {
        return 'ریال';
    }
    return $symbol;
}
add_filter('woocommerce_currency_symbol', 'bamero_register_ir_currency_symbol', 10, 2);
