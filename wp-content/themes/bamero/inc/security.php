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
function bamero_remove_wp_version_rss() {
    return '';
}
add_filter('the_generator', 'bamero_remove_wp_version_rss');

// Disable XML-RPC
add_filter('xmlrpc_enabled', '__return_false');

// =============================================================================
// SECURITY HEADERS (canonical definitions live in bamero-production-core plugin)
// Theme keeps fallback guards only for backward compatibility.
// =============================================================================

function bamero_script_loader_nonce($tag, $handle, $src) {
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
function bamero_inline_script_nonce($attributes) {
    if (is_admin()) {
        return $attributes;
    }
    if (empty($attributes['nonce'])) {
        $attributes['nonce'] = bamero_csp_nonce();
    }
    return $attributes;
}
add_filter('wp_inline_script_attributes', 'bamero_inline_script_nonce');

/** Disable author enumeration */
function bamero_disable_author_enum($redirect, $request) {
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
function bamero_restrict_rest_user_endpoints($endpoints) {
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
function bamero_remove_sitemap_users_provider($provider, $name) {
    return $name === 'users' ? false : $provider;
}
add_filter('wp_sitemaps_add_provider', 'bamero_remove_sitemap_users_provider', 10, 2);

/** Do not leak author name/URL through public oEmbed responses. */
function bamero_oembed_hide_author($data) {
    unset($data['author_name'], $data['author_url']);
    return $data;
}
add_filter('oembed_response_data', 'bamero_oembed_hide_author');
