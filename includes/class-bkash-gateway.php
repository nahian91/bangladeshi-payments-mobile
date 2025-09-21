<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_bKash extends WC_Payment_Gateway {

    public $account_type;
    public $account_number;
    public $bkash_charge;
    public $apply_bkash_charge;
    public $enable_qr;

    public function __construct() {
        $this->id                 = 'bkash';
        $this->has_fields         = true;
        $this->method_title       = __('bKash Payment', 'bangladeshi-payments-mobile');
        $this->method_description = __('Pay via bKash by entering your phone number and transaction ID.', 'bangladeshi-payments-mobile');

        // Load settings
        $this->init_form_fields();
        $this->init_settings();

        $this->title              = $this->get_option('title');
        $this->description        = $this->get_option('description');
        $this->icon               = plugins_url('img/bkash.png', __FILE__);
        $this->account_type       = $this->get_option('account_type');
        $this->account_number     = $this->get_option('account_number');
        $this->bkash_charge       = $this->get_option('bkash_charge');
        $this->apply_bkash_charge = $this->get_option('apply_bkash_charge') === 'yes';
        $this->enable_qr          = $this->get_option('enable_qr') === 'yes';

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => __('Enable/Disable', 'bangladeshi-payments-mobile'),
                'type'    => 'checkbox',
                'label'   => __('Enable bKash Payment', 'bangladeshi-payments-mobile'),
                'default' => 'no',
            ],
            'title' => [
                'title'       => __('Title', 'bangladeshi-payments-mobile'),
                'type'        => 'text',
                'description' => __('This controls the title the user sees during checkout.', 'bangladeshi-payments-mobile'),
                'default'     => __('bKash', 'bangladeshi-payments-mobile'),
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => __('Description', 'bangladeshi-payments-mobile'),
                'type'        => 'textarea',
                'description' => __('Payment method description that the customer will see during checkout.', 'bangladeshi-payments-mobile'),
                'default'     => __('Pay with bKash. Enter your bKash phone number and transaction ID.', 'bangladeshi-payments-mobile'),
            ],
            'account_type' => [
                'title'       => __('Account Type', 'bangladeshi-payments-mobile'),
                'type'        => 'select',
                'options'     => [
                    'personal' => __('Personal', 'bangladeshi-payments-mobile'),
                    'agent'    => __('Agent', 'bangladeshi-payments-mobile'),
                ],
                'default'     => 'personal',
            ],
            'account_number' => [
                'title'    => __('Account Number', 'bangladeshi-payments-mobile'),
                'type'     => 'text',
                'default'  => '',
                'required' => true,
            ],
            'apply_bkash_charge' => [
                'title'   => __('Apply bKash Charge', 'bangladeshi-payments-mobile'),
                'type'    => 'checkbox',
                'label'   => __('Apply bKash charge to total payment?', 'bangladeshi-payments-mobile'),
                'default' => 'yes',
            ],
            'bkash_charge' => [
                'title'             => __('bKash Charge (%)', 'bangladeshi-payments-mobile'),
                'type'              => 'number',
                'default'           => '1.4',
                'custom_attributes' => ['step' => '0.01'],
            ],
            'enable_qr' => [
                'title'   => __('Enable QR Code', 'bangladeshi-payments-mobile'),
                'type'    => 'checkbox',
                'label'   => __('Show QR code for bKash payment', 'bangladeshi-payments-mobile'),
                'default' => 'yes',
            ],
        ];
    }

    public function payment_fields() {
    ?>
        <div class="payment-fields-box">
            <div class="payment-fields-box-info">
                <?php 
                    printf(
                        '<p>%s</p>',
                        wp_kses_post(
                            sprintf(
                                __('You need to send us <strong>%1$s</strong> (Fees %2$s)', 'bangladeshi-payments-mobile'),
                                $this->calculate_total_payment(),
                                $this->calculate_bkash_fees()
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
                <label for="bkash_phone"><?php esc_html_e('bKash Phone Number', 'bangladeshi-payments-mobile'); ?> <span class="required">*</span></label>
                <input type="text" name="bkash_phone" id="bkash_phone" placeholder="<?php esc_attr_e('01XXXXXXXXX', 'bangladeshi-payments-mobile'); ?>" required>
            </div>

            <div class="payment-fields-box-trans">
                <label for="bkash_transaction_id"><?php esc_html_e('bKash Transaction ID', 'bangladeshi-payments-mobile'); ?> <span class="required">*</span></label>
                <input type="text" name="bkash_transaction_id" id="bkash_transaction_id" placeholder="<?php esc_attr_e('Transaction ID', 'bangladeshi-payments-mobile'); ?>" required>
            </div>

            <input type="hidden" name="bkash_nonce" value="<?php echo esc_attr(wp_create_nonce('bkash_payment_nonce')); ?>">

            <?php if ($this->enable_qr): ?>
                <div class="payment-fields-box-qr">
                    <img src="<?php echo esc_url('https://api.qrserver.com/v1/create-qr-code/?data=' . urlencode($this->account_number) . '&size=80x80'); ?>" alt="bKash QR Code">
                </div>
            <?php endif; ?>
        </div>
    <?php
    }

    private function calculate_total_payment() {
        $total = WC()->cart->total;
        $fee   = $this->apply_bkash_charge ? ($total * ($this->bkash_charge / 100)) : 0;
        return number_format($total + $fee, 2) . ' BDT';
    }

    private function calculate_bkash_fees() {
        $total = WC()->cart->total;
        $fee   = $this->apply_bkash_charge ? ($total * ($this->bkash_charge / 100)) : 0;
        return number_format($fee, 2) . ' BDT';
    }

    public function validate_fields() {
        if (!isset($_POST['bkash_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bkash_nonce'])), 'bkash_payment_nonce')) {
            wc_add_notice(__('Nonce verification failed.', 'bangladeshi-payments-mobile'), 'error');
            return false;
        }

        $bkash_phone = isset($_POST['bkash_phone']) ? sanitize_text_field(wp_unslash($_POST['bkash_phone'])) : '';
        $bkash_trx   = isset($_POST['bkash_transaction_id']) ? sanitize_text_field(wp_unslash($_POST['bkash_transaction_id'])) : '';

        if (empty($bkash_phone) || !preg_match('/^01[0-9]{9}$/', $bkash_phone)) {
            wc_add_notice(__('Please enter a valid bKash phone number.', 'bangladeshi-payments-mobile'), 'error');
            return false;
        }

        if (empty($bkash_trx)) {
            wc_add_notice(__('bKash transaction ID is required.', 'bangladeshi-payments-mobile'), 'error');
            return false;
        }

        return true;
    }

    public function process_payment($order_id) {
    $order = wc_get_order($order_id);

    // Verify nonce before processing POST data
    if ( ! isset( $_POST['bkash_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bkash_nonce'] ) ), 'bkash_payment_nonce' ) ) {
        wc_add_notice( __( 'Nonce verification failed.', 'bangladeshi-payments-mobile' ), 'error' );
        return [
            'result'   => 'fail',
            'redirect' => '',
        ];
    }

    $bkash_phone = isset($_POST['bkash_phone']) ? sanitize_text_field(wp_unslash($_POST['bkash_phone'])) : '';
    $bkash_trx   = isset($_POST['bkash_transaction_id']) ? sanitize_text_field(wp_unslash($_POST['bkash_transaction_id'])) : '';

    if ($bkash_phone) update_post_meta($order_id, '_bkash_phone', $bkash_phone);
    if ($bkash_trx) update_post_meta($order_id, '_bkash_transaction_id', $bkash_trx);

    // translators: Message shown to the user after order is placed, waiting for bKash confirmation.
    $order->update_status('on-hold', __('Waiting for bKash payment confirmation.', 'bangladeshi-payments-mobile'));

    wc_reduce_stock_levels($order_id);
    WC()->cart->empty_cart();

    return [
        'result'   => 'success',
        'redirect' => $this->get_return_url($order),
    ];
}

}

// Admin display of bKash info
add_action('woocommerce_admin_order_data_after_billing_address', function($order) {
    $bkash_phone = get_post_meta($order->get_id(), '_bkash_phone', true);
    $bkash_trx   = get_post_meta($order->get_id(), '_bkash_transaction_id', true);

    if ($bkash_phone || $bkash_trx):
    ?>
        <div class="payment-order-page">
            <table>
                <tr><td colspan="2"><h4><?php esc_html_e('Payment Information', 'bangladeshi-payments-mobile'); ?></h4></td></tr>
                <tr><td><?php esc_html_e('Payment Method:', 'bangladeshi-payments-mobile'); ?></td><td><?php esc_html_e('bKash', 'bangladeshi-payments-mobile'); ?></td></tr>
                <tr><td><?php esc_html_e('Phone Number:', 'bangladeshi-payments-mobile'); ?></td><td><?php echo esc_html($bkash_phone); ?></td></tr>
                <tr><td><?php esc_html_e('Transaction ID:', 'bangladeshi-payments-mobile'); ?></td><td><?php echo esc_html($bkash_trx); ?></td></tr>
            </table>
        </div>
    <?php
    endif;
});
