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
        $this->method_description = __('Pay via Upay by entering your phone number and transaction ID.', 'bangladeshi-payments-mobile');
        
        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');        
        $this->icon = plugins_url( 'img/upay.png', __FILE__ );
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
            'default' => 'no',
        ),
        'title' => array(
            'title' => __('Title', 'bangladeshi-payments-mobile'),
            'type' => 'text',
            'description' => __('This controls the title the user sees during checkout.', 'bangladeshi-payments-mobile'),
            'default' => __('Upay', 'bangladeshi-payments-mobile'),
            'desc_tip' => true,
        ),
        'description' => array(
            'title' => __('Description', 'bangladeshi-payments-mobile'),
            'type' => 'textarea',
            'description' => __('Payment method description that the customer will see during checkout.', 'bangladeshi-payments-mobile'),
            'default' => __('Pay with Upay. Enter your Upay phone number and transaction ID.', 'bangladeshi-payments-mobile'),
        ),
        'account_type' => array(
            'title' => __('Account Type', 'bangladeshi-payments-mobile'),
            'type' => 'select',
            'options' => array(
                'personal' => __('Personal', 'bangladeshi-payments-mobile'),
                'agent' => __('Agent', 'bangladeshi-payments-mobile'),
            ),
            'description' => __('Select the type of account used for Upay transactions.', 'bangladeshi-payments-mobile'),
            'default' => 'personal',
        ),
        'account_number' => array(
            'title' => __('Account Number', 'bangladeshi-payments-mobile'),
            'type' => 'text',
            'description' => __('Enter the account number for Upay transactions.', 'bangladeshi-payments-mobile'),
            'default' => '',
            'required' => true,
        ),
        'apply_upay_charge' => array(
            'title' => __('Apply Upay Charge', 'bangladeshi-payments-mobile'),
            'type' => 'checkbox',
            'label' => __('Apply Upay charge to total payment?', 'bangladeshi-payments-mobile'),
            'default' => 'yes',
        ),
        'upay_charge' => array(
            'title' => __('Upay Charge (%)', 'bangladeshi-payments-mobile'),
            'type' => 'number',
            'description' => __('Enter the Upay charge as a percentage (e.g., 1.4 for 1.4%).', 'bangladeshi-payments-mobile'),
            'default' => '1.4',
            'custom_attributes' => array(
                'step' => '0.01',
            ),
        ),
    );
}


public function payment_fields() {
    // Translators: %1$s is the total payment amount. %2$s is the Upay fees amount.
    echo '<p>' . sprintf(esc_html__('You need to send us %1$s (Fees %2$s)', 'bangladeshi-payments-mobile'), esc_html($this->calculate_total_payment()), esc_html($this->calculate_upay_fees())) . '</p>';
    echo '<p>' . esc_html($this->description) . '</p>';

    // Show Account Type and Number
    echo '<p><strong>' . esc_html__('Account Type: ', 'bangladeshi-payments-mobile') . '</strong>' . esc_html(ucfirst($this->account_type)) . '</p>';
    echo '<p><strong>' . esc_html__('Account Number: ', 'bangladeshi-payments-mobile') . '</strong>' . esc_html($this->account_number) . '</p>';
    
    echo '<div>
            <label for="upay_phone">' . esc_html__('Upay Phone Number', 'bangladeshi-payments-mobile') . ' <span class="required">*</span></label>
            <input type="text" name="upay_phone" id="upay_phone" placeholder="' . esc_attr__('01XXXXXXXXX', 'bangladeshi-payments-mobile') . '" required>
          </div>';
    echo '<div>
            <label for="upay_transaction_id">' . esc_html__('Upay Transaction ID', 'bangladeshi-payments-mobile') . ' <span class="required">*</span></label>
            <input type="text" name="upay_transaction_id" id="upay_transaction_id" placeholder="' . esc_attr__('Transaction ID', 'bangladeshi-payments-mobile') . '" required>
          </div>';
    echo '<input type="hidden" name="upay_nonce" value="' . esc_attr(wp_create_nonce('upay_payment_nonce')) . '">';
}


    // Calculate total payment based on order total and Upay charge
private function calculate_total_payment() {
    global $woocommerce;
    $order_total = $woocommerce->cart->total; 
    $upay_charge_percentage = $this->get_option('upay_charge');
    
    // Check if the charge should be applied
    $apply_upay_charge = $this->get_option('apply_upay_charge') === 'yes';

    $upay_fee = $apply_upay_charge ? ($order_total * ($upay_charge_percentage / 100)) : 0;
    $total_payment = $order_total + $upay_fee;

    return number_format($total_payment, 2) . ' BDT';
}

// Calculate Upay fees
private function calculate_upay_fees() {
    global $woocommerce;
    $order_total = $woocommerce->cart->total; 
    $upay_charge_percentage = $this->get_option('upay_charge');

    // Check if the charge should be applied
    $apply_upay_charge = $this->get_option('apply_upay_charge') === 'yes';

    $upay_fee = $apply_upay_charge ? ($order_total * ($upay_charge_percentage / 100)) : 0;
    return number_format($upay_fee, 2) . ' BDT';
}


    // Validate Upay fields (checkout)// Validate Upay fields (checkout)
public function validate_fields() {
    if (isset($_POST['upay_nonce'])) {
        $nonce = sanitize_text_field(wp_unslash($_POST['upay_nonce']));
        if (!wp_verify_nonce($nonce, 'upay_payment_nonce')) {
            wc_add_notice(__('Nonce verification failed.', 'bangladeshi-payments-mobile'), 'error');
            return false;
        }
    } else {
        wc_add_notice(__('Nonce is missing.', 'bangladeshi-payments-mobile'), 'error');
        return false;
    }

    // Check for upay phone number
    if (isset($_POST['upay_phone']) && !empty($_POST['upay_phone'])) {
        $upay_phone = sanitize_text_field(wp_unslash($_POST['upay_phone']));
        if (!preg_match('/^01[0-9]{9}$/', $upay_phone)) {
            wc_add_notice(__('Please enter a valid Upay phone number.', 'bangladeshi-payments-mobile'), 'error');
            return false;
        }
    } else {
        wc_add_notice(__('Upay phone number is required.', 'bangladeshi-payments-mobile'), 'error');
        return false;
    }

    // Check for upay transaction ID
    if (isset($_POST['upay_transaction_id']) && !empty($_POST['upay_transaction_id'])) {
        $upay_transaction_id = sanitize_text_field(wp_unslash($_POST['upay_transaction_id']));
        if (empty($upay_transaction_id)) {
            wc_add_notice(__('Please enter your Upay transaction ID.', 'bangladeshi-payments-mobile'), 'error');
            return false;
        }
    } else {
        wc_add_notice(__('Upay transaction ID is required.', 'bangladeshi-payments-mobile'), 'error');
        return false;
    }

    return true;
}

// Process the payment (checkout)
public function process_payment($order_id) {
    if (!isset($_POST['upay_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['upay_nonce'])), 'upay_payment_nonce')) {
        wc_add_notice(__('Nonce verification failed.', 'bangladeshi-payments-mobile'), 'error');
        return false;
    }

    // Check for upay phone number
    if (isset($_POST['upay_phone']) && !empty($_POST['upay_phone'])) {
        $upay_phone = sanitize_text_field(wp_unslash($_POST['upay_phone']));
        if (!preg_match('/^01[0-9]{9}$/', $upay_phone)) {
            wc_add_notice(__('Please enter a valid upay phone number starting with 01 and containing 11 digits.', 'bangladeshi-payments-mobile'), 'error');
            return false;
        }
    } else {
        wc_add_notice(__('upay phone number is required.', 'bangladeshi-payments-mobile'), 'error');
        return false;
    }

    // Check for Upay transaction ID
    if (isset($_POST['upay_transaction_id'])) {
        $upay_transaction_id = sanitize_text_field(wp_unslash($_POST['upay_transaction_id']));
    } else {
        wc_add_notice(__('Upay transaction ID is required.', 'bangladeshi-payments-mobile'), 'error');
        return false;
    }

    $order = wc_get_order($order_id);
    
    update_post_meta($order_id, '_upay_phone', $upay_phone);
    update_post_meta($order_id, '_upay_transaction_id', $upay_transaction_id);
    
    $order->update_status('on-hold', __('Waiting for Upay payment confirmation.', 'bangladeshi-payments-mobile'));

    wc_reduce_stock_levels($order_id);
    WC()->cart->empty_cart();

    return array(
        'result' => 'success',
        'redirect' => $this->get_return_url($order),
    );
}


    // Display Upay information on the order page
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
            <div class="payment-order-page">
                <table>
                    <tr>
                        <td colspan="2">
                            <div class="payment-order-page-heading">
                                <div class="upay-image bpm-bg-image"></div>
                                <h4><?php echo esc_html('Upay Payment Information', 'bangladeshi-payments-mobile'); ?></h4>
                            </div>      
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo esc_html('Phone Number:', 'bangladeshi-payments-mobile');?></td>
                        <td><?php echo esc_html($upay_phone);?></td>
                    </tr>
                    <tr>
                        <td><?php echo esc_html('Transaction ID:', 'bangladeshi-payments-mobile');?></td>
                        <td><?php echo esc_html($upay_transaction_id);?></td>
                    </tr>
                </table>
            </div>
        <?php 
    }
}
