<?php
/**
 * Template Name: 404
 * Description: صفحه ۴۰۴ سفارشی برای تم بامرو
 */

get_header();
?>

<main id="main-content" class="rtl">
    <div class="error-404 container text-center">
        <h1>۴۰۴</h1>
        <h2>صفحه یافت نشد</h2>
        <p>متأسفانه صفحه‌ای که به دنبال آن هستید، یافت نشد.</p>
        
        <div class="error-actions margin-bottom">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="button">بازگشت به صفحه اصلی</a>
            <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="button">بازگشت به فروشگاه</a>
        </div>

        <div class="error-search margin-bottom">
            <h3>جستجو در سایت</h3>
            <?php get_search_form(); ?>
        </div>
    </div>
</main>

<?php
get_footer();
?>