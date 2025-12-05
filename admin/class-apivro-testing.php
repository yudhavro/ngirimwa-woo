<?php
/**
 * Testing Functionality
 *
 * @package NgirimWA_Woo
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_init', 'apivro_woonotify_handle_test_submit');

function apivro_woonotify_handle_test_submit() {
    if (isset($_POST['apivro_test_phone']) && isset($_POST['apivro_test_message']) && check_admin_referer('apivro_woonotify_test_action', 'apivro_woonotify_test_nonce')) {
        $phone_input = sanitize_textarea_field($_POST['apivro_test_phone']);
        $message = sanitize_textarea_field($_POST['apivro_test_message']);

        if (empty($phone_input) || empty($message)) {
            add_settings_error('apivro_woonotify_test', 'apivro_woonotify_test_message', __('Phone number/Group ID and message are required.', 'apivro-woonotify'), 'error');
        } else {
            $recipients = apivro_woonotify_parse_recipients($phone_input);
            if (empty($recipients)) {
                add_settings_error('apivro_woonotify_test', 'apivro_woonotify_test_message', __('Invalid phone number or Group ID format.', 'apivro-woonotify'), 'error');
            } else {
                $success_count = 0;
                $fail_count = 0;
                
                foreach ($recipients as $recipient) {
                    $response = apivro_send_message($recipient, $message);
                    if ($response && isset($response['success']) && $response['success']) {
                        $success_count++;
                    } else {
                        $fail_count++;
                    }
                }
                
                if ($success_count > 0 && $fail_count === 0) {
                    $message_text = sprintf(__('Message sent successfully to %d recipient(s)!', 'apivro-woonotify'), $success_count);
                    add_settings_error('apivro_woonotify_test', 'apivro_woonotify_test_message', $message_text, 'updated');
                } elseif ($success_count > 0 && $fail_count > 0) {
                    $message_text = sprintf(__('Message sent to %d recipient(s), but failed for %d recipient(s).', 'apivro-woonotify'), $success_count, $fail_count);
                    add_settings_error('apivro_woonotify_test', 'apivro_woonotify_test_message', $message_text, 'error');
                } else {
                    add_settings_error('apivro_woonotify_test', 'apivro_woonotify_test_message', __('Failed to send message. Please check your API key and phone number/Group ID.', 'apivro-woonotify'), 'error');
                }
            }
        }
    }
}

function apivro_woonotify_test_phone_render() {
    ?>
    <textarea name='apivro_test_phone' rows='3' class='large-text code' placeholder='<?php esc_attr_e('628123456789,123456789289524319@g.us', 'apivro-woonotify'); ?>'></textarea>
    <p class="description"><?php echo esc_html__('Enter WhatsApp numbers or Group IDs, separated by commas (without spaces). Example: 628123456789,123456789289524319@g.us', 'apivro-woonotify'); ?></p>
    <?php
}

function apivro_woonotify_test_message_render() {
    ?>
    <textarea name='apivro_test_message' rows='5' class='large-text' placeholder='<?php echo esc_attr__('Type your test message here...', 'apivro-woonotify'); ?>'></textarea>
    <?php
}

