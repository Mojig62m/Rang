<?php

/**
 * Bamero Theme — Security
 *
 * Hardening: version disclosure removal, XML-RPC off, CSP nonces,
 * security headers and author-enumeration protection. bamero_csp_nonce()
 * and bamero_security_headers() stay function_exists()-guarded because the
 * canonical definitions live in the bamero-production-core plugin.
 *
 * @package Bamero
 */

defined('ABSPATH') || exit;

// ===== SECURITY IMPROVEMENTS =====

// Remove WordPress version from RSS feeds
function bamero_remove_wp_version_rss()
{
    return '';
}
add_filter('the_generator', 'bamero_remove_wp_version_rss');

// =============================================================================
// SECURITY HEADERS (canonical definitions live in bamero-production-core plugin)
// Theme keeps fallback guards only for backward compatibility.
// =============================================================================

/**
 * Fallback CSP nonce source when bamero-production-core is not active.
 * The plugin (loaded first) owns the canonical definition and the CSP header;
 * without it there is no CSP, but every nonce consumer below must still work.
 */
if (!function_exists('bamero_csp_nonce')) {
    function bamero_csp_nonce()
    {
        static $nonce = null;
        if (null === $nonce) {
            $nonce = base64_encode(random_bytes(16));
        }
        return $nonce;
    }
}

function bamero_script_loader_nonce($tag, $handle, $src)
{
    if (is_admin()) {
        return $tag;
    }
    $nonce = esc_attr(bamero_csp_nonce());
    if (strpos($tag, ' nonce=') === false) {
        $tag = str_replace('<script ', '<script nonce="' . $nonce . '" ', $tag);
    }
    return $tag;
}
add_filter('script_loader_tag', 'bamero_script_loader_nonce', 20, 3);

/** Add CSP nonce to WordPress-generated inline scripts (WP 6.4+). */
function bamero_inline_script_nonce($attributes)
{
    if (is_admin()) {
        return $attributes;
    }
    if (empty($attributes['nonce'])) {
        $attributes['nonce'] = bamero_csp_nonce();
    }
    return $attributes;
}
add_filter('wp_inline_script_attributes', 'bamero_inline_script_nonce');

/**
 * WooCommerce prints its "woocommerce-no-js → woocommerce-js" body-class
 * switch as a raw inline <script> (wc_no_js) that carries no CSP nonce, so
 * the strict script-src policy blocks it and JS-only UI stays hidden.
 * Replace it with the same snippet printed through wp_print_inline_script_tag(),
 * which receives the nonce via the wp_inline_script_attributes filter.
 */
function bamero_wc_no_js_nonced()
{
    if (!function_exists('wc_no_js') || false === has_action('wp_footer', 'wc_no_js')) {
        return;
    }
    remove_action('wp_footer', 'wc_no_js');
    wp_print_inline_script_tag("(function(){var b=document.body;b.className=b.className.replace(/woocommerce-no-js/,'woocommerce-js');})();");
}
add_action('wp_footer', 'bamero_wc_no_js_nonced', 0);

/** Disable author enumeration */
function bamero_disable_author_enum($redirect, $request)
{
    if (preg_match('/\?author=([0-9]*)/i', $request)) {
        return home_url('/');
    }
    return $redirect;
}
add_filter('redirect_canonical', 'bamero_disable_author_enum', 10, 2);

/**
 * Block REST user enumeration for anonymous visitors (/wp/v2/users*).
 * Logged-in users (block editor, admin) and the WooCommerce Store API
 * (/wc/store/*) are unaffected.
 */
function bamero_restrict_rest_user_endpoints($endpoints)
{
    if (is_user_logged_in()) {
        return $endpoints;
    }
    foreach (array_keys($endpoints) as $route) {
        if (strpos($route, '/wp/v2/users') === 0) {
            unset($endpoints[$route]);
        }
    }
    return $endpoints;
}
add_filter('rest_endpoints', 'bamero_restrict_rest_user_endpoints');

/** Keep author logins out of the core XML sitemap (wp-sitemap-users-*.xml). */
function bamero_remove_sitemap_users_provider($provider, $name)
{
    return $name === 'users' ? false : $provider;
}
add_filter('wp_sitemaps_add_provider', 'bamero_remove_sitemap_users_provider', 10, 2);

/** Do not leak author name/URL through public oEmbed responses. */
function bamero_oembed_hide_author($data)
{
    unset($data['author_name'], $data['author_url']);
    return $data;
}
add_filter('oembed_response_data', 'bamero_oembed_hide_author');
