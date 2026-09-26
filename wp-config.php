<?php
/**
 * Bamero production wp-config — fail-closed secrets (SEC-01..04).
 * WPLANG removed (SEC-04).
 */

declare(strict_types=1);

/**
 * Require non-empty env; reject known placeholders. Uses die() before WP loads.
 */
function bamero_require_env(string $key): string {
    $value = getenv($key);
    if ($value === false || $value === '') {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        die('Configuration error: missing required environment variable: ' . $key);
    }
    if (stripos($value, '' . 'put-your-' . 'unique-phrase') !== false
        || in_array(strtolower($value), array('changeme', 'secret', 'password', 'null', 'root', ''), true)
    ) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        die('Configuration error: rejected placeholder/insecure value for: ' . $key);
    }
    return $value;
}

define('DB_NAME', bamero_require_env('DB_NAME'));
define('DB_USER', bamero_require_env('DB_USER'));
define('DB_PASSWORD', bamero_require_env('DB_PASSWORD'));
define('DB_HOST', bamero_require_env('DB_HOST'));
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

define('AUTH_KEY',         bamero_require_env('AUTH_KEY'));
define('SECURE_AUTH_KEY',  bamero_require_env('SECURE_AUTH_KEY'));
define('LOGGED_IN_KEY',    bamero_require_env('LOGGED_IN_KEY'));
define('NONCE_KEY',        bamero_require_env('NONCE_KEY'));
define('AUTH_SALT',        bamero_require_env('AUTH_SALT'));
define('SECURE_AUTH_SALT', bamero_require_env('SECURE_AUTH_SALT'));
define('LOGGED_IN_SALT',   bamero_require_env('LOGGED_IN_SALT'));
define('NONCE_SALT',       bamero_require_env('NONCE_SALT'));

$table_prefix = (getenv('TABLE_PREFIX') !== false && getenv('TABLE_PREFIX') !== '')
    ? getenv('TABLE_PREFIX')
    : 'wp_bamero_';

define('WP_DEBUG', filter_var(getenv('WP_DEBUG') !== false ? getenv('WP_DEBUG') : '0', FILTER_VALIDATE_BOOLEAN));
define('WP_DEBUG_DISPLAY', false);
define('WP_DEBUG_LOG', filter_var(getenv('WP_DEBUG_LOG') !== false ? getenv('WP_DEBUG_LOG') : '1', FILTER_VALIDATE_BOOLEAN));

define('DISALLOW_FILE_EDIT', true);
$dfm = getenv('DISALLOW_FILE_MODS');
define('DISALLOW_FILE_MODS', filter_var($dfm !== false ? $dfm : '1', FILTER_VALIDATE_BOOLEAN));
define('FORCE_SSL_ADMIN', true);
define('WP_CACHE', true);
define('CONCATENATE_SCRIPTS', false);

define('EMPTY_TRASH_DAYS', 14);
define('WP_POST_REVISIONS', 5);
define('AUTOSAVE_INTERVAL', 120);
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');

$env_type = getenv('WP_ENVIRONMENT_TYPE');
if ($env_type !== false && $env_type !== '') {
    define('WP_ENVIRONMENT_TYPE', $env_type);
}

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

require_once ABSPATH . 'wp-settings.php';
