<?php
/**
 * Bamero Theme — SEO / structured data
 *
 * Schema.org entity graph (Organization, LocalBusiness, WebSite,
 * Product, BreadcrumbList), meta description / canonical / Open Graph /
 * Twitter Card fallbacks (only when no SEO plugin is active), hreflang
 * self-reference, meta robots for thin pages, owner-configurable GEO
 * settings and the dynamic robots.txt.
 *
 * @package Bamero
 */

defined('ABSPATH') || exit;

// =============================================================================
// HELPERS
// =============================================================================

/**
 * True when a dedicated SEO plugin owns titles, meta and structured data.
 * Rank Math, Yoast, AIOSEO and SEOPress all print their own tags; the theme
 * then stays silent so nothing is duplicated.
 */
function bamero_seo_plugin_active() {
    return defined('RANK_MATH_VERSION')
        || defined('WPSEO_VERSION')
        || defined('AIOSEO_VERSION')
        || defined('SEOPRESS_VERSION');
}

/** Sanitize a latitude/longitude Customizer value (decimal degrees). */
function bamero_sanitize_coordinate($value, $setting = null) {
    $value = trim(bamero_ascii_digits((string) $value));
    if ($value === '' || !is_numeric($value)) {
        return '';
    }
    $limit = (is_object($setting) && isset($setting->id) && $setting->id === 'bamero_geo_longitude') ? 180 : 90;
    $num   = (float) $value;
    if ($num < -$limit || $num > $limit) {
        return '';
    }
    return (string) round($num, 6);
}

/** Owner-configurable GEO settings (Appearance > Customize > Bamero contact). */
function bamero_seo_customize_register($wp_customize) {
    $fields = array(
        'bamero_geo_latitude'  => array('label' => __('عرض جغرافیایی فروشگاه (Latitude)', 'bamero'), 'default' => '32.666756', 'sanitize' => 'bamero_sanitize_coordinate', 'type' => 'text'),
        'bamero_geo_longitude' => array('label' => __('طول جغرافیایی فروشگاه (Longitude)', 'bamero'), 'default' => '51.644733', 'sanitize' => 'bamero_sanitize_coordinate', 'type' => 'text'),
        'bamero_maps_url'      => array('label' => __('پیوند نقشهٔ فروشگاه (Google Maps / نشان / بلد)', 'bamero'), 'default' => '', 'sanitize' => 'esc_url_raw', 'type' => 'url'),
    );
    foreach ($fields as $id => $field) {
        $wp_customize->add_setting($id, array(
            'default'           => $field['default'],
            'sanitize_callback' => $field['sanitize'],
            'transport'         => 'refresh',
        ));
        $wp_customize->add_control($id, array(
            'label'   => $field['label'],
            'section' => 'bamero_contact',
            'type'    => $field['type'],
        ));
    }
}
add_action('customize_register', 'bamero_seo_customize_register', 20);

/** Canonical URL of the current view ('' when the view has none). */
function bamero_seo_current_url() {
    if (is_404() || is_search()) {
        return '';
    }
    $paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
    if (is_singular()) {
        $url = wp_get_canonical_url();
        return $url ? $url : (string) get_permalink();
    }
    if ($paged > 1) {
        return (string) get_pagenum_link($paged, false);
    }
    if (is_home() && !is_front_page() && get_option('page_for_posts')) {
        return (string) get_permalink((int) get_option('page_for_posts'));
    }
    if (is_front_page() || is_home()) {
        return home_url('/');
    }
    if (function_exists('is_shop') && is_shop()) {
        return (string) get_permalink(wc_get_page_id('shop'));
    }
    if (is_category() || is_tag() || is_tax()) {
        $link = get_term_link(get_queried_object());
        return is_wp_error($link) ? '' : (string) $link;
    }
    if (is_post_type_archive()) {
        $link = get_post_type_archive_link((string) get_query_var('post_type'));
        return $link ? (string) $link : '';
    }
    return '';
}

/** Plain-text, whitespace-normalised, length-capped description. */
function bamero_seo_clean_text($text, $max = 160) {
    $text = wp_strip_all_tags(strip_shortcodes((string) $text), true);
    $text = trim(preg_replace('/\s+/u', ' ', $text));
    if ($text !== '' && function_exists('mb_strlen') && mb_strlen($text) > $max) {
        $text = rtrim(mb_substr($text, 0, $max - 1)) . '…';
    }
    return $text;
}

/** Meta description for the current view. */
function bamero_seo_description() {
    $fallback = get_bloginfo('description');
    if (!$fallback) {
        $fallback = 'فروشگاه اینترنتی بامرو؛ خرید رنگ ساختمانی و صنعتی، پوشش‌های ضدآب و انواع چسب با مشاورهٔ فنی در اصفهان.';
    }
    $text = '';
    if (function_exists('is_product') && is_product()) {
        $product = wc_get_product(get_queried_object_id());
        if ($product instanceof WC_Product) {
            $text = $product->get_short_description() ?: $product->get_description();
        }
    } elseif (is_singular()) {
        $post = get_queried_object();
        if ($post instanceof WP_Post && !post_password_required($post)) {
            $text = $post->post_excerpt !== '' ? $post->post_excerpt : $post->post_content;
        }
    } elseif (function_exists('is_shop') && is_shop()) {
        $text = get_post_field('post_excerpt', wc_get_page_id('shop'));
    } elseif (is_category() || is_tag() || is_tax()) {
        $term = get_queried_object();
        $text = term_description();
        if (trim(wp_strip_all_tags((string) $text)) === '' && $term instanceof WP_Term) {
            /* translators: %s: category name */
            $text = sprintf('خرید %s از فروشگاه رنگ و چسب بامرو با مشخصات فنی کامل و مشاورهٔ تخصصی.', $term->name);
        }
    }
    $text = bamero_seo_clean_text($text);
    return $text !== '' ? $text : bamero_seo_clean_text($fallback);
}

/** Absolute URL + alt of the share image (product/featured image, then logo). */
function bamero_seo_image() {
    $image_id = 0;
    if (function_exists('is_product') && is_product()) {
        $product = wc_get_product(get_queried_object_id());
        if ($product instanceof WC_Product) {
            $image_id = (int) $product->get_image_id();
        }
    } elseif (is_singular() && has_post_thumbnail(get_queried_object_id())) {
        $image_id = (int) get_post_thumbnail_id(get_queried_object_id());
    }
    if (!$image_id) {
        $image_id = (int) get_theme_mod('custom_logo', 0);
    }
    if ($image_id) {
        $src = wp_get_attachment_image_src($image_id, 'full');
        if ($src) {
            return array('url' => $src[0], 'width' => (int) $src[1], 'height' => (int) $src[2]);
        }
    }
    $icon = function_exists('get_site_icon_url') ? get_site_icon_url(512) : '';
    if ($icon) {
        return array('url' => $icon, 'width' => 512, 'height' => 512);
    }
    return array('url' => BAMERO_THEME_DIR . '/images/logo.svg', 'width' => 0, 'height' => 0);
}

/**
 * Full product_cat trail (root → leaf) for a product or category term.
 *
 * @param WP_Term|null $term Leaf term.
 * @return WP_Term[]
 */
function bamero_seo_term_trail($term) {
    if (!$term instanceof WP_Term) {
        return array();
    }
    $trail = array();
    foreach (array_reverse(get_ancestors($term->term_id, $term->taxonomy, 'taxonomy')) as $ancestor_id) {
        $ancestor = get_term($ancestor_id, $term->taxonomy);
        if ($ancestor instanceof WP_Term) {
            $trail[] = $ancestor;
        }
    }
    $trail[] = $term;
    return $trail;
}

/** Deepest product_cat of a product (same ordering WooCommerce breadcrumbs use). */
function bamero_seo_product_main_term($product_id) {
    if (!function_exists('wc_get_product_terms')) {
        return null;
    }
    $terms = wc_get_product_terms($product_id, 'product_cat', array('orderby' => 'parent', 'order' => 'DESC'));
    if (empty($terms) || !$terms[0] instanceof WP_Term) {
        return null;
    }
    return apply_filters('woocommerce_breadcrumb_main_term', $terms[0], $terms);
}

/**
 * Meta description, canonical, Open Graph and Twitter Card tags.
 * Skipped entirely when an SEO plugin is active (it prints its own).
 * WordPress core already prints rel=canonical on singular views, so the theme
 * only adds it for archives (shop, product categories, paginated lists).
 */
function bamero_output_meta_tags() {
    if (is_admin() || bamero_seo_plugin_active()) {
        return;
    }
    $url         = bamero_seo_current_url();
    $description = bamero_seo_description();
    $image       = bamero_seo_image();
    $title       = wp_get_document_title();
    $is_product  = function_exists('is_product') && is_product();

    if ($is_product) {
        $type = 'product';
    } elseif (is_singular() && !is_front_page() && !is_page()) {
        $type = 'article';
    } else {
        $type = 'website';
    }

    $tags = array();
    $tags[] = '<meta name="description" content="' . esc_attr($description) . '" />';
    if ($url !== '' && !is_singular()) {
        $tags[] = '<link rel="canonical" href="' . esc_url($url) . '" />';
    }
    $og = array(
        'og:locale'      => 'fa_IR',
        'og:site_name'   => get_bloginfo('name') ?: 'بامرو',
        'og:type'        => $type,
        'og:title'       => $title,
        'og:description' => $description,
        'og:url'         => $url,
        'og:image'       => $image['url'],
    );
    foreach ($og as $property => $content) {
        if ($content === '') {
            continue;
        }
        $value = ($property === 'og:url' || $property === 'og:image') ? esc_url($content) : esc_attr($content);
        $tags[] = '<meta property="' . esc_attr($property) . '" content="' . $value . '" />';
    }
    if ($image['width'] && $image['height']) {
        $tags[] = '<meta property="og:image:width" content="' . esc_attr((string) $image['width']) . '" />';
        $tags[] = '<meta property="og:image:height" content="' . esc_attr((string) $image['height']) . '" />';
    }
    $tags[] = '<meta property="og:image:alt" content="' . esc_attr($title) . '" />';
    if ($is_product) {
        $product = wc_get_product(get_queried_object_id());
        if ($product instanceof WC_Product && $product->get_price() !== '') {
            $tags[] = '<meta property="product:price:amount" content="' . esc_attr(wc_format_decimal($product->get_price(), 2)) . '" />';
            $tags[] = '<meta property="product:price:currency" content="' . esc_attr(get_woocommerce_currency()) . '" />';
            $tags[] = '<meta property="product:availability" content="' . ($product->is_in_stock() ? 'instock' : 'oos') . '" />';
        }
    }
    $tags[] = '<meta name="twitter:card" content="summary_large_image" />';
    $tags[] = '<meta name="twitter:title" content="' . esc_attr($title) . '" />';
    $tags[] = '<meta name="twitter:description" content="' . esc_attr($description) . '" />';
    $tags[] = '<meta name="twitter:image" content="' . esc_url($image['url']) . '" />';

    echo implode("\n", $tags) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every value escaped above.
}
add_action('wp_head', 'bamero_output_meta_tags', 2);

/** hreflang self-reference (single-language fa-IR site). */
function bamero_output_hreflang() {
    if (is_admin()) {
        return;
    }
    $url = bamero_seo_current_url();
    if ($url === '') {
        return;
    }
    echo '<link rel="alternate" hreflang="fa-IR" href="' . esc_url($url) . '" />' . "\n";
    echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($url) . '" />' . "\n";
}
add_action('wp_head', 'bamero_output_hreflang', 3);

/**
 * The theme's @graph already carries Product / BreadcrumbList / WebSite.
 * Stop WooCommerce from printing a second, un-nonced copy of the same
 * entities in the footer. When an SEO plugin owns schema, WooCommerce's
 * markup is left untouched for it to manage.
 */
function bamero_disable_wc_duplicate_schema($markup) {
    return bamero_seo_plugin_active() ? $markup : array();
}
add_filter('woocommerce_structured_data_product', 'bamero_disable_wc_duplicate_schema', 99);
add_filter('woocommerce_structured_data_breadcrumblist', 'bamero_disable_wc_duplicate_schema', 99);
add_filter('woocommerce_structured_data_website', 'bamero_disable_wc_duplicate_schema', 99);

// =============================================================================
// SEO + GEO (2026 production) – entity, local, AI-overview friendly JSON-LD
// =============================================================================

/**
 * Organization + LocalBusiness + WebSite entity graph (single @graph).
 * Optimized for Google AI Overviews / entity resolution (H2 2025–2026).
 */
function bamero_output_entity_graph() {
    if (is_admin()) {
        return;
    }
    // SEO-03: Rank Math owns structured data when active
    if (defined('RANK_MATH_VERSION')) {
        return;
    }
    // Yoast / AIOSEO / SEOPress also print a full graph; avoid duplicates.
    if (bamero_seo_plugin_active()) {
        return;
    }

    $site_name = get_bloginfo('name') ?: 'بامرو';
    $site_url  = home_url('/');
    $logo      = BAMERO_THEME_DIR . '/images/logo.svg';
    $phone     = bamero_phone_e164();
    $address   = bamero_address_display();

    $graph = array(
        '@context' => 'https://schema.org',
        '@graph'   => array(
            array(
                '@type' => 'Organization',
                '@id'   => $site_url . '#organization',
                'name'  => $site_name,
                'url'   => $site_url,
                'logo'  => array(
                    '@type'  => 'ImageObject',
                    'url'    => $logo,
                    'width'  => 512,
                    'height' => 512,
                ),
                'sameAs' => array_values(array_filter(array(
                    esc_url_raw((string) get_theme_mod('bamero_instagram_url', '')),
                    esc_url_raw((string) get_theme_mod('bamero_telegram_url', '')),
                ))),
                'contactPoint' => array(
                    '@type'       => 'ContactPoint',
                    'telephone'   => $phone,
                    'contactType' => 'customer service',
                    'areaServed'  => 'IR',
                    'availableLanguage' => array('fa', 'Persian'),
                ),
            ),
            array(
                '@type' => 'LocalBusiness',
                '@id'   => $site_url . '#localbusiness',
                'name'  => $site_name,
                'image' => $logo,
                'url'   => $site_url,
                'telephone' => $phone,
                'priceRange' => '$$',
                'address' => array(
                    '@type'           => 'PostalAddress',
                    'streetAddress'   => $address,
                    'addressLocality' => 'اصفهان',
                    'addressRegion'   => 'اصفهان',
                    'addressCountry'  => 'IR',
                ),
                'areaServed' => array(
                    array('@type' => 'City', 'name' => 'اصفهان'),
                    array('@type' => 'Country', 'name' => 'ایران'),
                ),
                'openingHoursSpecification' => array(
                    array(
                        '@type'     => 'OpeningHoursSpecification',
                        'dayOfWeek' => array('Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday'),
                        'opens'     => '08:00',
                        'closes'    => '17:00',
                    ),
                    array(
                        '@type'     => 'OpeningHoursSpecification',
                        'dayOfWeek' => 'Thursday',
                        'opens'     => '08:00',
                        'closes'    => '14:00',
                    ),
                ),
                'parentOrganization' => array('@id' => $site_url . '#organization'),
            ),
            array(
                '@type' => 'WebSite',
                '@id'   => $site_url . '#website',
                'url'   => $site_url,
                'name'  => $site_name,
                'inLanguage' => 'fa-IR',
                'publisher'  => array('@id' => $site_url . '#organization'),
                'potentialAction' => array(
                    '@type'       => 'SearchAction',
                    'target'      => array(
                        '@type'       => 'EntryPoint',
                        'urlTemplate' => $site_url . '?s={search_term_string}&post_type=product',
                    ),
                    'query-input' => 'required name=search_term_string',
                ),
            ),
        ),
    );

    // GEO: owner-configurable store coordinates (Customizer → Bamero contact).
    // Defaults are the previous approximate central-Isfahan values.
    $latitude  = bamero_sanitize_coordinate(get_theme_mod('bamero_geo_latitude', '32.666756'));
    $longitude = bamero_sanitize_coordinate(get_theme_mod('bamero_geo_longitude', '51.644733'), (object) array('id' => 'bamero_geo_longitude'));
    if ($latitude !== '' && $longitude !== '') {
        $graph['@graph'][1]['geo'] = array(
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float) $latitude,
            'longitude' => (float) $longitude,
        );
    }
    $maps_url = esc_url_raw((string) get_theme_mod('bamero_maps_url', ''));
    if ($maps_url !== '') {
        $graph['@graph'][1]['hasMap'] = $maps_url;
    }
    if ($phone === '') {
        unset($graph['@graph'][0]['contactPoint'], $graph['@graph'][1]['telephone']);
    }

    // Product entity on single product (E-E-A-T + rich results).
    // Uses the queried object: the global $product is not populated yet in wp_head.
    $main_term = null;
    if (function_exists('is_product') && is_product()) {
        $product = wc_get_product(get_queried_object_id());
        if ($product instanceof WC_Product) {
            $permalink = get_permalink($product->get_id());
            $main_term = bamero_seo_product_main_term($product->get_id());

            $images = array();
            foreach (array_merge(array($product->get_image_id()), $product->get_gallery_image_ids()) as $image_id) {
                $image_url = $image_id ? wp_get_attachment_url((int) $image_id) : '';
                if ($image_url) {
                    $images[] = $image_url;
                }
            }

            $brand_name = trim((string) $product->get_meta('_product_brand'));
            $entity = array(
                '@type'       => 'Product',
                '@id'         => $permalink . '#product',
                'name'        => $product->get_name(),
                'url'         => $permalink,
                'description' => bamero_seo_clean_text($product->get_short_description() ?: $product->get_description(), 5000),
                'sku'         => $product->get_sku(),
                'image'       => array_values(array_unique($images)),
                'category'    => $main_term ? implode(' > ', wp_list_pluck(bamero_seo_term_trail($main_term), 'name')) : '',
                'brand'       => array(
                    '@type' => 'Brand',
                    'name'  => $brand_name !== '' ? $brand_name : 'بامرو',
                ),
            );

            // Offer: only when a real price exists (Google rejects empty prices).
            if ($product->get_price() !== '') {
                if ($product->is_on_backorder()) {
                    $availability = 'https://schema.org/BackOrder';
                } elseif ($product->is_in_stock()) {
                    $availability = 'https://schema.org/InStock';
                } else {
                    $availability = 'https://schema.org/OutOfStock';
                }
                $sale_end = $product->is_on_sale() ? $product->get_date_on_sale_to() : null;
                $entity['offers'] = array(
                    '@type'           => 'Offer',
                    'url'             => $permalink,
                    'priceCurrency'   => get_woocommerce_currency(),
                    'price'           => wc_format_decimal($product->get_price(), 2),
                    // Same convention as WooCommerce core: sale end date, else end of next year.
                    'priceValidUntil' => $sale_end ? $sale_end->date('Y-m-d') : gmdate('Y-12-31', time() + YEAR_IN_SECONDS),
                    'itemCondition'   => 'https://schema.org/NewCondition',
                    'availability'    => $availability,
                    'seller'          => array('@id' => $site_url . '#organization'),
                );
            }

            // Ratings/reviews: only real, approved customer reviews — never synthesised.
            if (wc_review_ratings_enabled() && $product->get_review_count() > 0 && (float) $product->get_average_rating() > 0) {
                $entity['aggregateRating'] = array(
                    '@type'       => 'AggregateRating',
                    'ratingValue' => wc_format_decimal($product->get_average_rating(), 1),
                    'reviewCount' => (int) $product->get_review_count(),
                    'bestRating'  => 5,
                    'worstRating' => 1,
                );
                $reviews = get_comments(array(
                    'post_id' => $product->get_id(),
                    'status'  => 'approve',
                    'type'    => 'review',
                    'number'  => 5,
                ));
                foreach ($reviews as $review) {
                    $rating = (int) get_comment_meta($review->comment_ID, 'rating', true);
                    if ($rating < 1) {
                        continue;
                    }
                    $entity['review'][] = array(
                        '@type'         => 'Review',
                        'author'        => array('@type' => 'Person', 'name' => get_comment_author($review)),
                        'datePublished' => mysql2date('Y-m-d', $review->comment_date_gmt),
                        'reviewBody'    => bamero_seo_clean_text($review->comment_content, 1000),
                        'reviewRating'  => array('@type' => 'Rating', 'ratingValue' => $rating, 'bestRating' => 5, 'worstRating' => 1),
                    );
                }
            }

            $graph['@graph'][] = array_filter($entity, static function ($value) {
                return $value !== '' && $value !== array();
            });
        }
    }

    // BreadcrumbList with the full product_cat trail (helps AI Overviews + sitelinks).
    if (!is_front_page() && !is_search() && !is_404()) {
        $trail   = array(array('name' => 'خانه', 'item' => $site_url));
        $shop_id = function_exists('wc_get_page_id') ? (int) wc_get_page_id('shop') : 0;
        $is_wc   = (function_exists('is_shop') && is_shop())
            || (function_exists('is_product') && is_product())
            || (function_exists('is_product_taxonomy') && is_product_taxonomy());
        if ($is_wc && $shop_id > 0) {
            $trail[] = array('name' => get_the_title($shop_id) ?: 'فروشگاه', 'item' => get_permalink($shop_id));
        }
        $terms = array();
        if (function_exists('is_product') && is_product()) {
            $terms = bamero_seo_term_trail($main_term);
        } elseif (function_exists('is_product_taxonomy') && is_product_taxonomy()) {
            $queried = get_queried_object();
            $terms   = ($queried instanceof WP_Term && is_taxonomy_hierarchical($queried->taxonomy))
                ? bamero_seo_term_trail($queried)
                : ($queried instanceof WP_Term ? array($queried) : array());
        }
        foreach ($terms as $term) {
            $link = get_term_link($term);
            if (!is_wp_error($link)) {
                $trail[] = array('name' => $term->name, 'item' => $link);
            }
        }
        if (is_singular() && !(function_exists('is_shop') && is_shop())) {
            $trail[] = array('name' => get_the_title(get_queried_object_id()), 'item' => get_permalink(get_queried_object_id()));
        }
        if (count($trail) > 1) {
            $crumbs = array();
            foreach ($trail as $index => $crumb) {
                $crumbs[] = array(
                    '@type'    => 'ListItem',
                    'position' => $index + 1,
                    'name'     => wp_strip_all_tags((string) $crumb['name']),
                    'item'     => $crumb['item'],
                );
            }
            $graph['@graph'][] = array(
                '@type'           => 'BreadcrumbList',
                'itemListElement' => $crumbs,
            );
        }
    }

    echo '<script type="application/ld+json" nonce="' . esc_attr( function_exists( 'bamero_csp_nonce' ) ? bamero_csp_nonce() : '' ) . '">' . wp_json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . '</script>' . "\n";
}
add_action('wp_head', 'bamero_output_entity_graph', 5);

/**
 * Meta robots for thin pages via the core wp_robots API (one merged tag,
 * keeps core's max-image-preview:large). Shop, product and category pages
 * are never noindexed.
 */
function bamero_meta_robots($robots) {
    if (is_search() || is_404()) {
        $robots['noindex'] = true;
        $robots['follow']  = true;
    }
    return $robots;
}
add_filter('wp_robots', 'bamero_meta_robots');

// =============================================================================
// DYNAMIC robots.txt (domain-agnostic; no hard-coded production host)
// =============================================================================

/**
 * Serve robots.txt dynamically so the Sitemap URL always matches the real
 * domain the owner configures under Settings > General. A static file would
 * hard-code a host that does not exist yet.
 */
function bamero_robots_txt($output, $public) {
    if (!$public) {
        return $output;
    }
    if (stripos($output, 'Sitemap:') === false) {
        $output = rtrim($output) . "\n\nSitemap: " . home_url('/wp-sitemap.xml') . "\n";
    }
    return $output;
}
add_filter('robots_txt', 'bamero_robots_txt', 99, 2);
