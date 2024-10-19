<?php
// Add an admin menu page for transaction info
function bangladeshi_payments_add_admin_menu() {
    add_menu_page(
        __('Transaction Info', 'bangladeshi-payments-mobile'), // Page title
        __('Transaction Info', 'bangladeshi-payments-mobile'), // Menu title
        'manage_options', // Capability
        'bangladeshi-payments-transaction-info', // Menu slug
        'bangladeshi_payments_render_transaction_info_page', // Callback function
        'dashicons-list-view', // Icon
        56 // Position
    );
}
function bangladeshi_payments_render_transaction_info_page() {
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
    <div class="wrap">
        <h1><?php esc_html_e('Transaction Information', 'bangladeshi-payments-mobile'); ?></h1>
        <br>
        <!-- Report Form -->
        <form method="GET">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">
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
            <input type="date" name="start_date" id="start_date" value="<?php echo esc_attr($start_date); ?>">
            &nbsp;
            <label for="end_date"><?php esc_html_e('End Date:', 'bangladeshi-payments-mobile'); ?></label>
            <input type="date" name="end_date" id="end_date" value="<?php echo esc_attr($end_date); ?>">
            &nbsp;
            <input type="submit" class="button" value="<?php esc_attr_e('Generate Report', 'bangladeshi-payments-mobile'); ?>">
        </form>
        <br>

        <?php
        // Process the report generation
        if (!empty($start_date) && !empty($end_date)) {
            // Convert dates to timestamps for comparison
            $start_timestamp = strtotime($start_date);
            $end_timestamp = strtotime($end_date);

            // Check if start date is the same as or earlier than end date
            if ($start_timestamp > $end_timestamp) {
                echo '<div class="notice notice-error"><p>' . esc_html__('Start date must be the same as or earlier than end date.', 'bangladeshi-payments-mobile') . '</p></div>';
            } else {
                $total_amount = 0;

                // Fetch orders within the date range
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

                // Display total amount
                if ($total_amount > 0) {
                    echo '<h2>' . esc_html__('Total Amount: ', 'bangladeshi-payments-mobile') . wc_price($total_amount) . '</h2>';
                } else {
                    echo '<div class="notice notice-warning"><p>' . esc_html__('No transactions found for the selected criteria.', 'bangladeshi-payments-mobile') . '</p></div>';
                }
            }
        }
        ?>

        <!-- Existing Transaction Table -->
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
                        <a href="<?php echo esc_url(add_query_arg(['orderby' => 'status', 'order' => $new_order])); ?>">
                            <?php esc_html_e('Status', 'bangladeshi-payments-mobile'); ?>
                        </a>
                    </th>
                    <th><?php esc_html_e('Action', 'bangladeshi-payments-mobile'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch orders again for displaying the table
                $args = array(
                    'limit' => -1, // Retrieve all orders
                );

                $orders = wc_get_orders($args);

                // Filter orders based on selected payment method
                if ($selected_payment_method && $selected_payment_method !== 'all') {
                    $orders = array_filter($orders, function($order) use ($selected_payment_method) {
                        return $order->get_payment_method() === $selected_payment_method;
                    });
                }

                // Sort orders based on the selected column
                usort($orders, function($a, $b) use ($order_by, $order) {
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
                        case 'status':
                            $result = strcmp($a->get_status(), $b->get_status());
                            break;
                        case 'phone_number':
                            $result = strcmp($a->get_billing_phone(), $b->get_billing_phone());
                            break;
                        default:
                            $result = $a->get_id() - $b->get_id(); // Default sort by Order ID
                    }

                    return ($order === 'ASC') ? $result : -$result;
                });

                if (empty($orders)) {
                    echo '<tr><td colspan="7">' . esc_html__('No transactions found for the selected criteria.', 'bangladeshi-payments-mobile') . '</td></tr>';
                } else {
                    foreach ($orders as $order) {
                        $payment_method = $order->get_payment_method();
                        $transaction_id = '';
                        $phone_number = '';
                        $amount = $order->get_total(); // Get the order amount

                        // Fetch transaction ID and phone number based on payment method
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

                        $view_order_url = admin_url('post.php?post=' . $order->get_id() . '&action=edit');
                        ?>
                        <tr>
                            <td><?php echo esc_html($order->get_id()); ?></td> <!-- Display Order ID -->
                            <td><?php echo esc_html(ucfirst($payment_method)); ?></td> <!-- Display Payment Method -->
                            <td><?php echo esc_html($transaction_id); ?></td> <!-- Display Transaction ID -->
                            <td><?php echo esc_html($phone_number); ?></td> <!-- Display Phone Number -->
                            <td><b><?php echo wc_price($amount); ?></b></td> <!-- Display Amount -->
                            <td><?php echo esc_html($order->get_status()); ?></td>
                            <td><a href="<?php echo esc_url($view_order_url); ?>" class="button"><?php esc_html_e('View Order', 'bangladeshi-payments-mobile'); ?></a></td> <!-- View Order Link -->
                        </tr>
                        <?php
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
}
