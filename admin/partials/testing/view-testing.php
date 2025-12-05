<?php
/**
 * Testing Tab View
 *
 * @package NgirimWA_Woo
 */

if (!defined('ABSPATH')) {
    exit;
}

// Display test submission result messages
settings_errors('apivro_woonotify_test');
?>
<form method="post">
    <?php wp_nonce_field('apivro_woonotify_test_action', 'apivro_woonotify_test_nonce'); ?>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="apivro_test_phone"><?php echo esc_html__('Recipients', 'apivro-woonotify'); ?></label>
            </th>
            <td>
                <?php apivro_woonotify_test_phone_render(); ?>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="apivro_test_message"><?php echo esc_html__('Message', 'apivro-woonotify'); ?></label>
            </th>
            <td>
                <?php apivro_woonotify_test_message_render(); ?>
            </td>
        </tr>
    </table>

    <?php submit_button(__('Send Test', 'apivro-woonotify')); ?>
</form>