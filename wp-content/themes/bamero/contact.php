<?php
/**
 * Template Name: Contact
 *
 * @package Bamero
 */

defined('ABSPATH') || exit;

get_header();

$phone   = get_theme_mod('bamero_phone_number', '۰۹۱۳۴۲۹۲۳۲۹');
$address = get_theme_mod('bamero_address', 'اصفهان، خیابان خرم، نرسیده به خیابان صارمیه');
?>

<main id="main-content" class="contact-page" tabindex="-1">
    <div class="page-header container">
        <h1><?php echo esc_html__('تماس با ما', 'bamero'); ?></h1>
        <p><?php echo esc_html__('از طریق فرم یا اطلاعات تماس با ما در ارتباط باشید.', 'bamero'); ?></p>
    </div>

    <div class="contact-container container">
        <div class="contact-info">
            <h2><?php echo esc_html__('اطلاعات تماس', 'bamero'); ?></h2>
            <ul>
                <li>
                    <strong><?php echo esc_html__('آدرس:', 'bamero'); ?></strong>
                    <?php echo esc_html($address); ?>
                </li>
                <li>
                    <strong><?php echo esc_html__('تلفن:', 'bamero'); ?></strong>
                    <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a>
                </li>
                <li>
                    <strong><?php echo esc_html__('ساعات کاری:', 'bamero'); ?></strong>
                    <?php echo esc_html__('شنبه تا چهارشنبه ۸–۱۷ | پنجشنبه ۸–۱۴', 'bamero'); ?>
                </li>
            </ul>
        </div>

        <div class="contact-form">
            <h2><?php echo esc_html__('فرم تماس', 'bamero'); ?></h2>
            <p class="form-help" id="contact-help"><?php echo esc_html__('برای پاسخ‌گویی و پیگیری، شمارهٔ موبایل معتبر وارد کنید. پاسخ از طریق پیامک انجام می‌شود.', 'bamero'); ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bamero-contact-form">
                    <?php wp_nonce_field('bamero_contact', 'bamero_contact_nonce'); ?>
                    <input type="hidden" name="action" value="bamero_contact_submit">
                    <p>
                        <label for="c-name"><?php echo esc_html__('نام', 'bamero'); ?></label>
                        <input type="text" id="c-name" name="name" required autocomplete="name">
                    </p>
                    <p>
                        <label for="c-phone"><?php echo esc_html__('شماره موبایل', 'bamero'); ?> <span aria-hidden="true">*</span></label>
                        <input type="tel" id="c-phone" name="phone" required inputmode="tel" autocomplete="tel" aria-describedby="contact-help" placeholder="۰۹۱۲۱۲۳۴۵۶۷">
                    </p>
                    <p>
                        <label for="c-message"><?php echo esc_html__('پیام', 'bamero'); ?></label>
                        <textarea id="c-message" name="message" rows="5" required></textarea>
                    </p>
                    <button type="submit" class="button"><?php echo esc_html__('ارسال پیام', 'bamero'); ?></button>
                </form>
        </div>
    </div>
</main>

<?php
get_footer();
