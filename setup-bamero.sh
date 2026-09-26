#!/bin/bash

# اسکریپت خودکار برای آماده‌سازی پروژه بامرو
# این اسکریپت تمام تنظیمات لازم را برای استقرار انجام می‌دهد

# رنگ‌های استفاده شده در خروجی
green='\033[0;32m'
red='\033[0;31m'
yellow='\033[1;33m'
nc='\033[0m' # No Color

# تابع برای چاپ پیام‌های موفقیت
echo_success() {
    echo -e "${green}[✓]${nc} $1"
}

# تابع برای چاپ پیام‌های خطا
echo_error() {
    echo -e "${red}[✗]${nc} $1"
}

# تابع برای چاپ پیام‌های هشدار
echo_warning() {
    echo -e "${yellow}[!]${nc} $1"
}

# تابع برای چاپ عنوان
echo_title() {
    echo -e "\n${yellow}=== $1 ===${nc}\n"
}

# شروع اسکریپت
echo_title "اسکریپت خودکار برای آماده‌سازی پروژه بامرو"

# 1. تولید کلیدهای امنیتی جدید
echo_title "تولید کلیدهای امنیتی جدید"
if [ ! -f "wp-config.php" ]; then
    echo "فایل wp-config.php یافت نشد. در حال ایجاد فایل با تنظیمات امنیتی..."
    
    # تولید کلیدهای امنیتی جدید
    AUTH_KEY=$(curl -s https://api.wordpress.org/secret-key/1.1/salt/ | grep "define('AUTH_KEY'" | sed "s/.*'//;s/'.*//")
    SECURE_AUTH_KEY=$(curl -s https://api.wordpress.org/secret-key/1.1/salt/ | grep "define('SECURE_AUTH_KEY'" | sed "s/.*'//;s/'.*//")
    LOGGED_IN_KEY=$(curl -s https://api.wordpress.org/secret-key/1.1/salt/ | grep "define('LOGGED_IN_KEY'" | sed "s/.*'//;s/'.*//")
    NONCE_KEY=$(curl -s https://api.wordpress.org/secret-key/1.1/salt/ | grep "define('NONCE_KEY'" | sed "s/.*'//;s/'.*//")
    AUTH_SALT=$(curl -s https://api.wordpress.org/secret-key/1.1/salt/ | grep "define('AUTH_SALT'" | sed "s/.*'//;s/'.*//")
    SECURE_AUTH_SALT=$(curl -s https://api.wordpress.org/secret-key/1.1/salt/ | grep "define('SECURE_AUTH_SALT'" | sed "s/.*'//;s/'.*//")
    LOGGED_IN_SALT=$(curl -s https://api.wordpress.org/secret-key/1.1/salt/ | grep "define('LOGGED_IN_SALT'" | sed "s/.*'//;s/'.*//")
    NONCE_SALT=$(curl -s https://api.wordpress.org/secret-key/1.1/salt/ | grep "define('NONCE_SALT'" | sed "s/.*'//;s/'.*//")
    
    # ایجاد فایل wp-config.php با کلیدهای امنیتی جدید
    cat > wp-config.php <<EOL
<?php
/**
 * The base configuration for WordPress
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'bamero_db');

/** MySQL database username */
define('DB_USER', 'bamero_user');

/** MySQL database password */
define('DB_PASSWORD', 'YOUR_SECURE_DB_PASSWORD_HERE');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The database collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 */
define('AUTH_KEY',         '$AUTH_KEY');
define('SECURE_AUTH_KEY',  '$SECURE_AUTH_KEY');
define('LOGGED_IN_KEY',    '$LOGGED_IN_KEY');
define('NONCE_KEY',        '$NONCE_KEY');
define('AUTH_SALT',        '$AUTH_SALT');
define('SECURE_AUTH_SALT', '$SECURE_AUTH_SALT');
define('LOGGED_IN_SALT',   '$LOGGED_IN_SALT');
define('NONCE_SALT',       '$NONCE_SALT');

/**#@-*/

/**
 * WordPress database table prefix.
 */
\$table_prefix = 'wp_bamero_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 */
define('WP_DEBUG', false);
define('WP_DEBUG_DISPLAY', false);
define('WP_DEBUG_LOG', true);

/**
 * Security settings
 */
define('DISALLOW_FILE_EDIT', true);
define('DISALLOW_FILE_MODS', true);
define('FORCE_SSL', true);
define('WP_CACHE', true);

/**
 * Hide WordPress version
 */
remove_action('wp_head', 'wp_generator');

/**
 * Memory limit
 */
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');

/**
 * Absolute path to the WordPress directory.
 */
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
EOL

    echo_success "فایل wp-config.php با کلیدهای امنیتی جدید ایجاد شد."
else
    echo_warning "فایل wp-config.php قبلاً وجود دارد. لطفاً آن را به صورت دستی بررسی کنید."
fi

# 2. ایجاد فایل .htaccess با قوانین امنیتی و عملکرد
echo_title "ایجاد فایل .htaccess با قوانین امنیتی و عملکرد"
if [ ! -f ".htaccess" ]; then
    echo "فایل .htaccess یافت نشد. در حال ایجاد فایل با قوانین امنیتی..."
    
    cat > .htaccess <<EOL
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress

# Redirect HTTP to HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Protect sensitive files
<files wp-config.php>
  order allow,deny
  deny from all
</files>

<files xmlrpc.php>
  order deny,allow
  deny from all
</files>

<files readme.html>
  order allow,deny
  deny from all
</files>

<files license.txt>
  order allow,deny
  deny from all
</files>

# Restrict access to wp-admin
<FilesMatch "wp-admin">
  Order Deny,Allow
  Deny from all
  Allow from YOUR_IP_ADDRESS
</FilesMatch>

# Static cache
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/jpg "access 1 year"
  ExpiresByType image/jpeg "access 1 year"
  ExpiresByType image/gif "access 1 year"
  ExpiresByType image/png "access 1 year"
  ExpiresByType text/css "access 1 month"
  ExpiresByType text/html "access 1 month"
  ExpiresByType application/pdf "access 1 month"
  ExpiresByType text/x-javascript "access 1 month"
  ExpiresDefault "access 1 month"
</IfModule>

# Gzip compression
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html
  AddOutputFilterByType DEFLATE text/css
  AddOutputFilterByType DEFLATE text/javascript
  AddOutputFilterByType DEFLATE text/xml
  AddOutputFilterByType DEFLATE text/plain
  AddOutputFilterByType DEFLATE image/x-icon
  AddOutputFilterByType DEFLATE image/svg+xml
  AddOutputFilterByType DEFLATE application/rss+xml
  AddOutputFilterByType DEFLATE application/javascript
  AddOutputFilterByType DEFLATE application/x-javascript
  AddOutputFilterByType DEFLATE application/xml
  AddOutputFilterByType DEFLATE application/xhtml+xml
  AddOutputFilterByType DEFLATE application/rdf+xml
  AddOutputFilterByType DEFLATE application/atom+xml
  AddOutputFilterByType DEFLATE application/json
  AddOutputFilterByType DEFLATE application/ld+json
</IfModule>

# Hotlinking protection
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteCond %{HTTP_REFERER} !^https://bamero.ir/.* [NC]
  RewriteCond %{HTTP_REFERER} !^https://bamero.ir$ [NC]
  RewriteCond %{HTTP_REFERER} !^https://www.bamero.ir/.* [NC]
  RewriteCond %{HTTP_REFERER} !^https://www.bamero.ir$ [NC]
  RewriteRule \.(jpg|jpeg|png|gif)$ - [NC,F,L]
</IfModule>
EOL

    echo_success "فایل .htaccess با قوانین امنیتی و عملکرد ایجاد شد."
else
    echo_warning "فایل .htaccess قبلاً وجود دارد. لطفاً آن را به صورت دستی بررسی کنید."
fi

# 3. ایجاد فایل robots.txt
echo_title "ایجاد فایل robots.txt"
if [ ! -f "robots.txt" ]; then
    echo "فایل robots.txt یافت نشد. در حال ایجاد فایل..."
    
    cat > robots.txt <<EOL
User-agent: *
Disallow: /wp-admin/
Disallow: /wp-includes/
Disallow: /wp-content/plugins/
Disallow: /wp-content/themes/
Disallow: /readme.html
Disallow: /license.txt
Disallow: /xmlrpc.php

Allow: /wp-admin/admin-ajax.php
Allow: /wp-content/uploads/

Sitemap: https://bamero.ir/sitemap_index.xml
EOL

    echo_success "فایل robots.txt ایجاد شد."
else
    echo_warning "فایل robots.txt قبلاً وجود دارد. لطفاً آن را به صورت دستی بررسی کنید."
fi

# 4. ایجاد پوشه‌های لازم
echo_title "ایجاد پوشه‌های لازم"
mkdir -p wp-content/themes/bamero/css
mkdir -p wp-content/themes/bamero/js
mkdir -p wp-content/themes/bamero/fonts
mkdir -p wp-content/themes/bamero/images
mkdir -p wp-content/plugins
mkdir -p wp-content/uploads

echo_success "پوشه‌های لازم ایجاد شدند."

# 5. دانلود فونت IRANSans
echo_title "دانلود فونت IRANSans"
if [ ! -f "wp-content/themes/bamero/fonts/IRANSans.woff2" ]; then
    echo "در حال دانلود فونت IRANSans..."
    wget -q -O wp-content/themes/bamero/fonts/IRANSans.woff2 https://cdn.font-store.ir/IRANSans.woff2
    if [ $? -eq 0 ]; then
        echo_success "فونت IRANSans دانلود شد."
    else
        echo_error "دانلود فونت IRANSans با خطا مواجه شد. لطفاً به صورت دستی دانلود کنید."
    fi
else
    echo_warning "فونت IRANSans قبلاً وجود دارد."
fi

# 6. ایجاد فایل‌های CSS
echo_title "ایجاد فایل‌های CSS"

# فایل variables.css
if [ ! -f "wp-content/themes/bamero/css/variables.css" ]; then
    cat > wp-content/themes/bamero/css/variables.css <<EOL
:root {
    /* Colors - Premium Theme (Blue, White, Gold) */
    --color-primary: #1E3A8A;
    --color-primary-dark: #1E293B;
    --color-primary-light: #3B82F6;
    --color-secondary: #F59E0B;
    --color-secondary-dark: #D97706;
    --color-secondary-light: #FBBF24;
    --color-surface: #FFFFFF;
    --color-background: #F8FAFC;
    --color-text: #1E293B;
    --color-text-light: #64748B;
    --color-border: #E2E8F0;
    --color-success: #10B981;
    --color-warning: #F59E0B;
    --color-error: #EF4444;
    --color-info: #3B82F6;

    /* Typography */
    --font-primary: 'IRANSans', Tahoma, Arial, sans-serif;
    --font-size-xs: 12px;
    --font-size-sm: 14px;
    --font-size-base: 16px;
    --font-size-lg: 18px;
    --font-size-xl: 20px;
    --font-size-xxl: 24px;

    /* Spacing */
    --spacing-xs: 0.25rem;
    --spacing-sm: 0.5rem;
    --spacing-md: 1rem;
    --spacing-lg: 1.5rem;
    --spacing-xl: 2rem;
    --spacing-xxl: 3rem;

    /* Border Radius */
    --border-radius-sm: 4px;
    --border-radius-md: 8px;
    --border-radius-lg: 12px;
    --border-radius-full: 50%;

    /* Shadows */
    --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.1);
    --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);

    /* Transitions */
    --transition-fast: 0.15s ease;
    --transition-normal: 0.3s ease;
}
EOL
    echo_success "فایل variables.css ایجاد شد."
else
    echo_warning "فایل variables.css قبلاً وجود دارد."
fi

# فایل style.css
if [ ! -f "wp-content/themes/bamero/style.css" ]; then
    cat > wp-content/themes/bamero/style.css <<EOL
/*
Theme Name: Bamero
Theme URI: https://github.com/mojirt37/refactored-octo-waddle
Author: Moji Moji
Author URI: https://github.com/mojirt37
Description: تم سفارشی برای فروشگاه اینترنتی بامرو - پشتیبانی کامل از RTL و زبان فارسی.
Version: 1.0.0
License: MIT
Text Domain: bamero
*/

@import url("css/variables.css");

@font-face {
    font-family: 'IRANSans';
    src: url('fonts/IRANSans.woff2') format('woff2');
    font-weight: normal;
    font-style: normal;
    font-display: swap;
}

body {
    font-family: var(--font-primary);
    direction: rtl;
    text-align: right;
    margin: 0;
    padding: 0;
    line-height: 1.6;
    color: var(--color-text);
    background-color: var(--color-background);
}

header {
    background-color: var(--color-surface);
    box-shadow: var(--shadow-sm);
    padding: var(--spacing-sm) 0;
    position: sticky;
    top: 0;
    z-index: 1000;
}

main {
    padding: var(--spacing-md);
    max-width: 1200px;
    margin: 0 auto;
}

footer {
    background-color: var(--color-primary);
    color: var(--color-surface);
    text-align: center;
    padding: var(--spacing-md);
    margin-top: var(--spacing-md);
}

.button {
    display: inline-block;
    padding: var(--spacing-sm) var(--spacing-md);
    background-color: var(--color-primary);
    color: var(--color-surface);
    border: none;
    border-radius: var(--border-radius-sm);
    cursor: pointer;
    font-size: var(--font-size-base);
    transition: all var(--transition-fast);
}

.button:hover {
    background-color: var(--color-primary-dark);
}

.hero-banner {
    background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
    color: var(--color-surface);
    padding: var(--spacing-xxl) 0;
    text-align: center;
}

.hero-banner h1 {
    font-size: var(--font-size-xxl);
    margin-bottom: var(--spacing-sm);
}

.rtl {
    direction: rtl;
}
EOL
    echo_success "فایل style.css ایجاد شد."
else
    echo_warning "فایل style.css قبلاً وجود دارد."
fi

# 7. ایجاد فایل‌های PHP تم
echo_title "ایجاد فایل‌های PHP تم"

# فایل functions.php
if [ ! -f "wp-content/themes/bamero/functions.php" ]; then
    cat > wp-content/themes/bamero/functions.php <<EOL
<?php
/**
 * Bamero Theme Functions
 */

// Define theme constants
define('BAMERO_VERSION', '1.0.0');

// Security: Hide WordPress version
remove_action('wp_head', 'wp_generator');

// Disable file editing from WordPress admin
define('DISALLOW_FILE_EDIT', true);
define('DISALLOW_FILE_MODS', true);

// Theme setup
function bamero_setup() {
    load_theme_textdomain('bamero', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('custom-logo');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('rtl');
    
    register_nav_menus(array(
        'primary' => __('منوی اصلی', 'bamero'),
    ));
}
add_action('after_setup_theme', 'bamero_setup');

// Enqueue styles and scripts
function bamero_scripts() {
    wp_enqueue_style('bamero-variables', get_template_directory_uri() . '/css/variables.css', array(), BAMERO_VERSION);
    wp_enqueue_style('bamero-style', get_stylesheet_uri(), array('bamero-variables'), BAMERO_VERSION);
    wp_enqueue_script('bamero-script', get_template_directory_uri() . '/js/main.js', array('jquery'), BAMERO_VERSION, true);
}
add_action('wp_enqueue_scripts', 'bamero_scripts');
EOL
    echo_success "فایل functions.php ایجاد شد."
else
    echo_warning "فایل functions.php قبلاً وجود دارد."
fi

# فایل header.php
if [ ! -f "wp-content/themes/bamero/header.php" ]; then
    cat > wp-content/themes/bamero/header.php <<EOL
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <header class="rtl">
        <div class="container">
            <div class="header-top-bar">
                <div class="top-bar-left">
                    <span>📞 +98 21 1234 5678</span>
                </div>
                <div class="top-bar-right">
                    <?php if (is_user_logged_in()) : ?>
                        <a href="<?php echo wc_get_page_permalink('myaccount'); ?>">حساب کاربری</a>
                        <a href="<?php echo wc_get_cart_url(); ?>">سبد خرید</a>
                    <?php else : ?>
                        <a href="<?php echo wc_get_page_permalink('myaccount'); ?>">ورود / ثبت‌نام</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="header-main">
                <div class="logo">
                    <?php
                    if (has_custom_logo()) {
                        the_custom_logo();
                    } else {
                        echo '<h1><a href="' . esc_url(home_url('/')) . '">بامرو</a></h1>';
                    }
                    ?>
                </div>
                <nav class="main-navigation">
                    <?php wp_nav_menu(array('theme_location' => 'primary')); ?>
                </nav>
                <div class="header-cta">
                    <a href="/consultation" class="button button-secondary">مشاوره رنگ</a>
                </div>
            </div>
        </div>
    </header>
    <main>
EOL
    echo_success "فایل header.php ایجاد شد."
else
    echo_warning "فایل header.php قبلاً وجود دارد."
fi

# فایل footer.php
if [ ! -f "wp-content/themes/bamero/footer.php" ]; then
    cat > wp-content/themes/bamero/footer.php <<EOL
    </main>
    <footer class="rtl">
        <div class="container">
            <div class="footer-widgets">
                <div class="footer-widget">
                    <h3>درباره بامرو</h3>
                    <p>بامرو با سال‌ها تجربه در زمینه فروش رنگ و محصولات ساختمانی، آماده ارائه بهترین خدمات به شما مشتریان عزیز است.</p>
                </div>
                <div class="footer-widget">
                    <h3>لینک‌های سریع</h3>
                    <ul>
                        <li><a href="/">خانه</a></li>
                        <li><a href="/shop">فروشگاه</a></li>
                        <li><a href="/about">درباره ما</a></li>
                        <li><a href="/contact">تماس با ما</a></li>
                    </ul>
                </div>
                <div class="footer-widget">
                    <h3>تماس با ما</h3>
                    <ul>
                        <li>📞 +98 21 1234 5678</li>
                        <li>✉️ info@bamero.ir</li>
                        <li>📍 تهران، خیابان ولیعصر</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> بامرو. تمام حقوق محفوظ است.</p>
            </div>
        </div>
    </footer>
    <?php wp_footer(); ?>
</body>
</html>
EOL
    echo_success "فایل footer.php ایجاد شد."
else
    echo_warning "فایل footer.php قبلاً وجود دارد."
fi

# فایل index.php
if [ ! -f "wp-content/themes/bamero/index.php" ]; then
    cat > wp-content/themes/bamero/index.php <<EOL
<?php
/**
 * The main template file
 */

get_header();
?>

<main class="rtl">
    <section class="hero-banner">
        <div class="container">
            <h1>به فروشگاه بامرو خوش آمدید</h1>
            <p>مرجع رنگ و محصولات ساختمانی در ایران</p>
            <div class="hero-buttons">
                <a href="/shop" class="button">مشاهده محصولات</a>
                <a href="/consultation" class="button button-secondary">دریافت مشاوره رایگان</a>
            </div>
        </div>
    </section>
</main>

<?php
get_footer();
EOL
    echo_success "فایل index.php ایجاد شد."
else
    echo_warning "فایل index.php قبلاً وجود دارد."
fi

# فایل main.js
if [ ! -f "wp-content/themes/bamero/js/main.js" ]; then
    cat > wp-content/themes/bamero/js/main.js <<EOL
jQuery(document).ready(function($) {
    console.log("Bamero theme loaded successfully!");
    
    // Mobile menu toggle
    $('.mobile-menu-toggle').on('click', function() {
        $('.main-navigation').toggleClass('mobile-active');
    });
    
    // Scroll to top
    $(window).on('scroll', function() {
        if ($(this).scrollTop() > 100) {
            $('.scroll-to-top').fadeIn();
        } else {
            $('.scroll-to-top').fadeOut();
        }
    });
    
    $('.scroll-to-top').on('click', function(e) {
        e.preventDefault();
        $('html, body').animate({ scrollTop: 0 }, 'smooth');
    });
});
EOL
    echo_success "فایل main.js ایجاد شد."
else
    echo_warning "فایل main.js قبلاً وجود دارد."
fi

# 8. ایجاد فایل‌های WooCommerce
echo_title "ایجاد فایل‌های WooCommerce"

# فایل woocommerce.php
if [ ! -f "wp-content/themes/bamero/woocommerce.php" ]; then
    cat > wp-content/themes/bamero/woocommerce.php <<EOL
<?php
/**
 * WooCommerce Template Overrides for Bamero Theme
 */

if (!class_exists('WooCommerce')) {
    return;
}

// Remove default WooCommerce styles
add_filter('woocommerce_enqueue_styles', '__return_empty_array');

// Enqueue custom WooCommerce styles
function bamero_woocommerce_styles() {
    wp_enqueue_style('bamero-woocommerce', get_template_directory_uri() . '/css/woocommerce.css', array('bamero-style'), BAMERO_VERSION);
    if (is_rtl()) {
        wp_enqueue_style('bamero-woocommerce-rtl', get_template_directory_uri() . '/css/woocommerce-rtl.css', array('bamero-woocommerce'), BAMERO_VERSION);
    }
}
add_action('wp_enqueue_scripts', 'bamero_woocommerce_styles');

// Change WooCommerce texts to Persian
function bamero_woocommerce_persian_text($translated_text, $text, $domain) {
    if ($domain === 'woocommerce') {
        switch ($translated_text) {
            case 'Add to cart':
                $translated_text = 'افزودن به سبد خرید';
                break;
            case 'Shop':
                $translated_text = 'فروشگاه';
                break;
            case 'Cart':
                $translated_text = 'سبد خرید';
                break;
        }
    }
    return $translated_text;
}
add_filter('gettext', 'bamero_woocommerce_persian_text', 20, 3);
EOL
    echo_success "فایل woocommerce.php ایجاد شد."
else
    echo_warning "فایل woocommerce.php قبلاً وجود دارد."
fi

# فایل woocommerce.css
if [ ! -f "wp-content/themes/bamero/css/woocommerce.css" ]; then
    cat > wp-content/themes/bamero/css/woocommerce.css <<EOL
.woocommerce-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem;
}

.woocommerce-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 2rem;
    margin-bottom: 2rem;
    padding: 1rem;
    background-color: #f5f5f5;
    border-radius: 5px;
}

.product-card {
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    text-align: center;
}
EOL
    echo_success "فایل woocommerce.css ایجاد شد."
else
    echo_warning "فایل woocommerce.css قبلاً وجود دارد."
fi

# فایل woocommerce-rtl.css
if [ ! -f "wp-content/themes/bamero/css/woocommerce-rtl.css" ]; then
    cat > wp-content/themes/bamero/css/woocommerce-rtl.css <<EOL
.woocommerce {
    direction: rtl;
    text-align: right;
}

.woocommerce-filters {
    direction: rtl;
    text-align: right;
}

.product-card {
    text-align: right;
}
EOL
    echo_success "فایل woocommerce-rtl.css ایجاد شد."
else
    echo_warning "فایل woocommerce-rtl.css قبلاً وجود دارد."
fi

# 9. ایجاد فایل‌های پلاگین
echo_title "ایجاد فایل‌های پلاگین"

# پوشه پلاگین سفارشی
mkdir -p wp-content/plugins/bamero-custom-plugin

# فایل اصلی پلاگین
if [ ! -f "wp-content/plugins/bamero-custom-plugin/bamero-custom-plugin.php" ]; then
    cat > wp-content/plugins/bamero-custom-plugin/bamero-custom-plugin.php <<EOL
<?php
/**
 * Plugin Name: Bamero Custom Plugin
 * Description: پلاگین سفارشی برای فروشگاه بامرو
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add custom product fields
function bamero_add_custom_product_fields() {
    woocommerce_wp_text_input(array(
        'id' => '_product_color_code',
        'label' => 'کد رنگ',
        'placeholder' => 'مثال: #FFFFFF',
        'desc_tip' => true,
        'description' => 'کد رنگ محصول را وارد کنید.',
    ));
}
add_action('woocommerce_product_options_general_product_data', 'bamero_add_custom_product_fields');

// Save custom product fields
function bamero_save_custom_product_fields($post_id) {
    $product_color_code = isset($_POST['_product_color_code']) ? sanitize_text_field($_POST['_product_color_code']) : '';
    update_post_meta($post_id, '_product_color_code', $product_color_code);
}
add_action('woocommerce_process_product_meta', 'bamero_save_custom_product_fields');
EOL
    echo_success "فایل bamero-custom-plugin.php ایجاد شد."
else
    echo_warning "فایل bamero-custom-plugin.php قبلاً وجود دارد."
fi

# 10. کامیت و پوش تمام تغییرات
echo_title "کامیت و پوش تمام تغییرات"

# اضافه کردن تمام فایل‌ها به git
git add .

# کامیت تمام تغییرات
git commit -m "feat: setup Bamero project with security, theme, and WooCommerce configurations"

# پوش تمام تغییرات
git push origin main

echo_success "تمام تغییرات با موفقیت کامیت و پوش شدند."

echo_title "اتمام اسکریپت"
echo_success "اسکریپت با موفقیت اجرا شد! تمام فایل‌های پایه برای پروژه بامرو ایجاد و کامیت شدند."
echo_warning "لطفاً فایل‌ها را بررسی کرده و در صورت نیاز، تنظیمات را کامل کنید."
