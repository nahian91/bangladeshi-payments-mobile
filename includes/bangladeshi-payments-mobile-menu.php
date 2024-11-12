<?php
    function bangladeshi_payments_add_admin_menu() {
        add_menu_page(
            __('BD Payments', 'bangladeshi-payments-mobile'), // Page title
            __('BD Payments', 'bangladeshi-payments-mobile'), // Menu title
            'manage_options', // Capability
            'bangladeshi-payments-mobile', // Menu slug
            'bangladeshi_payments_render_general_page', // Callback function
            'dashicons-money', // Icon
            56 // Position
        );

        // Transaction Info submenu
        add_submenu_page(
            'bangladeshi-payments-mobile',
            __('Transaction Info', 'bangladeshi-payments-mobile'),
            __('Transaction Info', 'bangladeshi-payments-mobile'),
            'manage_options',
            'bangladeshi-payments-transaction-info',
            'bangladeshi_payments_render_transaction_info_page'
        );
    }
    add_action('admin_menu', 'bangladeshi_payments_add_admin_menu');

    // Render the General page
    function bangladeshi_payments_render_general_page() {
        $image_url = plugins_url('/img/banner.jpg', __FILE__);

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
                    <img src="<?php echo esc_url($image_url); ?>" alt="">
                    <p><?php esc_html_e('Welcome to the Bangladeshi Payments Mobile plugin! This plugin supports mobile payment gateways including bKash, Nagad, Rocket, and Upay.', 'bangladeshi-payments-mobile'); ?></p>

                    <p><?php esc_html_e('Use this plugin to easily integrate popular Bangladeshi mobile payment methods with your WooCommerce store. Ensure you configure each payment method in the settings to start receiving payments.', 'bangladeshi-payments-mobile'); ?></p>

                    <p><?php esc_html_e('For any questions or support, please refer to the documentation or contact us directly.', 'bangladeshi-payments-mobile'); ?></p>

                    <p><?php esc_html_e('Thank you for choosing Bangladeshi Payments Mobile!', 'bangladeshi-payments-mobile'); ?></p>

                    <!-- Review Button -->
                    <a href="https://wordpress.org/support/view/plugin-reviews/bangladeshi-payments-mobile" class="button button-primary" target="_blank">
                        <?php esc_html_e('Leave a Review', 'bangladeshi-payments-mobile'); ?>
                    </a>
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
                                            <td class="<?php echo $status_class; ?>"><?php echo $status; ?></td>
                                            <td><a href="<?php echo esc_url($edit_link); ?>" target="_blank" class="button button-primary">Edit</a></td>
                                        </tr>
                                        <?php
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                        <!-- Transaction Info Page Link in a Green Border Notice Box -->
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

    function bangladeshi_payments_render_transaction_info_page() {

    // Check if the nonce is set and valid
    if (isset($_GET['bangladeshi_payments_nonce'])) {
        $nonce = wp_unslash($_GET['bangladeshi_payments_nonce']); // Unsplash the nonce
        $nonce = sanitize_text_field($nonce); // Sanitize the nonce

        if (!wp_verify_nonce($nonce, 'bangladeshi_payments_report')) {
            // Invalid nonce - exit the function
            echo '<div class="notice notice-error"><p>' . esc_html__('Security check failed. Please try again.', 'bangladeshi-payments-mobile') . '</p></div>';
            return; // Stop processing further
        }
    }

    // Get sorting and filtering parameters
    $order_by = isset($_GET['orderby']) ? sanitize_text_field(wp_unslash($_GET['orderby'])) : 'ID';
    $order = isset($_GET['order']) ? sanitize_text_field(wp_unslash($_GET['order'])) : 'DESC';
    $selected_payment_method = isset($_GET['payment_method']) ? sanitize_text_field(wp_unslash($_GET['payment_method'])) : '';

    // Get date range parameters
    $start_date = isset($_GET['start_date']) ? sanitize_text_field(wp_unslash($_GET['start_date'])) : '';
    $end_date = isset($_GET['end_date']) ? sanitize_text_field(wp_unslash($_GET['end_date'])) : '';

    // Toggle sorting order
    $new_order = ($order === 'ASC') ? 'DESC' : 'ASC';

    // Payment methods for the select option
    $payment_methods = ['all' => 'All', 'bkash' => 'Bkash', 'rocket' => 'Rocket', 'nagad' => 'Nagad', 'upay' => 'Upay'];

    ?>
    <div class="wrap bpm-wrap">
        <h4><?php esc_html_e('Transaction Information', 'bangladeshi-payments-mobile'); ?></h4>
        <br>
        <!-- Report Form -->
        <form method="GET">
            <?php wp_nonce_field('bangladeshi_payments_report', 'bangladeshi_payments_nonce'); ?>
            <input type="hidden" name="page" value="<?php echo esc_attr(sanitize_key($_GET['page'])); ?>">
            <label for="payment_method"><?php esc_html_e('Payment Method:', 'bangladeshi-payments-mobile'); ?></label>
            <select name="payment_method" id="payment_method">
                <?php foreach ($payment_methods as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($selected_payment_method, $value); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            &nbsp;
            <label for="start_date"><?php esc_html_e('Start Date:', 'bangladeshi-payments-mobile'); ?></label>
            <input type="date" name="start_date" id="start_date" value="<?php echo esc_attr($start_date ? gmdate('Y-m-d', strtotime($start_date)) : ''); ?>">
            &nbsp;
            <label for="end_date"><?php esc_html_e('End Date:', 'bangladeshi-payments-mobile'); ?></label>
            <input type="date" name="end_date" id="end_date" value="<?php echo esc_attr($end_date ? gmdate('Y-m-d', strtotime($end_date)) : ''); ?>">
            &nbsp;
            <input type="submit" class="button" value="<?php esc_attr_e('Generate Report', 'bangladeshi-payments-mobile'); ?>">
        </form>
        <br>

        <?php
        // Process the report generation
        $total_amount = 0;

        // Fetch orders within the date range
        if (!empty($start_date) && !empty($end_date)) {
            // Convert dates to timestamps for comparison
            $start_timestamp = strtotime($start_date);
            $end_timestamp = strtotime($end_date);

            // Check if start date is the same as or earlier than end date
            if ($start_timestamp > $end_timestamp) {
                echo '<div class="notice notice-error"><p>' . esc_html__('Start date must be the same as or earlier than end date.', 'bangladeshi-payments-mobile') . '</p></div>';
            } else {
                // Fetch orders within the specified date range
                $args = [
                    'limit' => -1,
                    'status' => 'completed', // You can change this to the desired order status
                    'date_created' => $start_date . '...' . $end_date, // Date range
                ];
                $orders = wc_get_orders($args);

                // Calculate total amount for the selected payment method
                foreach ($orders as $order) {
                    if ($selected_payment_method === 'all' || $order->get_payment_method() === $selected_payment_method) {
                        $total_amount += $order->get_total();
                    }
                }

                // Display total amount only if there's a positive total
                if ($total_amount > 0) {
                    // Escape the label and format the total amount
                    echo '<h2>' . esc_html__('Total Amount: ', 'bangladeshi-payments-mobile') . wp_kses_post(wc_price($total_amount)) . '</h2>';
                }

            }
        }
    ?>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>
                    <a href="<?php echo esc_url(add_query_arg(['orderby' => 'ID', 'order' => $new_order])); ?>">
                        <?php esc_html_e('Order ID', 'bangladeshi-payments-mobile'); ?>
                    </a>
                </th>
                <th>
                    <a href="<?php echo esc_url(add_query_arg(['orderby' => 'payment_method', 'order' => $new_order])); ?>">
                        <?php esc_html_e('Payment Method', 'bangladeshi-payments-mobile'); ?>
                    </a>
                </th>
                <th>
                    <a href="<?php echo esc_url(add_query_arg(['orderby' => 'transaction_id', 'order' => $new_order])); ?>">
                        <?php esc_html_e('Transaction ID', 'bangladeshi-payments-mobile'); ?>
                    </a>
                </th>
                <th><?php esc_html_e('Phone Number', 'bangladeshi-payments-mobile'); ?></th>
                <th>
                    <a href="<?php echo esc_url(add_query_arg(['orderby' => 'amount', 'order' => $new_order])); ?>">
                        <?php esc_html_e('Amount', 'bangladeshi-payments-mobile'); ?>
                    </a>
                </th>
                <th>
                    <a href="<?php echo esc_url(add_query_arg(['orderby' => 'date_created', 'order' => $new_order])); ?>">
                        <?php esc_html_e('Date', 'bangladeshi-payments-mobile'); ?>
                    </a>
                </th>
                <th>
                    <a href="<?php echo esc_url(add_query_arg(['orderby' => 'order_status', 'order' => $new_order])); ?>">
                    <?php esc_html_e('Order Status', 'bangladeshi-payments-mobile'); ?></a>
                </th>
                <th><?php esc_html_e('Action', 'bangladeshi-payments-mobile'); ?></th>
            </tr>
        </thead>
    <tbody>
        <?php
        // Initialize an array to hold filtered orders
        $filtered_orders = [];

        // Fetch all orders again for displaying the table
        $args = array(
            'limit' => -1, // Retrieve all orders
        );

        $orders = wc_get_orders($args);

        // Filter orders based on selected payment method and date range
        foreach ($orders as $order) {
            $order_date = $order->get_date_created()->date('Y-m-d');

            // Check if the order is within the date range and matches the payment method
            if (
                ($start_date === '' || $order_date >= $start_date) &&
                ($end_date === '' || $order_date <= $end_date) &&
                ($selected_payment_method === 'all' || $order->get_payment_method() === $selected_payment_method)
            ) {
                $filtered_orders[] = $order; // Add matching order to the filtered list
            }
        }

        // Sort orders based on the selected column
        usort($filtered_orders, function($a, $b) use ($order_by, $order) {
            switch ($order_by) {
                case 'payment_method':
                    $result = strcmp($a->get_payment_method(), $b->get_payment_method());
                    break;
                case 'transaction_id':
                    $result = strcmp(get_post_meta($a->get_id(), '_transaction_id', true), get_post_meta($b->get_id(), '_transaction_id', true));
                    break;
                case 'amount':
                    $result = $a->get_total() - $b->get_total();
                    break;
                case 'date_created':
                    $result = strtotime($a->get_date_created()->date('Y-m-d')) - strtotime($b->get_date_created()->date('Y-m-d'));
                    break;
                default:
                    $result = $a->get_id() - $b->get_id();
            }

            return ($order === 'ASC') ? $result : -$result; // Reverse the result if the order is descending
        });

        foreach ($filtered_orders as $order) {
            $amount = $order->get_total();
            $payment_method = $order->get_payment_method();
        
            // Get order status and format it
            $order_status = wc_get_order_status_name($order->get_status());
        
            // Get transaction ID and phone number based on payment method
            $transaction_id = '';
            $phone_number = '';
        
            if ($payment_method === 'bkash') {
                $transaction_id = get_post_meta($order->get_id(), '_bkash_transaction_id', true);
                $phone_number = get_post_meta($order->get_id(), '_bkash_phone', true);
            } elseif ($payment_method === 'rocket') {
                $transaction_id = get_post_meta($order->get_id(), '_rocket_transaction_id', true);
                $phone_number = get_post_meta($order->get_id(), '_rocket_phone', true);
            } elseif ($payment_method === 'nagad') {
                $transaction_id = get_post_meta($order->get_id(), '_nagad_transaction_id', true);
                $phone_number = get_post_meta($order->get_id(), '_nagad_phone', true);
            } elseif ($payment_method === 'upay') {
                $transaction_id = get_post_meta($order->get_id(), '_upay_transaction_id', true);
                $phone_number = get_post_meta($order->get_id(), '_upay_phone', true);
            }
        
            echo '<tr>';
            echo '<td>' . esc_html($order->get_id()) . '</td>';
            echo '<td>' . esc_html(ucwords($payment_method)) . '</td>';
            echo '<td>' . esc_html($transaction_id ?: 'N/A') . '</td>';
            echo '<td>' . esc_html($phone_number ?: 'N/A') . '</td>';
            echo '<td>' . wp_kses_post(wc_price($amount)) . '</td>';
            echo '<td>' . esc_html($order->get_date_created()->date('d F Y')) . '</td>';
            echo '<td>' . esc_html($order_status) . '</td>'; // Display Order Status
            echo '<td><a href="' . esc_url($order->get_edit_order_url()) . '" class="button button-primary">' . esc_html__('Edit Order', 'bangladeshi-payments-mobile') . '</a></td>';
        }
        
        ?>
    </tbody>
</table>



    <!-- Display Total Amount after the table -->
        <?php if ($total_amount > 0): ?>
            <h2><?php esc_html_e('Total Amount: ', 'bangladeshi-payments-mobile'); echo wp_kses_post(wc_price($total_amount)); ?></h2>
        <?php endif; ?>
    </div>
    <?php
     }

?>
