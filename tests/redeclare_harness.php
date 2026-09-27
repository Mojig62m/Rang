<?php
/**
 * Empirical runtime harness.
 *
 * Loads the production-core plugin and the theme functions.php in the SAME
 * process, in the SAME order WordPress uses (plugins first, then the theme),
 * to prove there is no "Cannot redeclare" fatal error.
 *
 * WordPress core functions are stubbed as no-ops so the files can be loaded
 * outside a full WordPress bootstrap.
 */
error_reporting(E_ALL);
define('ABSPATH', __DIR__ . '/');
define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 3600);
define('DAY_IN_SECONDS', 86400);
define('BAMERO_THEME_VERSION', '2.0.0');

// --- WordPress stubs (no-ops) ---
function add_action(...$a) {}
function add_filter(...$a) {}
function remove_action(...$a) {}
function remove_filter(...$a) {}
function do_action(...$a) {}
function apply_filters($tag, $value = null) { return $value; }
function add_shortcode(...$a) {}
function register_nav_menus(...$a) {}
function register_nav_menu(...$a) {}
function add_theme_support(...$a) {}
function load_theme_textdomain(...$a) {}
function register_sidebar(...$a) {}
function wp_enqueue_style(...$a) {}
function wp_enqueue_script(...$a) {}
function wp_localize_script(...$a) {}
function wp_register_style(...$a) {}
function wp_register_script(...$a) {}
function get_option($k, $d = false) { return $d; }
function update_option(...$a) { return true; }
function get_theme_mod($k, $d = false) { return $d; }
function set_theme_mod(...$a) {}
function is_admin() { return false; }
function is_customize_preview() { return false; }
function __return_false() { return false; }
function __return_true() { return true; }
function __return_empty_array() { return array(); }
function get_template_directory_uri() { return ''; }
function get_template_directory() { return ''; }
function get_stylesheet_directory_uri() { return ''; }
function get_stylesheet_uri() { return ''; }
function get_bloginfo($k = '') { return ''; }
function home_url($p = '') { return 'https://example.test' . $p; }
function site_url($p = '') { return 'https://example.test' . $p; }
function esc_url($s) { return $s; }
function esc_url_raw($s) { return $s; }
function esc_attr($s) { return $s; }
function esc_html($s) { return $s; }
function esc_textarea($s) { return $s; }
function esc_js($s) { return $s; }
function wp_kses_post($s) { return $s; }
function wp_kses(...$a) { return ''; }
function sanitize_text_field($s) { return $s; }
function wp_unslash($s) { return $s; }
function wp_json_encode($v, $f = 0) { return json_encode($v, $f); }
function absint($n) { return abs((int) $n); }
function wp_strip_all_tags($s) { return strip_tags($s); }
function __($s, $d = null) { return $s; }
function _x($s, $c = null, $d = null) { return $s; }
function esc_html__($s, $d = null) { return $s; }
function esc_attr__($s, $d = null) { return $s; }
function esc_html_e($s, $d = null) { echo $s; }
function esc_attr_e($s, $d = null) { echo $s; }
function _e($s, $d = null) { echo $s; }
function is_wp_error($t) { return false; }
function get_terms(...$a) { return array(); }
function get_page_by_path(...$a) { return null; }
function get_permalink(...$a) { return ''; }
function get_the_title(...$a) { return ''; }
function is_search() { return false; }
function is_404() { return false; }
function is_singular() { return false; }
function is_front_page() { return false; }
function is_home() { return false; }
function is_product() { return false; }
function is_shop() { return false; }
function is_account_page() { return false; }
function is_cart() { return false; }
function is_checkout() { return false; }
function is_user_logged_in() { return false; }
function wc_get_page_permalink(...$a) { return ''; }
function wc_get_products(...$a) { return array(); }
function wp_head() {}
function wp_footer() {}
function nocache_headers() {}
function wp_die($m = '') { throw new RuntimeException('wp_die: ' . $m); }
function wp_send_json(...$a) {}
function wp_send_json_success(...$a) {}
function wp_send_json_error(...$a) {}
function register_rest_route(...$a) {}
function wp_next_scheduled(...$a) { return false; }
function wp_schedule_event(...$a) {}
function wp_mail(...$a) { return true; }
function wp_hash_password($p) { return 'hash:' . $p; }
function wp_check_password($p, $h) { return $h === 'hash:' . $p; }
function wp_set_auth_cookie(...$a) {}
function wp_set_current_user(...$a) {}
function get_user_by(...$a) { return false; }
function wp_insert_user(...$a) { return 1; }
function wp_create_user(...$a) { return 1; }
function current_time(...$a) { return time(); }
function get_transient($k) { return false; }
function set_transient(...$a) { return true; }
function delete_transient(...$a) { return true; }
function get_user_meta(...$a) { return ''; }
function update_user_meta(...$a) { return true; }
function wp_remote_post(...$a) { return array(); }
function wp_remote_retrieve_body(...$a) { return ''; }
function wp_remote_retrieve_response_code(...$a) { return 200; }
function is_email($e) { return (bool) filter_var($e, FILTER_VALIDATE_EMAIL); }
function sanitize_email($e) { return $e; }
function get_avatar_url(...$a) { return ''; }
function wp_nonce_field(...$a) {}
function wp_create_nonce(...$a) { return 'nonce'; }
function wp_verify_nonce(...$a) { return true; }
function check_ajax_referer(...$a) { return true; }
function is_ssl() { return true; }
function wp_parse_url($u, $c = -1) { return parse_url($u, $c); }
function wp_parse_args($a, $d = array()) { return array_merge($d, (array) $a); }
function shortcode_atts($d, $a, $s = '') { return array_merge($d, (array) $a); }
function has_shortcode(...$a) { return false; }
function get_query_var(...$a) { return ''; }
function wp_get_theme() { return new stdClass(); }
function get_locale() { return 'fa_IR'; }
function get_bloginfo_language_attributes() { return 'lang="fa-IR" dir="rtl"'; }
function language_attributes() { echo 'lang="fa-IR" dir="rtl"'; }
function body_class(...$a) {}
function post_class(...$a) {}
function get_header(...$a) {}
function get_footer(...$a) {}
function get_sidebar(...$a) {}
function get_search_form(...$a) {}
function paginate_links(...$a) { return ''; }
function wp_nav_menu(...$a) {}
function has_nav_menu(...$a) { return false; }
function wp_get_nav_menu_items(...$a) { return array(); }
function get_the_ID() { return 0; }
function get_post_type() { return ''; }
function wc_get_cart_url() { return ''; }
function wc_get_checkout_url() { return ''; }
function wc_get_account_endpoint_url(...$a) { return ''; }
function wc_get_product(...$a) { return null; }
function wc_price($p) { return (string) $p; }
function wc_get_loop_prop(...$a) { return ''; }
function wc_set_loop_prop(...$a) {}
function wc_get_template_part(...$a) {}
function wc_get_template(...$a) {}
function is_woocommerce() { return false; }
function WC() { return new stdClass(); }
function wc_get_customer_order_notes(...$a) { return array(); }

echo "STEP 1: loading bamero-production-core.php (plugin)...\n";
require __DIR__ . '/../wp-content/plugins/bamero-production-core/bamero-production-core.php';
echo "STEP 1 OK. bamero_csp_nonce exists? " . (function_exists('bamero_csp_nonce') ? 'YES' : 'NO') . "\n";

echo "STEP 2: loading theme functions.php...\n";
require __DIR__ . '/../wp-content/themes/bamero/functions.php';
echo "STEP 2 OK (no fatal).\n";

echo "\nRESULT: PASS — plugin + theme loaded together with no redeclare fatal error.\n";
