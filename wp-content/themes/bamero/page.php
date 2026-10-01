<?php
/**
 * Default Page Template for Bamero Theme
 *
 * Renders the page title, breadcrumb and — critically — the page content via
 * the_content(), so shortcode-driven pages work correctly:
 *   - mobile-login   -> [bamero_mobile_auth]   (SMS/OTP register + login)
 *   - consultation   -> [bamero_color_consultation]
 *   - cart           -> [woocommerce_cart]
 *   - checkout       -> [woocommerce_checkout]
 *   - my-account     -> [woocommerce_my_account]
 *   - any generic page content
 *
 * Without this template the theme fell back to index.php (a homepage layout)
 * which never called the_content(), leaving those pages blank.
 *
 * @package Bamero
 */

defined('ABSPATH') || exit;

get_header();

$bamero_is_wc_page = function_exists('is_cart') && (is_cart() || is_checkout() || is_account_page());
?>

<main id="main-content" class="bamero-page <?php echo $bamero_is_wc_page ? 'bamero-page--commerce' : 'bamero-page--content'; ?>" tabindex="-1">

    <?php if (!$bamero_is_wc_page) : ?>
    <div class="page-hero" aria-label="<?php echo esc_attr(get_the_title()); ?>">
        <div class="container">
            <h1><?php echo esc_html(get_the_title()); ?></h1>
        </div>
    </div>
    <nav class="breadcrumb-bar container" aria-label="<?php echo esc_attr__('مسیر صفحه', 'bamero'); ?>">
        <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html__('خانه', 'bamero'); ?></a>
        <span aria-hidden="true">/</span>
        <span aria-current="page"><?php echo esc_html(get_the_title()); ?></span>
    </nav>
    <?php endif; ?>

    <div class="bamero-page-body container">
        <?php
        while (have_posts()) :
            the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('bamero-entry'); ?>>
                <?php if ($bamero_is_wc_page) : ?>
                    <h1 class="screen-reader-text"><?php echo esc_html(get_the_title()); ?></h1>
                <?php endif; ?>
                <div class="entry-content">
                    <?php
                    the_content();

                    wp_link_pages(array(
                        'before' => '<div class="page-links">' . esc_html__('صفحات:', 'bamero'),
                        'after'  => '</div>',
                    ));
                    ?>
                </div>
            </article>
            <?php
            if (comments_open() || get_comments_number()) {
                comments_template();
            }
        endwhile;
        ?>
    </div>
</main>

<?php
get_footer();
