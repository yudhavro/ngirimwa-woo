<?php
if (!defined('ABSPATH')) {
    exit;
}

$api_key = get_option('apivro_woonotify_api_key', '');
$admin_phone = get_option('apivro_woonotify_admin_phone', '');
?>
<form method="post" action="">
    <?php wp_nonce_field('apivro-woonotify-settings-group-options'); ?>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="apivro_woonotify_api_key"><?php echo esc_html__('API Key', 'apivro-woonotify'); ?></label>
            </th>
            <td>
                <input type="password" 
                       id="apivro_woonotify_api_key" 
                       name="apivro_woonotify_api_key" 
                       value="<?php echo esc_attr($api_key); ?>" 
                       class="regular-text code" 
                       autocomplete="off" />
                <p class="description">
                    <?php echo esc_html__('Enter your NgirimWA API Key. You can get your API key from your NgirimWA dashboard at dash.ngirimwa.com', 'apivro-woonotify'); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="apivro_woonotify_admin_phone"><?php echo esc_html__('WhatsApp Admin Number', 'apivro-woonotify'); ?></label>
            </th>
            <td>
                <textarea id="apivro_woonotify_admin_phone" 
                          name="apivro_woonotify_admin_phone" 
                          rows="5" 
                          class="large-text code" 
                          placeholder="<?php esc_attr_e('628123456789,123456789289524319@g.us', 'apivro-woonotify'); ?>"><?php echo esc_textarea($admin_phone); ?></textarea>
                <p class="description">
                    <?php echo esc_html__('Enter WhatsApp numbers or Group IDs, separated by commas (without spaces). Each recipient will receive the notification. Example: 628123456789,123456789289524319@g.us', 'apivro-woonotify'); ?>
                </p>
            </td>
        </tr>
    </table>
    <?php submit_button(); ?>
</form>
