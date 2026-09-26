<?php
/**
 * Homepage Template for Bamero Theme
 *
 * @package Bamero
 */

get_header();

// PHP-03: single product query + transient cache (1h)
$bamero_home_products = get_transient('bamero_home_products_v1');
if (false === $bamero_home_products || !is_array($bamero_home_products)) {
    $q = new WP_Query(array(
        'post_type'           => 'product',
        'posts_per_page'      => 12,
        'post_status'         => 'publish',
        'orderby'             => 'date',
        'order'               => 'DESC',
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
    ));
    $bamero_home_products = $q->posts;
    set_transient('bamero_home_products_v1', $bamero_home_products, 3600);
    wp_reset_postdata();
}

$product_categories = get_terms(array(
    'taxonomy'   => 'product_cat',
    'hide_empty' => true,
    'number'     => 4,
));
if (is_wp_error($product_categories)) {
    $product_categories = array();
}

// Schema Markup for homepage
$schema_markup = array(
    "@context" => "https://schema.org",
    "@type" => "WebSite",
    "name" => get_bloginfo('name'),
    "url" => home_url('/'),
    "description" => get_bloginfo('description'),
    "potentialAction" => array(
        "@type" => "SearchAction",
        "target" => home_url('/?s={search_term_string}&post_type=product'),
        "query-input" => "required name=search_term_string"
    )
);

?>

<!-- Schema Markup -->
<script type="application/ld+json" nonce="<?php echo esc_attr(function_exists('bamero_csp_nonce') ? bamero_csp_nonce() : ''); ?>">
<?php echo json_encode($schema_markup, JSON_UNESCAPED_UNICODE); ?>
</script>

<main id="main-content" class="rtl">
    <!-- Hero Banner -->
    <section class="hero-banner" aria-label="بنر اصلی">
        <div class="hero-content container">
            <h1><?php echo esc_html__('رنگ‌های باکیفیت برای ساختمان شما', 'bamero'); ?></h1>
            <p><?php echo esc_html__('مرجع رنگ و محصولات ساختمانی در ایران', 'bamero'); ?></p>
            <div class="hero-buttons">
                <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="button cta-button">
                    <i class="fas fa-shopping-bag"></i> <?php echo esc_html__('مشاهده محصولات', 'bamero'); ?>
                </a>
                <a href="<?php echo esc_url(get_permalink(get_page_by_path('consultation'))); ?>" class="button button-secondary">
                    <i class="fas fa-comments"></i> <?php echo esc_html__('دریافت مشاوره رایگان', 'bamero'); ?>
                </a>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="featured-products container" aria-label="محصولات پرفروش">
        <div class="section-header">
            <h2><?php echo esc_html__('محصولات پرفروش', 'bamero'); ?></h2>
            <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="view-all-link">
                <?php echo esc_html__('مشاهده همه', 'bamero'); ?> <i class="fas fa-chevron-left"></i>
            </a>
        </div>
        <div class="products-grid">
            <?php
            $slice = array_slice($bamero_home_products, 0, 8);
            if (!empty($slice)) :
                foreach ($slice as $post) :
                    setup_postdata($GLOBALS['post'] = $post);
                    $product = function_exists('wc_get_product') ? wc_get_product($post->ID) : null;
                    if (!$product) { continue; }
                    ?>
                    <div class="product-card">
                        <a href="<?php the_permalink(); ?>">
                            <?php
                            if (has_post_thumbnail()) {
                                the_post_thumbnail('bamero-thumbnail');
                            } else {
                                echo '<img src="' . esc_url(BAMERO_THEME_DIR . '/images/placeholder.svg') . '" alt="' . esc_attr(get_the_title()) . '" width="300" height="300" />';
                            }
                            ?>
                            <h3><?php the_title(); ?></h3>
                            <span class="price"><?php echo wp_kses_post($product->get_price_html()); ?></span>
                        </a>
                        <div class="product-actions">
                            <?php woocommerce_template_loop_add_to_cart(); ?>
                        </div>
                    </div>
                    <?php
                endforeach;
                wp_reset_postdata();
            else :
                echo '<p class="text-center">' . esc_html__('هیچ محصولی یافت نشد.', 'bamero') . '</p>';
            endif;
            ?>
        </div>
    </section>

    <!-- Product Categories -->
    <section class="product-categories container" aria-label="دسته‌بندی محصولات">
        <div class="section-header">
            <h2><?php echo esc_html__('دسته‌بندی محصولات', 'bamero'); ?></h2>
        </div>
        <div class="categories-grid">
            <?php
            foreach ($product_categories as $category) :
                $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
                $image = wp_get_attachment_url($thumbnail_id);
                $placeholder = BAMERO_THEME_DIR . '/images/placeholder.svg';
                
                if (!$image) {
                    $image = $placeholder;
                }
                ?>
                <div class="category-card">
                    <a href="<?php echo esc_url(get_term_link($category)); ?>">
                        <div class="category-image">
                            <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($category->name); ?>" loading="lazy" />
                        </div>
                        <h3><?php echo esc_html($category->name); ?></h3>
                        <span class="category-count">
                            <?php echo esc_html($category->count); ?> <?php echo esc_html__('محصول', 'bamero'); ?>
                        </span>
                    </a>
                </div>
                <?php
            endforeach;
            ?>
        </div>
    </section>

    <!-- Latest Products -->
    <section class="latest-products container" aria-label="جدیدترین محصولات">
        <div class="section-header">
            <h2><?php echo esc_html__('جدیدترین محصولات', 'bamero'); ?></h2>
            <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>?orderby=date" class="view-all-link">
                <?php echo esc_html__('مشاهده همه', 'bamero'); ?> <i class="fas fa-chevron-left"></i>
            </a>
        </div>
        <div class="products-grid">
            <?php
            $slice = array_slice($bamero_home_products, 0, 4);
            if (!empty($slice)) :
                foreach ($slice as $post) :
                    setup_postdata($GLOBALS['post'] = $post);
                    $product = function_exists('wc_get_product') ? wc_get_product($post->ID) : null;
                    if (!$product) { continue; }
                    ?>
                    <div class="product-card">
                        <a href="<?php the_permalink(); ?>">
                            <?php
                            if (has_post_thumbnail()) {
                                the_post_thumbnail('bamero-thumbnail');
                            } else {
                                echo '<img src="' . esc_url(BAMERO_THEME_DIR . '/images/placeholder.svg') . '" alt="' . esc_attr(get_the_title()) . '" width="300" height="300" />';
                            }
                            ?>
                            <h3><?php the_title(); ?></h3>
                            <span class="price"><?php echo wp_kses_post($product->get_price_html()); ?></span>
                        </a>
                        <div class="product-actions">
                            <?php woocommerce_template_loop_add_to_cart(); ?>
                        </div>
                    </div>
                    <?php
                endforeach;
                wp_reset_postdata();
            else :
                echo '<p class="text-center">' . esc_html__('هیچ محصول جدیدی یافت نشد.', 'bamero') . '</p>';
            endif;
            ?>
        </div>
    </section>

    <!-- On Sale Products -->
    <?php
    $on_sale_list = array();
    foreach ($bamero_home_products as $post) {
        $pr = function_exists('wc_get_product') ? wc_get_product($post->ID) : null;
        if ($pr && $pr->is_on_sale()) {
            $on_sale_list[] = $post;
        }
    }
    if (!empty($on_sale_list)) :
    ?>

    <section class="on-sale-products container" aria-label="محصولات تخفیف‌دار">
        <div class="section-header">
            <h2><?php echo esc_html__('محصولات تخفیف‌دار', 'bamero'); ?></h2>
            <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>?orderby=price-desc" class="view-all-link">
                <?php echo esc_html__('مشاهده همه', 'bamero'); ?> <i class="fas fa-chevron-left"></i>
            </a>
        </div>
        <div class="products-grid">
            <?php
            foreach (array_slice($on_sale_list, 0, 4) as $post) : setup_postdata($GLOBALS['post'] = $post); $product = wc_get_product($post->ID); if (!$product) { continue; }
                global $product;
                ?>
                <div class="product-card">
                    <a href="<?php the_permalink(); ?>">
                        <?php
                        if (has_post_thumbnail()) {
                            the_post_thumbnail('bamero-thumbnail');
                        } else {
                            echo '<img src="' . esc_url(BAMERO_THEME_DIR . '/images/placeholder.svg') . '" alt="' . esc_attr(get_the_title()) . '" />';
                        }
                        ?>
                        <?php if ($product->is_on_sale()) : ?>
                            <span class="onsale"><?php echo esc_html__('تخفیف', 'bamero'); ?></span>
                        <?php endif; ?>
                        <h3><?php the_title(); ?></h3>
                        <span class="price"><?php echo wc_price($product->get_price()); ?></span>
                    </a>
                    <div class="product-actions">
                        <?php woocommerce_template_loop_add_to_cart(); ?>
                    </div>
                </div>
                <?php
            endforeach;
            wp_reset_postdata();
            ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- About Section -->
    <section class="about-section container" aria-label="درباره بامرو">
        <div class="about-content">
            <h2><?php echo esc_html__('درباره بامرو', 'bamero'); ?></h2>
            <p><?php echo esc_html__('بامرو با سال‌ها تجربه در زمینه فروش رنگ و محصولات ساختمانی، آماده ارائه بهترین خدمات به شما مشتریان عزیز است. ما با هدف ارائه محصولات با کیفیت بالا و خدمات استثنایی، همواره در حال بهبود و نوآوری بوده‌ایم.', 'bamero'); ?></p>
            <div class="about-features">
                <div class="feature">
                    <i class="fas fa-check-circle"></i>
                    <h3><?php echo esc_html__('کیفیت بالا', 'bamero'); ?></h3>
                    <p><?php echo esc_html__('تمام محصولات ما از برندهای معتبر و با کیفیت بالا هستند.', 'bamero'); ?></p>
                </div>
                <div class="feature">
                    <i class="fas fa-tag"></i>
                    <h3><?php echo esc_html__('قیمت مناسب', 'bamero'); ?></h3>
                    <p><?php echo esc_html__('ما همواره سعی می‌کنیم بهترین قیمت‌ها را به شما ارائه دهیم.', 'bamero'); ?></p>
                </div>
                <div class="feature">
                    <i class="fas fa-shipping-fast"></i>
                    <h3><?php echo esc_html__('تحویل سریع', 'bamero'); ?></h3>
                    <p><?php echo esc_html__('سفارشات شما در سریع‌ترین زمان ممکن تحویل داده می‌شوند.', 'bamero'); ?></p>
                </div>
                <div class="feature">
                    <i class="fas fa-comments"></i>
                    <h3><?php echo esc_html__('مشاوره رایگان', 'bamero'); ?></h3>
                    <p><?php echo esc_html__('تیم ما آماده ارائه مشاوره رایگان در مورد انتخاب رنگ و محصولات است.', 'bamero'); ?></p>
                </div>
            </div>
            <div class="about-cta">
                <a href="<?php echo esc_url(get_permalink(get_page_by_path('about'))); ?>" class="button">
                    <?php echo esc_html__('بیشتر بدانید', 'bamero'); ?>
                </a>
            </div>
        </div>
    </section>

    <!-- Brands Section -->
    <section class="brands-section container" aria-label="برندهای همکار">
        <h2><?php echo esc_html__('برندهای همکار', 'bamero'); ?></h2>
        <div class="brands-grid">
            <div class="brand">
                <img src="<?php echo esc_url(BAMERO_THEME_DIR . '/images/brand-1.png'); ?>" alt="برند ۱" loading="lazy" />
            </div>
            <div class="brand">
                <img src="<?php echo esc_url(BAMERO_THEME_DIR . '/images/brand-2.png'); ?>" alt="برند ۲" loading="lazy" />
            </div>
            <div class="brand">
                <img src="<?php echo esc_url(BAMERO_THEME_DIR . '/images/brand-3.png'); ?>" alt="برند ۳" loading="lazy" />
            </div>
            <div class="brand">
                <img src="<?php echo esc_url(BAMERO_THEME_DIR . '/images/brand-4.png'); ?>" alt="برند ۴" loading="lazy" />
            </div>
            <div class="brand">
                <img src="<?php echo esc_url(BAMERO_THEME_DIR . '/images/brand-5.png'); ?>" alt="برند ۵" loading="lazy" />
            </div>
        </div>
    </section>
</main>

<?php
get_footer();
?>
