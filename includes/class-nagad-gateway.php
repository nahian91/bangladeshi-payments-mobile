<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Nagad extends WC_Payment_Gateway {

    public $account_type;
    public $account_number;
    public $nagad_charge;
    public $apply_nagad_charge;
    public $enable_qr;

    public function __construct() {
        $this->id                 = 'nagad';
        $this->icon               = plugins_url('img/nagad.png', __FILE__);
        $this->has_fields         = true;
        $this->method_title       = __('Nagad Payment', 'bangladeshi-payments-mobile');
        $this->method_description = __('Pay via Nagad by entering your phone number and transaction ID.', 'bangladeshi-payments-mobile');

        $this->init_form_fields();
        $this->init_settings();

        $this->title              = $this->get_option('title');
        $this->description        = $this->get_option('description');
        $this->account_type       = $this->get_option('account_type');
        $this->account_number     = $this->get_option('account_number');
        $this->nagad_charge       = $this->get_option('nagad_charge');
        $this->apply_nagad_charge = $this->get_option('apply_nagad_charge') === 'yes';
        $this->enable_qr          = $this->get_option('enable_qr') === 'yes';

        // Save settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

        // Show payment info in admin order (once)
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'display_admin_order_info'], 10, 1);
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => __('Enable/Disable', 'bangladeshi-payments-mobile'),
                'type'    => 'checkbox',
                'label'   => __('Enable Nagad Payment', 'bangladeshi-payments-mobile'),
                'default' => 'no',
            ],
            'title' => [
                'title'   => __('Title', 'bangladeshi-payments-mobile'),
                'type'    => 'text',
                'default' => __('Nagad', 'bangladeshi-payments-mobile'),
            ],
            'description' => [
                'title'   => __('Description', 'bangladeshi-payments-mobile'),
                'type'    => 'textarea',
                'default' => __('Pay with Nagad. Enter your Nagad phone number and transaction ID.', 'bangladeshi-payments-mobile'),
            ],
            'account_type' => [
                'title'   => __('Account Type', 'bangladeshi-payments-mobile'),
                'type'    => 'select',
                'options' => ['personal' => __('Personal', 'bangladeshi-payments-mobile'), 'agent' => __('Agent', 'bangladeshi-payments-mobile')],
                'default' => 'personal',
            ],
            'account_number' => [
                'title'    => __('Account Number', 'bangladeshi-payments-mobile'),
                'type'     => 'text',
                'default'  => '',
                'required' => true,
            ],
            'apply_nagad_charge' => [
                'title'   => __('Apply Nagad Charge', 'bangladeshi-payments-mobile'),
                'type'    => 'checkbox',
                'label'   => __('Apply Nagad charge to total payment?', 'bangladeshi-payments-mobile'),
                'default' => 'yes',
            ],
            'nagad_charge' => [
                'title'             => __('Nagad Charge (%)', 'bangladeshi-payments-mobile'),
                'type'              => 'number',
                'default'           => '1.4',
                'custom_attributes' => ['step' => '0.01'],
            ],
            'enable_qr' => [
                'title'   => __('Enable QR Code', 'bangladeshi-payments-mobile'),
                'type'    => 'checkbox',
                'label'   => __('Show QR code for Nagad payment', 'bangladeshi-payments-mobile'),
                'default' => 'yes',
            ],
        ];
    }

    public function payment_fields() {
        ?>
        <p><?php printf(
            esc_html__('You need to send us %1$s (Fees %2$s)', 'bangladeshi-payments-mobile'),
            esc_html($this->calculate_total_payment()),
            esc_html($this->calculate_nagad_fees())
        ); ?></p>

        <p><?php echo esc_html($this->description); ?></p>
        <p><strong><?php esc_html_e('Account Type:', 'bangladeshi-payments-mobile'); ?></strong> <?php echo esc_html(ucfirst($this->account_type)); ?></p>
        <p><strong><?php esc_html_e('Account Number:', 'bangladeshi-payments-mobile'); ?></strong> <?php echo esc_html($this->account_number); ?></p>

        <div>
            <label for="nagad_phone"><?php esc_html_e('Nagad Phone Number', 'bangladeshi-payments-mobile'); ?> <span class="required">*</span></label>
            <input type="text" name="nagad_phone" id="nagad_phone" placeholder="<?php esc_attr_e('01XXXXXXXXX', 'bangladeshi-payments-mobile'); ?>" required>
        </div>

        <div>
            <label for="nagad_transaction_id"><?php esc_html_e('Nagad Transaction ID', 'bangladeshi-payments-mobile'); ?> <span class="required">*</span></label>
            <input type="text" name="nagad_transaction_id" id="nagad_transaction_id" placeholder="<?php esc_attr_e('Transaction ID', 'bangladeshi-payments-mobile'); ?>" required>
        </div>

        <input type="hidden" name="nagad_nonce" value="<?php echo esc_attr(wp_create_nonce('nagad_payment_nonce')); ?>">

        <?php if ($this->enable_qr): ?>
            <p>
                <img src="<?php echo esc_url('https://api.qrserver.com/v1/create-qr-code/?data=' . urlencode($this->account_number) . '&size=80x80'); ?>" alt="Nagad QR Code">
            </p>
        <?php endif; ?>
        <?php
    }

    private function calculate_total_payment() {
        $total = WC()->cart->total;
        $fee   = $this->apply_nagad_charge ? ($total * ($this->nagad_charge / 100)) : 0;
        return number_format($total + $fee, 2) . ' BDT';
    }

    private function calculate_nagad_fees() {
        $total = WC()->cart->total;
        $fee   = $this->apply_nagad_charge ? ($total * ($this->nagad_charge / 100)) : 0;
        return number_format($fee, 2) . ' BDT';
    }

    public function validate_fields() {
        if (!isset($_POST['nagad_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nagad_nonce'])), 'nagad_payment_nonce')) {
            wc_add_notice(__('Nonce verification failed.', 'bangladeshi-payments-mobile'), 'error');
            return false;
        }

        if (empty($_POST['nagad_phone']) || !preg_match('/^01[0-9]{9}$/', sanitize_text_field(wp_unslash($_POST['nagad_phone'])))) {
            wc_add_notice(__('Please enter a valid Nagad phone number.', 'bangladeshi-payments-mobile'), 'error');
            return false;
        }

        if (empty($_POST['nagad_transaction_id'])) {
            wc_add_notice(__('Nagad transaction ID is required.', 'bangladeshi-payments-mobile'), 'error');
            return false;
        }

        return true;
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        update_post_meta($order_id, '_nagad_phone', sanitize_text_field(wp_unslash($_POST['nagad_phone'])));
        update_post_meta($order_id, '_nagad_transaction_id', sanitize_text_field(wp_unslash($_POST['nagad_transaction_id'])));

        $order->update_status('on-hold', __('Waiting for Nagad payment confirmation.', 'bangladeshi-payments-mobile'));
        wc_reduce_stock_levels($order_id);
        WC()->cart->empty_cart();

        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }

    // Display admin info once
    public function display_admin_order_info($order) {
        static $displayed = false; // prevent duplicate display
        if ($displayed) return;
        $displayed = true;

        $phone = get_post_meta($order->get_id(), '_nagad_phone', true);
        $trx   = get_post_meta($order->get_id(), '_nagad_transaction_id', true);

        if ($phone || $trx) :
        ?>
            <div class="payment-order-page">
                <table>
                    <tr>
                        <td colspan="2"><h4><?php esc_html_e('Nagad Payment Information', 'bangladeshi-payments-mobile'); ?></h4></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Payment Method:', 'bangladeshi-payments-mobile'); ?></td>
                        <td><?php esc_html_e('Nagad', 'bangladeshi-payments-mobile'); ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Phone Number:', 'bangladeshi-payments-mobile'); ?></td>
                        <td><?php echo esc_html($phone); ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Transaction ID:', 'bangladeshi-payments-mobile'); ?></td>
                        <td><?php echo esc_html($trx); ?></td>
                    </tr>
                </table>
            </div>
        <?php
        endif;
    }
}

// Register gateway
add_filter('woocommerce_payment_gateways', function($methods) {
    $methods[] = 'WC_Gateway_Nagad';
    return $methods;
});
