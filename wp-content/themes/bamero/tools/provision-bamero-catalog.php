<?php
/**
 * Provision a real, repeatable Bamero catalog for a staging/production-like database.
 * Run once after WordPress and WooCommerce are active:
 *   wp eval-file tools/provision-bamero-catalog.php --user=1
 *
 * Data is idempotent by SKU and mobile number. Products are real WooCommerce products,
 * with price, stock, category, SKU, product metadata and local catalog artwork.
 */
if (!defined('ABSPATH')) { exit; }
if (!class_exists('WooCommerce')) { if (defined('WP_CLI') && WP_CLI) WP_CLI::error('WooCommerce must be active.'); exit; }

$catalog = array(
    array('sku'=>'BMR-ACR-01','name'=>'رنگ اکریلیک مات سفید ابری','category'=>'رنگ ساختمان','price'=>890000,'stock'=>42,'brand'=>'بامرو','ral'=>'RAL 9016','surface'=>'گچ، سیمان و بتن','coverage'=>'۱۰ تا ۱۲ مترمربع در لیتر','size'=>'۴ لیتر','accent'=>'#dfe8f1'),
    array('sku'=>'BMR-ACR-02','name'=>'رنگ اکریلیک نیمه‌مات استخوانی','category'=>'رنگ ساختمان','price'=>975000,'stock'=>35,'brand'=>'بامرو','ral'=>'RAL 1013','surface'=>'دیوار داخلی و سقف','coverage'=>'۱۰ تا ۱۲ مترمربع در لیتر','size'=>'۴ لیتر','accent'=>'#e9dcc8'),
    array('sku'=>'BMR-EXT-01','name'=>'رنگ نمای اکریلیک ضدآب طوسی','category'=>'نمای ساختمان','price'=>2380000,'stock'=>18,'brand'=>'بامرو پرو','ral'=>'RAL 7035','surface'=>'نمای سیمانی و بتن','coverage'=>'۸ تا ۱۰ مترمربع در لیتر','size'=>'۱۰ لیتر','accent'=>'#91a4b5'),
    array('sku'=>'BMR-EXT-02','name'=>'پوشش نانو عایق رطوبتی سفید','category'=>'عایق و ضدآب','price'=>3190000,'stock'=>14,'brand'=>'بامرو نانو','ral'=>'RAL 9003','surface'=>'بام، سرویس و دیوار نم‌دار','coverage'=>'۴ تا ۶ مترمربع در کیلوگرم','size'=>'۲۰ کیلوگرم','accent'=>'#eef4f7'),
    array('sku'=>'BMR-IND-01','name'=>'پوشش صنعتی اپوکسی کف طوسی','category'=>'رنگ صنعتی','price'=>4850000,'stock'=>9,'brand'=>'بامرو صنعتی','ral'=>'RAL 7040','surface'=>'کف کارخانه و پارکینگ','coverage'=>'۵ تا ۷ مترمربع در کیلوگرم','size'=>'۲۰ کیلوگرم','accent'=>'#738293'),
    array('sku'=>'BMR-IND-02','name'=>'آستر ضدزنگ زینک‌فسفات','category'=>'رنگ صنعتی','price'=>1650000,'stock'=>24,'brand'=>'بامرو صنعتی','ral'=>'RAL 3011','surface'=>'فلز و سازه فولادی','coverage'=>'۸ تا ۱۰ مترمربع در لیتر','size'=>'۴ لیتر','accent'=>'#8f5a42'),
    array('sku'=>'BMR-GLU-01','name'=>'چسب کاشی پودری انعطاف‌پذیر','category'=>'چسب و بتونه','price'=>420000,'stock'=>64,'brand'=>'بامرو سازه','ral'=>'—','surface'=>'کاشی، سرامیک و سنگ','coverage'=>'۳ تا ۴ مترمربع در کیسه','size'=>'۲۰ کیلوگرم','accent'=>'#d6c7aa'),
    array('sku'=>'BMR-GLU-02','name'=>'چسب همه‌کاره شفاف حرفه‌ای','category'=>'چسب و بتونه','price'=>185000,'stock'=>87,'brand'=>'بامرو سازه','ral'=>'—','surface'=>'چوب، فلز، شیشه و PVC','coverage'=>'مصرف موضعی','size'=>'۳۰۰ میلی‌لیتر','accent'=>'#c8d7e2'),
    array('sku'=>'BMR-PUT-01','name'=>'بتونه آماده نقاشی پایه‌آب','category'=>'چسب و بتونه','price'=>295000,'stock'=>51,'brand'=>'بامرو','ral'=>'سفید','surface'=>'گچ و دیوار داخلی','coverage'=>'۲ تا ۳ مترمربع در کیلوگرم','size'=>'۵ کیلوگرم','accent'=>'#f4efe6'),
    array('sku'=>'BMR-TOOL-01','name'=>'غلتک نقاشی پرز کوتاه ممتاز','category'=>'ابزار نقاشی','price'=>265000,'stock'=>73,'brand'=>'بامرو ابزار','ral'=>'—','surface'=>'رنگ‌های پایه‌آب و روغنی','coverage'=>'عرض ۲۵ سانتی‌متر','size'=>'یک عدد','accent'=>'#d8b45d'),
);
$users = array(
    array('mobile'=>'09121234501','first'=>'مهدی','last'=>'احمدی'), array('mobile'=>'09121234502','first'=>'سارا','last'=>'کریمی'),
    array('mobile'=>'09121234503','first'=>'علی','last'=>'رضایی'), array('mobile'=>'09121234504','first'=>'نگار','last'=>'مرادی'),
    array('mobile'=>'09121234505','first'=>'حسین','last'=>'اکبری'), array('mobile'=>'09121234506','first'=>'مریم','last'=>'نوری'),
    array('mobile'=>'09121234507','first'=>'امیر','last'=>'حسینی'), array('mobile'=>'09121234508','first'=>'الهام','last'=>'محمدی'),
    array('mobile'=>'09121234509','first'=>'رضا','last'=>'صادقی'), array('mobile'=>'09121234510','first'=>'سمیه','last'=>'موسوی'),
);
function bmr_catalog_mobile($mobile) { return '+98' . substr(preg_replace('/\D+/', '', $mobile), 1); }
function bmr_catalog_art($product_id, $name, $accent, $category) {
    $upload = wp_upload_dir(); $dir = trailingslashit($upload['basedir']) . 'bamero-catalog';
    if (!wp_mkdir_p($dir)) return 0;
    $file = $dir . '/product-' . $product_id . '.svg';
    $safe_name = esc_html($name); $safe_cat = esc_html($category);
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="900" viewBox="0 0 1200 900"><defs><linearGradient id="g" x1="0" x2="1"><stop stop-color="' . esc_attr($accent) . '"/><stop offset="1" stop-color="#ffffff"/></linearGradient></defs><rect width="1200" height="900" fill="url(#g)"/><circle cx="980" cy="150" r="260" fill="#ffffff" opacity=".28"/><rect x="360" y="180" width="480" height="560" rx="48" fill="#ffffff" opacity=".94"/><rect x="410" y="300" width="380" height="260" rx="24" fill="' . esc_attr($accent) . '"/><text x="600" y="630" text-anchor="middle" font-family="Arial,sans-serif" font-size="42" font-weight="700" fill="#152238">BAMERO</text><text x="600" y="690" text-anchor="middle" font-family="Arial,sans-serif" font-size="24" fill="#526277">' . $safe_cat . '</text><title>' . $safe_name . '</title></svg>';
    file_put_contents($file, $svg, LOCK_EX);
    $url = trailingslashit($upload['baseurl']) . 'bamero-catalog/product-' . $product_id . '.svg';
    $attachment_id = attachment_url_to_postid($url);
    if ($attachment_id) return $attachment_id;
    $attachment_id = wp_insert_attachment(array('post_mime_type'=>'image/svg+xml','post_title'=>$name,'post_status'=>'inherit'), $file, $product_id);
    if (!$attachment_id || is_wp_error($attachment_id)) return 0;
    return (int) $attachment_id;
}
foreach ($catalog as $item) {
    $product_id = wc_get_product_id_by_sku($item['sku']);
    $product = $product_id ? wc_get_product($product_id) : new WC_Product_Simple();
    $product->set_name($item['name']); $product->set_sku($item['sku']); $product->set_status('publish'); $product->set_catalog_visibility('visible');
    $product->set_regular_price((string) $item['price']); $product->set_price((string) $item['price']); $product->set_manage_stock(true); $product->set_stock_quantity($item['stock']); $product->set_stock_status('instock');
    $product->set_description('محصول حرفه‌ای ' . $item['name'] . ' برای اجرای تمیز و ماندگار. پیش از خرید، سطح زیرکار و میزان مصرف را با مشاور بامرو بررسی کنید.');
    $product->set_short_description($item['size'] . ' | پوشش‌دهی: ' . $item['coverage']);
    $product_id = $product->save();
    $term = term_exists($item['category'], 'product_cat'); if (!$term) $term = wp_insert_term($item['category'], 'product_cat');
    if (!is_wp_error($term)) wp_set_object_terms($product_id, array((int) $term['term_id']), 'product_cat');
    update_post_meta($product_id, '_product_brand', $item['brand']); update_post_meta($product_id, '_bamero_ral_code', $item['ral']); update_post_meta($product_id, '_bamero_surface', $item['surface']); update_post_meta($product_id, '_bamero_coverage', $item['coverage']); update_post_meta($product_id, '_bamero_package_size', $item['size']);
    $image_id = bmr_catalog_art($product_id, $item['name'], $item['accent'], $item['category']); if ($image_id) set_post_thumbnail($product_id, $image_id);
}
foreach ($users as $item) {
    $mobile = bmr_catalog_mobile($item['mobile']); $existing = get_users(array('meta_key'=>'bamero_mobile','meta_value'=>$mobile,'number'=>1,'fields'=>'all'));
    if ($existing) continue;
    $salt = function_exists('wp_salt') ? wp_salt('auth') : 'bamero'; $internal = hash('sha256', $mobile . $salt) . '@bamero.internal';
    $user_id = wp_insert_user(array('user_login'=>$mobile,'user_pass'=>wp_generate_password(40, true, true),'user_email'=>$internal,'first_name'=>$item['first'],'last_name'=>$item['last'],'display_name'=>$item['first'] . ' ' . $item['last'],'role'=>'customer'));
    if (!is_wp_error($user_id)) { update_user_meta($user_id, 'bamero_mobile', $mobile); update_user_meta($user_id, 'billing_phone', $mobile); update_user_meta($user_id, 'billing_first_name', $item['first']); update_user_meta($user_id, 'billing_last_name', $item['last']); update_user_meta($user_id, 'billing_email', ''); }
}
if (defined('WP_CLI') && WP_CLI) WP_CLI::success('Bamero catalog provisioned: 10 real WooCommerce products and 10 mobile-first customer accounts.');
