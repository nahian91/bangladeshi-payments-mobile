<?php 

// Render the General page
function bangladeshi_payments_render_general_page() {

    // Initialize WooCommerce payment gateways class
    $gateways = WC()->payment_gateways->get_available_payment_gateways();            

    // Define your custom gateway IDs
    $custom_gateways = [
        'bpm_bkash' => 'bKash',
        'bpm_nagad' => 'Nagad',
        'bpm_rocket' => 'Rocket',
        'bpm_upay' => 'Upay'
    ];
    ?>
    <div class="wrap bpm-wrap">
        <h4><?php esc_html_e('General Information', 'bangladeshi-payments-mobile'); ?></h4>
        <div class="bpb-general">
            <div class="bpb-general-content">
                <div class="bpm-banner"></div>
                <p><?php esc_html_e('Welcome to the Bangladeshi Payments Mobile plugin! This plugin supports mobile payment gateways including bKash, Nagad, Rocket, and Upay.', 'bangladeshi-payments-mobile'); ?></p>

                <p><?php esc_html_e('Use this plugin to easily integrate popular Bangladeshi mobile payment methods with your WooCommerce store. Ensure you configure each payment method in the settings to start receiving payments.', 'bangladeshi-payments-mobile'); ?></p>

                <p><?php esc_html_e('For any questions or support, please refer to the documentation or contact us directly.', 'bangladeshi-payments-mobile'); ?></p>

                <p><?php esc_html_e('Thank you for choosing Bangladeshi Payments Mobile!', 'bangladeshi-payments-mobile'); ?></p>

                <!-- Review Button -->
                <a href="https://wordpress.org/support/view/plugin-reviews/bangladeshi-payments-mobile" class="button button-primary" target="_blank">
                    <?php esc_html_e('Leave a Review', 'bangladeshi-payments-mobile'); ?>
                </a>

                <h4 style="margin-top: 50px;"><?php esc_html_e('My Other Plugins', 'bangladeshi-payments-mobile'); ?></h4>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Plugin Name', 'bangladeshi-payments-mobile'); ?></th>
                            <th><?php esc_html_e('Download', 'bangladeshi-payments-mobile'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Define your plugins list
                        $my_plugins = [
                            [
                                'name' => 'Awesome Blocks for Gutenberg',
                                'download' => 'https://wordpress.org/plugins/awesome-block/'
                            ],
                            [
                                'name' => 'Awesome Invoice, Delivery Notes & Packing Slips',
                                'download' => 'https://wordpress.org/plugins/awesome-invoice-delivery-notes-packing-slips/'
                            ],
                            [
                                'name' => 'Awesome Team Widgets for Elementor',
                                'download' => 'https://wordpress.org/plugins/awesome-team-widgets/'
                            ],
                            [
                                'name' => 'Awesome Widgets for Elementor',
                                'download' => 'https://wordpress.org/plugins/awesome-widgets/'
                            ],
                            [
                                'name' => 'Infinity FAQ Schema & Accordion',
                                'download' => 'https://wordpress.org/plugins/infinity-simple-faq/'
                            ],
                            [
                                'name' => 'Infinity Testimonials',
                                'download' => 'https://wordpress.org/plugins/infinity-testimonilas/'
                            ],
                            [
                                'name' => 'Ultimate CF7 Integration with Analytics, Reports & Export',
                                'download' => 'https://wordpress.org/plugins/nahian-ultimate-integration-for-contact-form-7-and-elementor/'
                            ],
                        ];

                        foreach ($my_plugins as $plugin) {
                            ?>
                            <tr>
                                <td><?php echo esc_html($plugin['name']); ?></td>
                                <td>
                                    <a href="<?php echo esc_url($plugin['download']); ?>" class="button button-secondary" target="_blank">
                                        <?php esc_html_e('Download', 'bangladeshi-payments-mobile'); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>

            </div>

            <div class="bpb-general-support">
                <div class="payment-statistics">
                    <h4><?php echo esc_html('Payment Method Statistics', 'bangladeshi-payments-mobile') ?></h4>
                    <table class="transaction-table">
                        <thead>
                            <tr>
                                <th><?php echo esc_html('Payment Method', 'bangladeshi-payments-mobile') ?></th>
                                <th><?php echo esc_html('Total Amount', 'bangladeshi-payments-mobile') ?></th>
                                <th><?php echo esc_html('Status', 'bangladeshi-payments-mobile') ?></th>
                                <th><?php echo esc_html('Action', 'bangladeshi-payments-mobile') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $allowed_gateways = ['bkash', 'nagad', 'upay', 'rocket'];
                            $gateways = WC()->payment_gateways->payment_gateways();
                            foreach ($gateways as $gateway) {
                                if (in_array($gateway->id, $allowed_gateways)) {
                                    $total_amount = 0;
                                    $status = ($gateway->enabled === 'yes') ? 'Active' : 'Inactive';
                                    $status_class = ($gateway->enabled === 'yes') ? 'status-active' : 'status-inactive';
                                    $args = array(
                                        'limit' => -1,
                                        'status' => 'completed',
                                        'payment_method' => $gateway->id,
                                    );
                                    $orders = wc_get_orders($args);
                                    foreach ($orders as $order) {
                                        $total_amount += $order->get_total();
                                    }
                                    $edit_link = admin_url('admin.php?page=wc-settings&tab=checkout&section=' . $gateway->id);
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($gateway->get_title()); ?></td>
                                        <td><?php echo wc_price($total_amount); ?></td>
                                        <td class="<?php echo esc_html($status_class); ?>"><?php echo esc_html($status); ?></td>
                                        <td><a href="<?php echo esc_url($edit_link); ?>" target="_blank" class="button button-primary">Edit</a></td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>

                    <!-- Transaction Info Page Link -->
                    <div class="notice transaction-info-notice">
                        <p><strong><?php echo esc_html('More Transaction Report', 'bangladeshi-payments-mobile') ?></strong> - <a href="<?php echo esc_url(admin_url('admin.php?page=bangladeshi-payments-transaction-info')); ?>"><?php echo esc_html('View Transaction Info', 'bangladeshi-payments-mobile') ?></a></p>
                    </div>
                </div>
                <br>

                <div class="single-bpb-support">
                    <ul>
                        <li>
                            <span class="dashicons dashicons-email das-icons"></span> 
                            <div id="email-text">nahiansylhet@gmail.com</div>
                            <button class="copy-btn" onclick="copyToClipboard('#email-text')"><span class="dashicons dashicons-admin-page"></span> </button>
                        </li>
                        <li>
                            <span class="dashicons dashicons-admin-site das-icons"></span>
                            <div id="website-text">www.devnahian.com</div>
                            <button class="copy-btn" onclick="copyToClipboard('#website-text')"><span class="dashicons dashicons-admin-page"></span></button>
                        </li>
                        <li>
                            <span class="dashicons dashicons-whatsapp das-icons"></span>
                            <div id="whatsapp-text">+8801686195607</div>
                            <button class="copy-btn" onclick="copyToClipboard('#whatsapp-text')"><span class="dashicons dashicons-admin-page"></span></button>
                        </li>
                    </ul>

                    <script>
                        // Function to copy text to clipboard
                        function copyToClipboard(elementId) {
                            var text = document.querySelector(elementId).textContent;
                            var tempInput = document.createElement('input');
                            document.body.appendChild(tempInput);
                            tempInput.value = text;
                            tempInput.select();
                            document.execCommand('copy');
                            document.body.removeChild(tempInput);
                            alert('Copied: ' + text);
                        }
                    </script>
                </div>
            </div>
        </div>
    </div>
    <?php
}
