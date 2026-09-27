<?php
/**
 * My Account login / registration — phone OTP only.
 *
 * Overrides WooCommerce's default e-mail/password form so that customers on the
 * My Account page authenticate exclusively with an SMS one-time code. There is
 * no e-mail field, no password field and no password-recovery link.
 *
 * @package Bamero
 */

defined('ABSPATH') || exit;
?>

<div class="bamero-myaccount-auth">
    <?php
    if (shortcode_exists('bamero_mobile_auth')) {
        echo do_shortcode('[bamero_mobile_auth]');
    } else {
        // Fail visible rather than silently showing a password form.
        echo '<p class="woocommerce-info" role="status">'
            . esc_html__('ورود با شماره موبایل در دسترس نیست. افزونه احراز هویت موبایل را فعال کنید.', 'bamero')
            . '</p>';
    }
    ?>
</div>
