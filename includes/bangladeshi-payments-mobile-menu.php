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
add_action('admin_menu', 'bangladeshi_payments_add_admin_menu');

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
    <div class="wrap">
        <h1><?php esc_html_e('Transaction Information', 'bangladeshi-payments-mobile'); ?></h1>
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

        // Display the filtered orders in the table
        foreach ($filtered_orders as $order) {
            $amount = $order->get_total();
            $payment_method = $order->get_payment_method();

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
            echo '<td>' . esc_html($phone_number ?: 'N/A') . '</td>'; // Phone number display
            echo '<td>' . wp_kses_post(wc_price($amount)) . '</td>';
            echo '<td>' . esc_html($order->get_date_created()->date('d F Y')) . '</td>'; // Updated date format
            echo '<td><a href="' . esc_url($order->get_edit_order_url()) . '" class="button button-primary">' . esc_html__('Edit Order', 'bangladeshi-payments-mobile') . '</a></td>';

            // Accumulate total amount for displaying later
            $total_amount += $amount;
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
