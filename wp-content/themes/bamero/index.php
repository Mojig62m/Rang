<?php
/**
 * Fallback / Archive / Search Template for Bamero Theme
 *
 * The homepage is rendered by front-page.php and static pages by page.php.
 * This template is the WordPress-hierarchy fallback used for archives, search
 * results, the blog index and any other listing view. It renders a real loop
 * (products use the WooCommerce product card, other post types a simple card)
 * and never references placeholder/brand imagery.
 *
 * @package Bamero
 */

defined('ABSPATH') || exit;

get_header();

// Contextual, human-readable heading for the current view.
if (is_search()) {
    /* translators: %s: search query */
    $bamero_title = sprintf(__('نتایج جستجو برای: %s', 'bamero'), get_search_query());
} elseif (is_archive()) {
    $bamero_title = wp_strip_all_tags(get_the_archive_title());
} elseif (is_home() && !is_front_page()) {
    $bamero_title = single_post_title('', false);
    $bamero_title = $bamero_title ? $bamero_title : __('آخرین مطالب', 'bamero');
} else {
    $bamero_title = get_the_title();
    $bamero_title = $bamero_title ? $bamero_title : get_bloginfo('name');
}
?>

<main id="main-content" class="bamero-archive" tabindex="-1">

    <div class="page-hero" aria-label="<?php echo esc_attr($bamero_title); ?>">
        <div class="container">
            <h1><?php echo esc_html($bamero_title); ?></h1>
        </div>
    </div>

    <div class="container bamero-archive-body">

        <?php if (have_posts()) : ?>

            <div class="products-grid bamero-archive-grid">
                <?php
                while (have_posts()) :
                    the_post();

                    if (get_post_type() === 'product' && function_exists('wc_get_template_part')) {
                        // Real WooCommerce product card (theme override: woocommerce/content-product.php).
                        wc_get_template_part('content', 'product');
                        continue;
                    }
                    ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class('product-card bamero-post-card'); ?>>
                        <a href="<?php the_permalink(); ?>">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('medium', array('loading' => 'lazy')); ?>
                            <?php else : ?>
                                <div class="bamero-post-art" aria-hidden="true"><span>BAMERO</span></div>
                            <?php endif; ?>
                            <h3><?php the_title(); ?></h3>
                        </a>
                        <div class="bamero-post-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                        <a class="button" href="<?php the_permalink(); ?>">
                            <?php echo esc_html__('ادامه مطلب', 'bamero'); ?>
                        </a>
                    </article>
                    <?php
                endwhile;
                ?>
            </div>

            <?php
            the_posts_pagination(array(
                'mid_size'  => 1,
                'prev_text' => esc_html__('قبلی', 'bamero'),
                'next_text' => esc_html__('بعدی', 'bamero'),
            ));
            ?>

        <?php else : ?>

            <div class="bamero-empty-state text-center">
                <h2><?php echo esc_html__('موردی یافت نشد', 'bamero'); ?></h2>
                <p><?php echo esc_html__('چیزی مطابق جستجوی شما پیدا نشد. می‌توانید عبارت دیگری را امتحان کنید یا از فروشگاه بازدید کنید.', 'bamero'); ?></p>
                <?php get_search_form(); ?>
                <a class="button" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/')); ?>">
                    <?php echo esc_html__('بازگشت به فروشگاه', 'bamero'); ?>
                </a>
            </div>

        <?php endif; ?>

    </div>
</main>

<?php
get_footer();
