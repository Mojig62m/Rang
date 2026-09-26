<?php
/**
 * Template Name: About
 *
 * @package Bamero
 */

defined('ABSPATH') || exit;

get_header();
?>

<main id="main-content" class="about-page" tabindex="-1">
    <div class="page-header container">
        <h1><?php echo esc_html__('درباره بامرو', 'bamero'); ?></h1>
        <p><?php echo esc_html__('مرجع رنگ، پوشش و چسب ساختمانی', 'bamero'); ?></p>
    </div>

    <div class="about-container container">
        <div class="about-content">
            <h2><?php echo esc_html__('داستان ما', 'bamero'); ?></h2>
            <p>
                <?php echo esc_html__('بامرو با تمرکز بر فروش تخصصی رنگ، پوشش‌های محافظ و چسب‌های ساختمانی فعالیت می‌کند. هدف ما ارائه محصولات استاندارد با مشخصات فنی شفاف و پشتیبانی واقعی به پروژه‌های ساختمانی و مصرف‌کنندگان نهایی است.', 'bamero'); ?>
            </p>

            <h2><?php echo esc_html__('مأموریت', 'bamero'); ?></h2>
            <p>
                <?php echo esc_html__('ارائه محصول با کیفیت قابل ردیابی، مشاوره فنی صادقانه و تحویل قابل اعتماد. ما معتقدیم انتخاب صحیح رنگ و پوشش، عمر مفید سازه را افزایش می‌دهد.', 'bamero'); ?>
            </p>

            <h2><?php echo esc_html__('مزیت‌های رقابتی', 'bamero'); ?></h2>
            <ul class="about-features">
                <li><strong><?php echo esc_html__('مشخصات فنی شفاف:', 'bamero'); ?></strong> <?php echo esc_html__('اطلاعات پوشش، زیرسازی و شرایط اجرا در دسترس است.', 'bamero'); ?></li>
                <li><strong><?php echo esc_html__('مشاوره تخصصی:', 'bamero'); ?></strong> <?php echo esc_html__('راهنمایی انتخاب محصول بر اساس سطح، شرایط جوی و کاربری.', 'bamero'); ?></li>
                <li><strong><?php echo esc_html__('تنوع حجم:', 'bamero'); ?></strong> <?php echo esc_html__('بسته‌بندی‌های متداول بازار برای مصرف جزئی و پروژه‌ای.', 'bamero'); ?></li>
                <li><strong><?php echo esc_html__('پشتیبانی پس از فروش:', 'bamero'); ?></strong> <?php echo esc_html__('پیگیری سفارش و پاسخگویی فنی.', 'bamero'); ?></li>
            </ul>
        </div>
    </div>
</main>

<?php
get_footer();
