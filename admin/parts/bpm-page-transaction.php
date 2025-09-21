<?php
function bangladeshi_payments_render_transaction_info_page() {

    // Enqueue DataTables
    add_action('admin_footer', function() {
        ?>
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script>
            jQuery(document).ready(function($){
                $('.bpm-transactions-table').DataTable({
                    "order": [], // Disable initial sorting
                    "pageLength": 25,
                    "columnDefs": [
                        { "orderable": false, "targets": 7 } // Action column not sortable
                    ]
                });
            });
        </script>
        <?php
    });

    // Check nonce
    if (isset($_GET['bangladeshi_payments_nonce'])) {
        $nonce = sanitize_text_field(wp_unslash($_GET['bangladeshi_payments_nonce']));
        if (!wp_verify_nonce($nonce, 'bangladeshi_payments_report')) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Security check failed. Please try again.', 'bangladeshi-payments-mobile') . '</p></div>';
            return;
        }
    }

    // Get filters
    $selected_payment_method = isset($_GET['payment_method']) ? sanitize_text_field(wp_unslash($_GET['payment_method'])) : '';
    $start_date = isset($_GET['start_date']) ? sanitize_text_field(wp_unslash($_GET['start_date'])) : '';
    $end_date = isset($_GET['end_date']) ? sanitize_text_field(wp_unslash($_GET['end_date'])) : '';

    $payment_methods = ['all'=>'All','bkash'=>'Bkash','rocket'=>'Rocket','nagad'=>'Nagad','upay'=>'Upay'];
    ?>
    <div class="wrap bpm-wrap">
        <h4><?php esc_html_e('Transaction Information', 'bangladeshi-payments-mobile'); ?></h4>
        <br>
        <form method="GET">
            <?php wp_nonce_field('bangladeshi_payments_report','bangladeshi_payments_nonce'); ?>
            <input type="hidden" name="page" value="<?php echo esc_attr(sanitize_key($_GET['page'])); ?>">

            <label for="payment_method"><?php esc_html_e('Payment Method:', 'bangladeshi-payments-mobile'); ?></label>
            <select name="payment_method" id="payment_method">
                <?php foreach ($payment_methods as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($selected_payment_method, $value); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            &nbsp;<label for="start_date"><?php esc_html_e('Start Date:', 'bangladeshi-payments-mobile'); ?></label>
            <input type="date" name="start_date" id="start_date" value="<?php echo esc_attr($start_date ? gmdate('Y-m-d', strtotime($start_date)) : ''); ?>">

            &nbsp;<label for="end_date"><?php esc_html_e('End Date:', 'bangladeshi-payments-mobile'); ?></label>
            <input type="date" name="end_date" id="end_date" value="<?php echo esc_attr($end_date ? gmdate('Y-m-d', strtotime($end_date)) : ''); ?>">

            &nbsp;<input type="submit" class="button" value="<?php esc_attr_e('Generate Report', 'bangladeshi-payments-mobile'); ?>">
        </form>
        <br>

        <?php
        // Always fetch all orders
        $args = ['limit'=>-1,'status'=>'completed'];
        $orders = wc_get_orders($args);

        // Filter orders if a payment method or date range is selected
        $filtered_orders = [];
        $total_amount = 0;
        foreach ($orders as $order) {
            $order_date = $order->get_date_created()->date('Y-m-d');
            if (
                ($selected_payment_method === '' || $selected_payment_method==='all' || $order->get_payment_method()===$selected_payment_method) &&
                ($start_date === '' || $order_date >= $start_date) &&
                ($end_date === '' || $order_date <= $end_date)
            ) {
                $filtered_orders[] = $order;
                $total_amount += $order->get_total();
            }
        }
        ?>

        <table class="wp-list-table widefat fixed striped bpm-transactions-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Order ID','bangladeshi-payments-mobile'); ?></th>
                    <th><?php esc_html_e('Payment Method','bangladeshi-payments-mobile'); ?></th>
                    <th><?php esc_html_e('Transaction ID','bangladeshi-payments-mobile'); ?></th>
                    <th><?php esc_html_e('Phone Number','bangladeshi-payments-mobile'); ?></th>
                    <th><?php esc_html_e('Amount','bangladeshi-payments-mobile'); ?></th>
                    <th><?php esc_html_e('Date','bangladeshi-payments-mobile'); ?></th>
                    <th><?php esc_html_e('Order Status','bangladeshi-payments-mobile'); ?></th>
                    <th><?php esc_html_e('Action','bangladeshi-payments-mobile'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($filtered_orders as $order):
                    $payment_method = $order->get_payment_method();
                    $transaction_id = get_post_meta($order->get_id(),'_'.$payment_method.'_transaction_id',true);
                    $phone_number = get_post_meta($order->get_id(),'_'.$payment_method.'_phone',true);
                    ?>
                    <tr>
                        <td><?php echo esc_html($order->get_id()); ?></td>
                        <td><?php echo esc_html(ucwords($payment_method)); ?></td>
                        <td><?php echo esc_html($transaction_id?:'N/A'); ?></td>
                        <td><?php echo esc_html($phone_number?:'N/A'); ?></td>
                        <td><?php echo wp_kses_post(wc_price($order->get_total())); ?></td>
                        <td><?php echo esc_html($order->get_date_created()->date('d F Y')); ?></td>
                        <td><?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></td>
                        <td><a href="<?php echo esc_url($order->get_edit_order_url()); ?>" class="button button-primary"><?php esc_html_e('Edit','bangladeshi-payments-mobile'); ?></a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if($total_amount>0): ?>
            <h2><?php esc_html_e('Total Amount: ','bangladeshi-payments-mobile'); echo wp_kses_post(wc_price($total_amount)); ?></h2>
        <?php endif; ?>
    </div>
<?php
}
