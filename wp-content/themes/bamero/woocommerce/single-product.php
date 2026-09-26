<?php
/**
 * Single product template.
 *
 * @package Bamero
 */

defined('ABSPATH') || exit;

get_header('shop');

while (have_posts()) {
    the_post();

    $product = $GLOBALS['product'] ?? null;
    if (!$product instanceof WC_Product) {
        $product = wc_get_product(get_the_ID());
        if ($product instanceof WC_Product) {
            $GLOBALS['product'] = $product;
        }
    }

    if (!$product instanceof WC_Product) {
        echo '<main id="main-content" class="container"><p>' . esc_html__('محصول یافت نشد.', 'bamero') . '</p></main>';
        get_footer('shop');
        return;
    }

    wc_get_template_part('content', 'single-product');
}

get_footer('shop');
