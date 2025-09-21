<?php
if (!defined('ABSPATH')) exit;

class WC_Gateway_Upay extends WC_Payment_Gateway {

    public $account_type;
    public $account_number;
    public $upay_charge;
    public $apply_upay_charge;
    public $enable_qr;

    public function __construct() {
        $this->id                 = 'upay';
        $this->icon               = '';
        $this->has_fields         = true;
        $this->method_title       = __('Upay Payment', 'bangladeshi-payments-mobile');
        $this->method_description = __('Pay via Upay by entering your phone number and transaction ID.', 'bangladeshi-payments-mobile');

        $this->init_form_fields();
        $this->init_settings();

        $this->title               = $this->get_option('title');
        $this->description         = $this->get_option('description');
        $this->icon                = plugins_url('img/upay.png', __FILE__);
        $this->account_type        = $this->get_option('account_type');
        $this->account_number      = $this->get_option('account_number');
        $this->upay_charge         = $this->get_option('upay_charge');
        $this->apply_upay_charge   = $this->get_option('apply_upay_charge') === 'yes';
        $this->enable_qr           = $this->get_option('enable_qr') === 'yes';

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
    }

    // Admin toggle switch CSS
    public function admin_enqueue_scripts($hook) {
        if ('woocommerce_page_wc-settings' !== $hook) return;
        wp_add_inline_style('woocommerce_admin_styles', "
            .bpm-toggle-switch-wrapper { display: flex; align-items: center; }
            .bpm-toggle-switch { position: relative; display: inline-block; width: 50px; height: 24px; margin-right: 10px; }
            .bpm-toggle-switch input { opacity: 0; width: 0; height: 0; }
            .bpm-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px; }
            .bpm-slider:before { position: absolute; content: ''; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
            .bpm-toggle-switch input:checked + .bpm-slider { background-color: #0073aa; }
            .bpm-toggle-switch input:checked + .bpm-slider:before { transform: translateX(26px); }
        ");
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => __('Enable/Disable', 'bangladeshi-payments-mobile'),
                'type'    => 'checkbox',
                'label'   => __('Enable Upay Payment', 'bangladeshi-payments-mobile'),
                'default' => 'no',
                'desc_tip'=> false,
                'custom_attributes' => ['class' => 'bpm-toggle-switch-checkbox']
            ],
            'title' => [
                'title'       => __('Title', 'bangladeshi-payments-mobile'),
                'type'        => 'text',
                'default'     => __('Upay', 'bangladeshi-payments-mobile'),
                'desc_tip'    => true,
            ],
            'description' => [
                'title'   => __('Description', 'bangladeshi-payments-mobile'),
                'type'    => 'textarea',
                'default' => __('Pay with Upay. Enter your Upay phone number and transaction ID.', 'bangladeshi-payments-mobile'),
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
            'apply_upay_charge' => [
                'title'   => __('Apply Upay Charge', 'bangladeshi-payments-mobile'),
                'type'    => 'checkbox',
                'label'   => __('Apply Upay charge to total payment?', 'bangladeshi-payments-mobile'),
                'default' => 'yes',
            ],
            'upay_charge' => [
                'title'             => __('Upay Charge (%)', 'bangladeshi-payments-mobile'),
                'type'              => 'number',
                'default'           => '1.4',
                'custom_attributes' => ['step' => '0.01'],
            ],
            'enable_qr' => [
                'title'   => __('Enable QR Code', 'bangladeshi-payments-mobile'),
                'type'    => 'checkbox',
                'label'   => __('Show QR code for Upay payment', 'bangladeshi-payments-mobile'),
                'default' => 'yes',
            ],
        ];
    }

    public function payment_fields() {
    ?>
    <div class="payment-fields-box">
        <div class="payment-fields-box-info">
            <?php 
            // translators: %1$s is total amount, %2$s is Upay fee
            printf(
                '<p>%s</p>',
                wp_kses_post(
                    sprintf(
                        __('You need to send us <strong>%1$s</strong> (Fees %2$s)', 'bangladeshi-payments-mobile'),
                        $this->calculate_total_payment(),
                        $this->calculate_upay_fees()
                    )
                )
            );
            ?>
        </div>

        <div class="payment-fields-box-desc">
            <?php echo esc_html($this->description); ?>
        </div>

        <ul>
                <li><?php esc_html_e('Account Type:', 'bangladeshi-payments-mobile'); ?> <span><?php echo esc_html(ucfirst($this->account_type)); ?></span></li>
                <li><?php esc_html_e('Account Number:', 'bangladeshi-payments-mobile'); ?> <span><?php echo esc_html($this->account_number); ?></span></li>
            </ul>

        <div class="payment-fields-box-phone">
            <label for="upay_phone"><?php esc_html_e('Upay Phone Number', 'bangladeshi-payments-mobile'); ?> <span class="required">*</span></label>
            <input type="text" name="upay_phone" id="upay_phone" placeholder="<?php esc_attr_e('01XXXXXXXXX', 'bangladeshi-payments-mobile'); ?>" required>
        </div>

        <div class="payment-fields-box-trans">
            <label for="upay_transaction_id"><?php esc_html_e('Upay Transaction ID', 'bangladeshi-payments-mobile'); ?> <span class="required">*</span></label>
            <input type="text" name="upay_transaction_id" id="upay_transaction_id" placeholder="<?php esc_attr_e('Transaction ID', 'bangladeshi-payments-mobile'); ?>" required>
        </div>

        <input type="hidden" name="upay_nonce" value="<?php echo esc_attr(wp_create_nonce('upay_payment_nonce')); ?>">

        <?php if ($this->enable_qr): ?>
            <div class="payment-fields-box-qr">
                <img src="<?php echo esc_url('https://api.qrserver.com/v1/create-qr-code/?data=' . urlencode($this->account_number) . '&size=80x80'); ?>" alt="Upay QR Code">
            </div>
        <?php endif; ?>
    </div>
    <?php
}


    private function calculate_total_payment() {
        $total = WC()->cart->total;
        $fee   = $this->apply_upay_charge ? ($total * ($this->upay_charge / 100)) : 0;
        return number_format($total + $fee, 2) . ' BDT';
    }

    private function calculate_upay_fees() {
        $total = WC()->cart->total;
        $fee   = $this->apply_upay_charge ? ($total * ($this->upay_charge / 100)) : 0;
        return number_format($fee, 2) . ' BDT';
    }

    public function validate_fields() {
        if (!isset($_POST['upay_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['upay_nonce'])), 'upay_payment_nonce')) {
            wc_add_notice(__('Nonce verification failed.', 'bangladeshi-payments-mobile'), 'error');
            return false;
        }

        if (empty($_POST['upay_phone']) || !preg_match('/^01[0-9]{9}$/', sanitize_text_field(wp_unslash($_POST['upay_phone'])))) {
            wc_add_notice(__('Please enter a valid Upay phone number.', 'bangladeshi-payments-mobile'), 'error');
            return false;
        }

        if (empty($_POST['upay_transaction_id'])) {
            wc_add_notice(__('Upay transaction ID is required.', 'bangladeshi-payments-mobile'), 'error');
            return false;
        }

        return true;
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        update_post_meta($order_id, '_upay_phone', sanitize_text_field(wp_unslash($_POST['upay_phone'])));
        update_post_meta($order_id, '_upay_transaction_id', sanitize_text_field(wp_unslash($_POST['upay_transaction_id'])));

        $order->update_status('on-hold', __('Waiting for Upay payment confirmation.', 'bangladeshi-payments-mobile'));
        wc_reduce_stock_levels($order_id);
        WC()->cart->empty_cart();

        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }
}

// Add Upay gateway
add_filter('woocommerce_payment_gateways', function($methods) {
    $methods[] = 'WC_Gateway_Upay';
    return $methods;
});

// Show info in admin order
add_action('woocommerce_admin_order_data_after_billing_address', function($order) {
    $phone = get_post_meta($order->get_id(), '_upay_phone', true);
    $trx   = get_post_meta($order->get_id(), '_upay_transaction_id', true);

    if ($phone || $trx):
    ?>
    <div class="payment-order-page">
        <table>
            <tr>
                <td colspan="2">
                    <h4><?php echo esc_html__('Upay Payment Information', 'bangladeshi-payments-mobile'); ?></h4>
                </td>
            </tr>
            <tr>
                <td><?php echo esc_html__('Payment Method:', 'bangladeshi-payments-mobile'); ?></td>
                <td><?php echo esc_html__('Upay', 'bangladeshi-payments-mobile'); ?></td>
            </tr>
            <tr>
                <td><?php echo esc_html__('Phone Number:', 'bangladeshi-payments-mobile'); ?></td>
                <td><?php echo esc_html($phone); ?></td>
            </tr>
            <tr>
                <td><?php echo esc_html__('Transaction ID:', 'bangladeshi-payments-mobile'); ?></td>
                <td><?php echo esc_html($trx); ?></td>
            </tr>
        </table>
    </div>
    <?php
    endif;
});
