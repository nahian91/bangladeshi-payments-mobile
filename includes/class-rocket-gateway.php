<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Rocket extends WC_Payment_Gateway {

    public $account_type;
    public $account_number;
    public $rocket_charge;
    public $apply_rocket_charge;
    public $enable_qr;

    public function __construct() {
        $this->id                 = 'rocket';
        $this->icon               = plugins_url('img/rocket.png', __FILE__);
        $this->has_fields         = true;
        $this->method_title       = __('Rocket Payment', 'bangladeshi-payments-mobile');
        $this->method_description = __('Pay via Rocket by entering your phone number and transaction ID.', 'bangladeshi-payments-mobile');

        $this->init_form_fields();
        $this->init_settings();

        $this->title               = $this->get_option('title');
        $this->description         = $this->get_option('description');
        $this->account_type        = $this->get_option('account_type');
        $this->account_number      = $this->get_option('account_number');
        $this->rocket_charge       = $this->get_option('rocket_charge');
        $this->apply_rocket_charge = $this->get_option('apply_rocket_charge') === 'yes';
        $this->enable_qr           = $this->get_option('enable_qr') === 'yes';

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'display_admin_order_info'], 10, 1);
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => __('Enable/Disable', 'bangladeshi-payments-mobile'),
                'type'    => 'checkbox',
                'label'   => __('Enable Rocket Payment', 'bangladeshi-payments-mobile'),
                'default' => 'no',
            ],
            'title' => [
                'title'   => __('Title', 'bangladeshi-payments-mobile'),
                'type'    => 'text',
                'default' => __('Rocket', 'bangladeshi-payments-mobile'),
            ],
            'description' => [
                'title'   => __('Description', 'bangladeshi-payments-mobile'),
                'type'    => 'textarea',
                'default' => __('Pay with Rocket. Enter your Rocket phone number and transaction ID.', 'bangladeshi-payments-mobile'),
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
            'apply_rocket_charge' => [
                'title'   => __('Apply Rocket Charge', 'bangladeshi-payments-mobile'),
                'type'    => 'checkbox',
                'label'   => __('Apply Rocket charge to total payment?', 'bangladeshi-payments-mobile'),
                'default' => 'yes',
            ],
            'rocket_charge' => [
                'title'             => __('Rocket Charge (%)', 'bangladeshi-payments-mobile'),
                'type'              => 'number',
                'default'           => '1.4',
                'custom_attributes' => ['step' => '0.01'],
            ],
            'enable_qr' => [
                'title'   => __('Enable QR Code', 'bangladeshi-payments-mobile'),
                'type'    => 'checkbox',
                'label'   => __('Show QR code for Rocket payment', 'bangladeshi-payments-mobile'),
                'default' => 'yes',
            ],
        ];
    }

   public function payment_fields() {
    ?>
    <div class="payment-fields-box">
        <div class="payment-fields-box-info">
            <?php 
            // translators: %1$s is total amount, %2$s is Rocket fee
            printf(
                '<p>%s</p>',
                wp_kses_post(
                    sprintf(
                        __('You need to send us <strong>%1$s</strong> (Fees %2$s)', 'bangladeshi-payments-mobile'),
                        $this->calculate_total_payment(),
                        $this->calculate_rocket_fees()
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
            <label for="rocket_phone"><?php esc_html_e('Rocket Phone Number', 'bangladeshi-payments-mobile'); ?> <span class="required">*</span></label>
            <input type="text" name="rocket_phone" id="rocket_phone" placeholder="<?php esc_attr_e('01XXXXXXXXX', 'bangladeshi-payments-mobile'); ?>" required>
        </div>

        <div class="payment-fields-box-trans">
            <label for="rocket_transaction_id"><?php esc_html_e('Rocket Transaction ID', 'bangladeshi-payments-mobile'); ?> <span class="required">*</span></label>
            <input type="text" name="rocket_transaction_id" id="rocket_transaction_id" placeholder="<?php esc_attr_e('Transaction ID', 'bangladeshi-payments-mobile'); ?>" required>
        </div>

        <input type="hidden" name="rocket_nonce" value="<?php echo esc_attr(wp_create_nonce('rocket_payment_nonce')); ?>">

        <?php if ($this->enable_qr): ?>
            <div class="payment-fields-box-qr">
                <img src="<?php echo esc_url('https://api.qrserver.com/v1/create-qr-code/?data=' . urlencode($this->account_number) . '&size=80x80'); ?>" alt="Rocket QR Code">
            </div>
        <?php endif; ?>
    </div>
    <?php
}


    private function calculate_total_payment() {
        $total = WC()->cart->total;
        $fee   = $this->apply_rocket_charge ? ($total * ($this->rocket_charge / 100)) : 0;
        return number_format($total + $fee, 2) . ' BDT';
    }

    private function calculate_rocket_fees() {
        $total = WC()->cart->total;
        $fee   = $this->apply_rocket_charge ? ($total * ($this->rocket_charge / 100)) : 0;
        return number_format($fee, 2) . ' BDT';
    }

    public function validate_fields() {
        if (!isset($_POST['rocket_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rocket_nonce'])), 'rocket_payment_nonce')) {
            wc_add_notice(__('Nonce verification failed.', 'bangladeshi-payments-mobile'), 'error');
            return false;
        }

        if (empty($_POST['rocket_phone']) || !preg_match('/^01[0-9]{9}$/', sanitize_text_field(wp_unslash($_POST['rocket_phone'])))) {
            wc_add_notice(__('Please enter a valid Rocket phone number.', 'bangladeshi-payments-mobile'), 'error');
            return false;
        }

        if (empty($_POST['rocket_transaction_id'])) {
            wc_add_notice(__('Rocket transaction ID is required.', 'bangladeshi-payments-mobile'), 'error');
            return false;
        }

        return true;
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        update_post_meta($order_id, '_rocket_phone', sanitize_text_field(wp_unslash($_POST['rocket_phone'])));
        update_post_meta($order_id, '_rocket_transaction_id', sanitize_text_field(wp_unslash($_POST['rocket_transaction_id'])));

        $order->update_status('on-hold', __('Waiting for Rocket payment confirmation.', 'bangladeshi-payments-mobile'));
        wc_reduce_stock_levels($order_id);
        WC()->cart->empty_cart();

        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }

    public function display_admin_order_info($order) {
        static $displayed = false; // prevent duplicate display
        if ($displayed) return;
        $displayed = true;

        $phone = get_post_meta($order->get_id(), '_rocket_phone', true);
        $trx   = get_post_meta($order->get_id(), '_rocket_transaction_id', true);

        if ($phone || $trx) :
        ?>
            <div class="payment-order-page">
                <table>
                    <tr>
                        <td colspan="2"><h4><?php esc_html_e('Rocket Payment Information', 'bangladeshi-payments-mobile'); ?></h4></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Payment Method:', 'bangladeshi-payments-mobile'); ?></td>
                        <td><?php esc_html_e('Rocket', 'bangladeshi-payments-mobile'); ?></td>
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

// Register Rocket gateway
add_filter('woocommerce_payment_gateways', function($methods) {
    $methods[] = 'WC_Gateway_Rocket';
    return $methods;
});
