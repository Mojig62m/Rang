<?php
/**
 * Plugin Name: Bamero WooCommerce Setup
 * Description: ایجاد دسته‌بندی، برچسب و محصولات کاتالوگ بامرو + تنظیمات پایه ووکامرس.
 * Version: 1.3.0
 * Author: Bamero
 * Text Domain: bamero-woocommerce-setup
 * Requires Plugins: woocommerce
 */
defined('ABSPATH') || exit;

define('BAMERO_WC_SETUP_VERSION', '1.3.0');

function bamero_wc_setup_schema_validate() {
    if (!class_exists('WooCommerce')) return false;
    if (!function_exists('wp_insert_post') || !function_exists('wp_insert_term') || !function_exists('update_option')) return false;
    $currency = 'IRT';
    $country = 'IR:TE';
    $weight = 'kg';
    $dimension = 'cm';
    return in_array($currency, array('IRT', 'IRR'), true)
        && (bool) preg_match('/^[A-Z]{2}:[A-Z]{2}$/', $country)
        && in_array($weight, array('kg', 'g'), true)
        && in_array($dimension, array('cm', 'm'), true);
}

function bamero_wc_setup_require_schema() {
    if (!bamero_wc_setup_schema_validate()) {
        return new WP_Error('setup_schema_invalid', 'WooCommerce setup schema validation failed before database writes.');
    }
    $GLOBALS['bamero_wc_setup_schema_validated'] = true;
    return true;
}

function bamero_wc_setup_schema_is_validated() {
    return !empty($GLOBALS['bamero_wc_setup_schema_validated']);
}

function bamero_wc_setup_activate() {
    if (true !== bamero_wc_setup_require_schema()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(esc_html__('اعتبارسنجی schema تنظیمات ووکامرس ناموفق بود؛ هیچ write انجام نشد.', 'bamero-woocommerce-setup'));
    }
    bamero_create_product_categories();
    bamero_create_product_tags();
    bamero_prune_excess_seed_data();
    bamero_create_catalog_products();
    bamero_create_test_users();
    bamero_configure_woocommerce();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'bamero_wc_setup_activate');
register_deactivation_hook(__FILE__, function () { flush_rewrite_rules(); });

function bamero_create_product_categories() {
    if (!bamero_wc_setup_schema_is_validated()) return;
    $categories = array('رنگ ساختمان' => 'رنگ‌های ساختمانی داخلی و نما', 'رنگ صنعتی' => 'رنگ‌های صنعتی و کف', 'ضد آب' => 'پوشش‌های ضد رطوبت و نانو', 'چسب و بتونه' => 'چسب‌ها و بتونه‌های آماده', 'ابزار نقاشی' => 'تینر، حلال و ابزار', 'رنگ خودرو' => 'رنگ و آستر خودرو');
    foreach ($categories as $name => $desc) {
        if (!term_exists($name, 'product_cat')) wp_insert_term($name, 'product_cat', array('description' => $desc));
    }
}

function bamero_create_product_tags() {
    if (!bamero_wc_setup_schema_is_validated()) return;
    foreach (array('۵ لیتر', '۱۰ لیتر', 'قابل شستشو', 'ضدآب', 'VOC پایین', 'پرفروش') as $tag) {
        if (!term_exists($tag, 'product_tag')) wp_insert_term($tag, 'product_tag');
    }
}

function bamero_create_catalog_products() {
    if (!bamero_wc_setup_schema_is_validated()) return;
    $products = array(
        array('name' => 'رنگ پلاستیک مات بامرو', 'sku' => 'RP-PL-001', 'price' => '245700', 'cat' => 'رنگ ساختمان', 'tags' => array('۵ لیتر', 'قابل شستشو'), 'stock' => 42, 'description' => 'رنگ پلاستیک مات مناسب فضاهای داخلی.', 'color_code' => '#F4F1EA'),
        array('name' => 'روغن الیف براق درجه یک', 'sku' => 'RP-OL-002', 'price' => '389400', 'cat' => 'رنگ ساختمان', 'tags' => array('پرفروش'), 'stock' => 28, 'description' => 'پوشش براق و بادوام برای سطوح چوبی.', 'color_code' => '#D9B36C'),
        array('name' => 'رنگ اکرولیک نیمه‌براق', 'sku' => 'RP-AC-003', 'price' => '420350', 'cat' => 'رنگ ساختمان', 'tags' => array('قابل شستشو'), 'stock' => 36, 'description' => 'رنگ اکرولیک کم‌بو با پوشش یکنواخت.', 'color_code' => '#DCE8EF'),
        array('name' => 'ضد آب نانو بام', 'sku' => 'RP-NA-004', 'price' => '675250', 'cat' => 'ضد آب', 'tags' => array('ضدآب'), 'stock' => 19, 'description' => 'محافظ نانو برای سطوح در معرض رطوبت.', 'color_code' => '#B7D5E5'),
        array('name' => 'رنگ روغنی سوپر لاکچری', 'sku' => 'RP-OG-005', 'price' => '512800', 'cat' => 'رنگ ساختمان', 'tags' => array('۱۰ لیتر'), 'stock' => 31, 'description' => 'رنگ روغنی با دوام و جلای بالا.', 'color_code' => '#E8D6C0'),
        array('name' => 'بتونه سنگی آماده', 'sku' => 'RP-BT-006', 'price' => '185650', 'cat' => 'چسب و بتونه', 'tags' => array('پرفروش'), 'stock' => 57, 'description' => 'بتونه آماده برای ترمیم و زیرسازی.', 'color_code' => '#D4D0C8'),
        array('name' => 'رنگ ترافیک زرد', 'sku' => 'RP-TR-007', 'price' => '298900', 'cat' => 'رنگ صنعتی', 'tags' => array('VOC پایین'), 'stock' => 24, 'description' => 'رنگ مقاوم برای خط‌کشی و سطوح صنعتی.', 'color_code' => '#E5B93F'),
        array('name' => 'چسب چوب صنعتی', 'sku' => 'RP-CH-008', 'price' => '156250', 'cat' => 'چسب و بتونه', 'tags' => array('پرفروش'), 'stock' => 63, 'description' => 'چسب چوب صنعتی با گیرش مطمئن.', 'color_code' => '#D6A85F'),
        array('name' => 'رنگ اپوکسی کف', 'sku' => 'RP-EP-009', 'price' => '890750', 'cat' => 'رنگ صنعتی', 'tags' => array('۱۰ لیتر'), 'stock' => 12, 'description' => 'پوشش اپوکسی مقاوم در برابر سایش.', 'color_code' => '#7B8790'),
        array('name' => 'حلال تینر فوری', 'sku' => 'RP-TH-010', 'price' => '198450', 'cat' => 'ابزار نقاشی', 'tags' => array('۵ لیتر'), 'stock' => 48, 'description' => 'حلال مناسب رنگ‌های فوری و صنعتی.', 'color_code' => '#E7E1D0'),
    );
    foreach ($products as $item) {
        $exists = get_posts(array('post_type' => 'product', 'meta_key' => '_sku', 'meta_value' => $item['sku'], 'posts_per_page' => 1, 'post_status' => 'any', 'fields' => 'ids'));
        if (!empty($exists)) continue;
        $product_id = wp_insert_post(array('post_title' => $item['name'], 'post_content' => $item['description'], 'post_status' => 'publish', 'post_type' => 'product'), true);
        if (is_wp_error($product_id) || !$product_id) continue;
        wp_set_object_terms($product_id, 'simple', 'product_type');
        $cat = get_term_by('name', $item['cat'], 'product_cat');
        if ($cat) wp_set_object_terms($product_id, (int) $cat->term_id, 'product_cat');
        $tag_ids = array();
        foreach ($item['tags'] as $tag_name) { $tag = get_term_by('name', $tag_name, 'product_tag'); if ($tag) $tag_ids[] = (int) $tag->term_id; }
        if ($tag_ids) wp_set_object_terms($product_id, $tag_ids, 'product_tag');
        update_post_meta($product_id, '_regular_price', $item['price']);
        update_post_meta($product_id, '_price', $item['price']);
        update_post_meta($product_id, '_sku', sanitize_text_field($item['sku']));
        update_post_meta($product_id, '_manage_stock', 'yes');
        update_post_meta($product_id, '_stock', (string) absint($item['stock']));
        update_post_meta($product_id, '_stock_status', 'instock');
        update_post_meta($product_id, '_visibility', 'visible');
        update_post_meta($product_id, '_product_color_code', sanitize_hex_color($item['color_code']));
        update_post_meta($product_id, '_product_brand', 'بامرو');
        $technical = array(
            'RP-PL-001' => array('ral' => 'RAL 9016', 'resin' => 'اکریلیک آب‌پایه', 'drying' => '۲ ساعت', 'coverage' => '۸', 'voc' => 'پایین', 'density' => '۱.۳ kg/L', 'surface' => 'گچ، سیمان'),
            'RP-OL-002' => array('ral' => 'RAL 1014', 'resin' => 'روغنی', 'drying' => '۶ ساعت', 'coverage' => '۱۰', 'voc' => 'متوسط', 'density' => '۱.۲ kg/L', 'surface' => 'چوب'),
            'RP-AC-003' => array('ral' => 'RAL 7035', 'resin' => 'اکریلیک', 'drying' => '۳ ساعت', 'coverage' => '۹', 'voc' => 'پایین', 'density' => '۱.۲ kg/L', 'surface' => 'دیوار داخلی'),
            'RP-NA-004' => array('ral' => 'RAL 5015', 'resin' => 'نانو سیلیکونی', 'drying' => '۴ ساعت', 'coverage' => '۶', 'voc' => 'پایین', 'density' => '۱.۱ kg/L', 'surface' => 'بتن و نما'),
            'RP-OG-005' => array('ral' => 'RAL 9003', 'resin' => 'آلکیدی', 'drying' => '۸ ساعت', 'coverage' => '۱۱', 'voc' => 'متوسط', 'density' => '۱.۲ kg/L', 'surface' => 'فلز و چوب'),
            'RP-BT-006' => array('ral' => 'RAL 7032', 'resin' => 'پلیمری', 'drying' => '۱ ساعت', 'coverage' => '۵', 'voc' => 'پایین', 'density' => '۱.۷ kg/L', 'surface' => 'سنگ و گچ'),
            'RP-TR-007' => array('ral' => 'RAL 1023', 'resin' => 'ترموپلاستیک', 'drying' => '۳۰ دقیقه', 'coverage' => '۵', 'voc' => 'متوسط', 'density' => '۱.۵ kg/L', 'surface' => 'آسفالت و بتن'),
            'RP-CH-008' => array('ral' => 'RAL 8001', 'resin' => 'PVA', 'drying' => '۱ ساعت', 'coverage' => '۱۲', 'voc' => 'پایین', 'density' => '۱.۰ kg/L', 'surface' => 'چوب'),
            'RP-EP-009' => array('ral' => 'RAL 7001', 'resin' => 'اپوکسی دو جزئی', 'drying' => '۱۲ ساعت', 'coverage' => '۴', 'voc' => 'پایین', 'density' => '۱.۴ kg/L', 'surface' => 'کف صنعتی'),
            'RP-TH-010' => array('ral' => 'NCS S 0500-N', 'resin' => 'حلال آلی', 'drying' => 'وابسته به رنگ', 'coverage' => '۰', 'voc' => 'بالا', 'density' => '۰.۸ kg/L', 'surface' => 'رقیق‌سازی'),
        );
        if (isset($technical[$item['sku']])) {
            $meta_keys = array('ral' => '_bamero_ral_code', 'resin' => '_bamero_resin_base', 'drying' => '_bamero_drying_time', 'coverage' => '_bamero_coverage', 'voc' => '_bamero_voc', 'density' => '_bamero_density', 'surface' => '_bamero_surface');
            foreach ($technical[$item['sku']] as $key => $value) update_post_meta($product_id, $meta_keys[$key], sanitize_text_field($value));
        }
        update_post_meta($product_id, '_bamero_seed_record', 'catalog-v1');
    }
}

function bamero_prune_excess_seed_data() {
    if (!bamero_wc_setup_schema_is_validated()) return;
    $allowed_skus = array('RP-PL-001', 'RP-OL-002', 'RP-AC-003', 'RP-NA-004', 'RP-OG-005', 'RP-BT-006', 'RP-TR-007', 'RP-CH-008', 'RP-EP-009', 'RP-TH-010');
    $seed_products = get_posts(array('post_type' => 'product', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_bamero_seed_record'));
    foreach ($seed_products as $product_id) {
        $sku = get_post_meta($product_id, '_sku', true);
        if (!in_array($sku, $allowed_skus, true)) wp_delete_post($product_id, true);
    }
    foreach (array('RP-FA-011', 'RP-PR-012') as $legacy_sku) {
        $legacy = get_posts(array('post_type' => 'product', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_sku', 'meta_value' => $legacy_sku));
        foreach ($legacy as $product_id) wp_delete_post($product_id, true);
    }
    $seed_users = get_users(array('meta_key' => '_bamero_seed_record', 'meta_value' => 'test-users-v1', 'fields' => 'ID'));
    foreach ($seed_users as $user_id) {
        $login = get_userdata($user_id)->user_login;
        if (!preg_match('/^bamero_test_(0[1-9]|10)$/', $login)) wp_delete_user($user_id);
    }
}

function bamero_create_test_users() {
    if (!bamero_wc_setup_schema_is_validated()) return;
    $segments = array('browser', 'browser', 'browser', 'browser', 'browser', 'browser', 'browser', 'buyer', 'buyer', 'abandoner');
    foreach ($segments as $index => $segment) {
        $number = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
        $username = 'bamero_test_' . $number;
        $email = $username . '@example.invalid';
        $user = get_user_by('login', $username);
        if (!$user) {
            $user_id = wp_insert_user(array('user_login' => $username, 'user_pass' => wp_generate_password(24, true, true), 'user_email' => $email, 'display_name' => 'کاربر تست بامرو ' . ($index + 1), 'role' => 'customer'));
            if (is_wp_error($user_id)) continue;
        } else {
            $user_id = $user->ID;
        }
        update_user_meta($user_id, 'billing_phone', '09' . str_pad((string) (100000000 + $index), 9, '0', STR_PAD_LEFT));
        update_user_meta($user_id, '_bamero_test_segment', $segment);
        update_user_meta($user_id, '_bamero_seed_record', 'test-users-v1');
    }
}

function bamero_configure_woocommerce() {
    if (!bamero_wc_setup_schema_is_validated()) return;
    update_option('woocommerce_currency', 'IRT');
    update_option('woocommerce_default_country', 'IR:TE');
    update_option('woocommerce_weight_unit', 'kg');
    update_option('woocommerce_dimension_unit', 'cm');
    update_option('woocommerce_enable_guest_checkout', 'yes');
    update_option('woocommerce_enable_coupons', 'yes');
}
add_action('admin_notices', function () {
    if (!get_transient('bamero_wc_setup_notice')) return;
    delete_transient('bamero_wc_setup_notice');
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('کاتالوگ بامرو و تنظیمات ووکامرس با موفقیت اعمال شد.', 'bamero-woocommerce-setup') . '</p></div>';
});
add_action('activated_plugin', function ($plugin) { if ($plugin === plugin_basename(__FILE__)) set_transient('bamero_wc_setup_notice', 1, 30); });
