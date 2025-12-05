<?php
/**
 * Helper function to render message template form
 *
 * @param string $customer_field_name Customer message field name.
 * @param string $admin_field_name Admin message field name.
 * @param string $customer_value Customer message value.
 * @param string $admin_value Admin message value.
 * @param string $nonce_action Nonce action name.
 * @param string $nonce_name Nonce field name.
 * @param string $submit_name Submit button name.
 * @return void
 */
if (!function_exists('apivro_woonotify_render_message_form')) {
function apivro_woonotify_render_message_form($customer_field_name, $admin_field_name, $customer_value, $admin_value, $nonce_action, $nonce_name, $submit_name) {
    ?>
    <div class="apivro-settings-wrapper">
        <div class="apivro-settings-content">
            <form method="post">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($customer_field_name); ?>"><?php echo esc_html__('Customer Message', 'apivro-woonotify'); ?></label>
                        </th>
                        <td>
                            <textarea id="<?php echo esc_attr($customer_field_name); ?>" 
                                      class="large-text code" 
                                      name="<?php echo esc_attr($customer_field_name); ?>" 
                                      rows="10"><?php echo esc_textarea($customer_value); ?></textarea>
                            <p class="description">
                                <?php echo esc_html__('Message template that will be sent to the customer when this order status is triggered.', 'apivro-woonotify'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($admin_field_name); ?>"><?php echo esc_html__('Admin Message', 'apivro-woonotify'); ?></label>
                        </th>
                        <td>
                            <textarea id="<?php echo esc_attr($admin_field_name); ?>" 
                                      class="large-text code" 
                                      name="<?php echo esc_attr($admin_field_name); ?>" 
                                      rows="8"><?php echo esc_textarea($admin_value); ?></textarea>
                            <p class="description">
                                <?php echo esc_html__('Message template that will be sent to admin when this order status is triggered.', 'apivro-woonotify'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php wp_nonce_field($nonce_action, $nonce_name); ?>

                <p class="submit">
                    <input type="submit" name="<?php echo esc_attr($submit_name); ?>" class="button button-primary" value="<?php echo esc_attr__('Save Changes', 'apivro-woonotify'); ?>">
                </p>
            </form>
        </div>

        <?php apivro_woonotify_render_variables_list(); ?>
    </div>
    <?php
}
}

/**
 * Render available variables list
 *
 * @return void
 */
if (!function_exists('apivro_woonotify_render_variables_list')) {
function apivro_woonotify_render_variables_list() {
    $variables = array(
        '{customer_name}' => __('Customer name', 'apivro-woonotify'),
        '{customer_phone}' => __('Customer phone number', 'apivro-woonotify'),
        '{customer_email}' => __('Customer email', 'apivro-woonotify'),
        '{customer_address}' => __('Customer address', 'apivro-woonotify'),
        '{order_id}' => __('Order ID', 'apivro-woonotify'),
        '{order_number}' => __('Order number', 'apivro-woonotify'),
        '{order_date}' => __('Order date', 'apivro-woonotify'),
        '{order_status}' => __('Order status', 'apivro-woonotify'),
        '{order_items}' => __('Order items list', 'apivro-woonotify'),
        '{shipping_total}' => __('Shipping total', 'apivro-woonotify'),
        '{order_total}' => __('Order total', 'apivro-woonotify'),
        '{invoice_link}' => __('Invoice link', 'apivro-woonotify'),
        '{order_pay_method}' => __('Payment method', 'apivro-woonotify'),
    );
    ?>
    <div class="apivro-variables-list">
        <h2><?php echo esc_html__('Available Variables', 'apivro-woonotify'); ?></h2>
        <p class="description">
            <?php echo esc_html__('Click on any variable to copy it to your clipboard, then paste it into the message fields above.', 'apivro-woonotify'); ?>
        </p>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th style="width: 40%;"><?php echo esc_html__('Variable', 'apivro-woonotify'); ?></th>
                    <th><?php echo esc_html__('Description', 'apivro-woonotify'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($variables as $variable => $description) : ?>
                    <tr>
                        <td><code class="apivro-variable-code" data-variable="<?php echo esc_attr($variable); ?>"><?php echo esc_html($variable); ?></code></td>
                        <td><?php echo esc_html($description); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
}

