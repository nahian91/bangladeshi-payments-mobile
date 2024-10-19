<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_upay extends WC_Payment_Gateway {

    public function __construct() {
        $this->id = 'upay'; 
        $this->icon = ''; 
        $this->has_fields = true; 
        $this->method_title = __('Upay Payment', 'bangladeshi-payments-mobile');
        $this->method_description = __('Pay via upay by entering your phone number and transaction ID.', 'bangladeshi-payments-mobile');
        
        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->account_type = $this->get_option('account_type'); 
        $this->account_number = $this->get_option('account_number'); 
        $this->upay_charge = $this->get_option('upay_charge'); 

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    // Admin settings fields
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'bangladeshi-payments-mobile'),
                'type' => 'checkbox',
                'label' => __('Enable Upay Payment', 'bangladeshi-payments-mobile'),
                'default' => 'yes',
            ),
            'title' => array(
                'title' => __('Title', 'bangladeshi-payments-mobile'),
                'type' => 'text',
                'description' => __('This controls the title the user sees during checkout.', 'bangladeshi-payments-mobile'),
                'default' => __('Upay Payment', 'bangladeshi-payments-mobile'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'bangladeshi-payments-mobile'),
                'type' => 'textarea',
                'description' => __('Payment method description that the customer will see during checkout.', 'bangladeshi-payments-mobile'),
                'default' => __('Pay with upay. Enter your upay phone number and transaction ID.', 'bangladeshi-payments-mobile'),
            ),
            'account_type' => array(
                'title' => __('Account Type', 'bangladeshi-payments-mobile'),
                'type' => 'select',
                'options' => array(
                    'personal' => __('Personal', 'bangladeshi-payments-mobile'),
                    'agent' => __('Agent', 'bangladeshi-payments-mobile'),
                ),
                'description' => __('Select the type of account used for upay transactions.', 'bangladeshi-payments-mobile'),
                'default' => 'personal',
            ),
            'account_number' => array(
                'title' => __('Account Number', 'bangladeshi-payments-mobile'),
                'type' => 'text',
                'description' => __('Enter the account number for upay transactions.', 'bangladeshi-payments-mobile'),
                'default' => '',
            ),
            'upay_charge' => array(
                'title' => __('Upay Charge (%)', 'bangladeshi-payments-mobile'),
                'type' => 'number',
                'description' => __('Enter the upay charge as a percentage (e.g., 1.4 for 1.4%).', 'bangladeshi-payments-mobile'),
                'default' => '1.4',
                'custom_attributes' => array(
                    'step' => '0.01',
                ),
            ),
        );
    }

    // Payment fields
    public function payment_fields() {
        echo '<p>' . sprintf(esc_html__('You need to send us %1$s (Fees %2$s)', 'bangladeshi-payments-mobile'), esc_html($this->calculate_total_payment()), esc_html($this->calculate_upay_fees())) . '</p>';
        echo '<p>' . esc_html($this->description) . '</p>';
        
        echo '<div>
                <label for="upay_phone">' . esc_html__('upay Phone Number', 'bangladeshi-payments-mobile') . ' <span class="required">*</span></label>
                <input type="text" name="upay_phone" id="upay_phone" placeholder="' . esc_attr__('01XXXXXXXXX', 'bangladeshi-payments-mobile') . '" required>
              </div>';
        echo '<div>
                <label for="upay_transaction_id">' . esc_html__('Upay Transaction ID', 'bangladeshi-payments-mobile') . ' <span class="required">*</span></label>
                <input type="text" name="upay_transaction_id" id="upay_transaction_id" placeholder="' . esc_attr__('Transaction ID', 'bangladeshi-payments-mobile') . '" required>
              </div>';
        echo '<input type="hidden" name="upay_nonce" value="' . esc_attr(wp_create_nonce('upay_payment_nonce')) . '">';
    }

    // Calculate total payment based on order total and upay charge
    private function calculate_total_payment() {
        global $woocommerce;
        $order_total = $woocommerce->cart->total; 
        $upay_charge_percentage = $this->get_option('upay_charge');
        $upay_fee = ($order_total * ($upay_charge_percentage / 100));
        $total_payment = $order_total + $upay_fee;
        return number_format($total_payment, 2) . ' BDT';
    }

    // Calculate upay fees
    private function calculate_upay_fees() {
        global $woocommerce;
        $order_total = $woocommerce->cart->total; 
        $upay_charge_percentage = $this->get_option('upay_charge');
        $upay_fee = ($order_total * ($upay_charge_percentage / 100));
        return number_format($upay_fee, 2) . ' BDT';
    }

      


    // Display upay information on the order page
    public function display_upay_info_on_order($order_id) {
        $upay_phone = get_post_meta($order_id, '_upay_phone', true);
        $upay_transaction_id = get_post_meta($order_id, '_upay_transaction_id', true);
        
        if ($upay_phone || $upay_transaction_id) {
            echo '<h3>' . esc_html('Upay Payment Information', 'bangladeshi-payments-mobile') . '</h3>';
            echo '<p><strong>' . esc_html('Phone Number:', 'bangladeshi-payments-mobile') . '</strong> ' . esc_html($upay_phone) . '</p>';
            echo '<p><strong>' . esc_html('Transaction ID:', 'bangladeshi-payments-mobile') . '</strong> ' . esc_html($upay_transaction_id) . '</p>';
        }
    }
}

// Add the gateway to WooCommerce
add_filter('woocommerce_payment_gateways', 'add_upay_gateway');
function add_upay_gateway($methods) {
    $methods[] = 'WC_Gateway_upay';
    return $methods;
}

// Display upay information under Billing column on the order page
add_action('woocommerce_admin_order_data_after_billing_address', 'display_upay_info_admin_order', 10, 1);
function display_upay_info_admin_order($order) {
    $upay_phone = get_post_meta($order->get_id(), '_upay_phone', true);
    $upay_transaction_id = get_post_meta($order->get_id(), '_upay_transaction_id', true);
    
    if ($upay_phone || $upay_transaction_id) {
        ?>
            <div class="paymen-order-page">
                <?php 
                    echo '<h3>' . esc_html('Upay Payment Information', 'bangladeshi-payments-mobile') . '</h3>';
                    echo '<p><strong>' . esc_html('Phone Number:', 'bangladeshi-payments-mobile') . '</strong> ' . esc_html($upay_phone) . '</p>';
                    echo '<p><strong>' . esc_html('Transaction ID:', 'bangladeshi-payments-mobile') . '</strong> ' . esc_html($upay_transaction_id) . '</p>';
                ?>
            </div>
        <?php 
    }
}
