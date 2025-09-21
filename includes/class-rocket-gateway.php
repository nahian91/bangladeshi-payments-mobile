<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_rocket extends WC_Payment_Gateway {

    public function __construct() {
        $this->id = 'rocket'; 
        $this->icon = ''; 
        $this->has_fields = true; 
        $this->method_title = __('Rocket Payment', 'bangladeshi-payments-mobile');
        $this->method_description = __('Pay via Rocket by entering your phone number and transaction ID.', 'bangladeshi-payments-mobile');
        
        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');        
        $this->icon = plugins_url( 'img/rocket.png', __FILE__ );
        $this->account_type = $this->get_option('account_type'); 
        $this->account_number = $this->get_option('account_number'); 
        $this->rocket_charge = $this->get_option('rocket_charge'); 

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    // Admin settings fields
public function init_form_fields() {
    $this->form_fields = array(
        'enabled' => array(
            'title' => __('Enable/Disable', 'bangladeshi-payments-mobile'),
            'type' => 'checkbox',
            'label' => __('Enable Rocket Payment', 'bangladeshi-payments-mobile'),
            'default' => 'no',
        ),
        'title' => array(
            'title' => __('Title', 'bangladeshi-payments-mobile'),
            'type' => 'text',
            'description' => __('This controls the title the user sees during checkout.', 'bangladeshi-payments-mobile'),
            'default' => __('Rocket', 'bangladeshi-payments-mobile'),
            'desc_tip' => true,
        ),
        'description' => array(
            'title' => __('Description', 'bangladeshi-payments-mobile'),
            'type' => 'textarea',
            'description' => __('Payment method description that the customer will see during checkout.', 'bangladeshi-payments-mobile'),
            'default' => __('Pay with Rocket. Enter your rocket phone number and transaction ID.', 'bangladeshi-payments-mobile'),
        ),
        'account_type' => array(
            'title' => __('Account Type', 'bangladeshi-payments-mobile'),
            'type' => 'select',
            'options' => array(
                'personal' => __('Personal', 'bangladeshi-payments-mobile'),
                'agent' => __('Agent', 'bangladeshi-payments-mobile'),
            ),
            'description' => __('Select the type of account used for rocket transactions.', 'bangladeshi-payments-mobile'),
            'default' => 'personal',
        ),
        'account_number' => array(
            'title' => __('Account Number', 'bangladeshi-payments-mobile'),
            'type' => 'text',
            'description' => __('Enter the account number for rocket transactions.', 'bangladeshi-payments-mobile'),
            'default' => '',
            'required' => true,
        ),
        'apply_rocket_charge' => array(
            'title' => __('Apply Rocket Charge', 'bangladeshi-payments-mobile'),
            'type' => 'checkbox',
            'label' => __('Apply Rocket charge to total payment?', 'bangladeshi-payments-mobile'),
            'default' => 'yes',
        ),
        'rocket_charge' => array(
            'title' => __('rocket Charge (%)', 'bangladeshi-payments-mobile'),
            'type' => 'number',
            'description' => __('Enter the rocket charge as a percentage (e.g., 1.4 for 1.4%).', 'bangladeshi-payments-mobile'),
            'default' => '1.4',
            'custom_attributes' => array(
                'step' => '0.01',
            ),
        ),
    );
}


public function payment_fields() {
    // Translators: %1$s is the total payment amount. %2$s is the rocket fees amount.
    echo '<p>' . sprintf(esc_html__('You need to send us %1$s (Fees %2$s)', 'bangladeshi-payments-mobile'), esc_html($this->calculate_total_payment()), esc_html($this->calculate_rocket_fees())) . '</p>';
    echo '<p>' . esc_html($this->description) . '</p>';

    // Show Account Type and Number
    echo '<p><strong>' . esc_html__('Account Type: ', 'bangladeshi-payments-mobile') . '</strong>' . esc_html(ucfirst($this->account_type)) . '</p>';
    echo '<p><strong>' . esc_html__('Account Number: ', 'bangladeshi-payments-mobile') . '</strong>' . esc_html($this->account_number) . '</p>';
    
    echo '<div>
            <label for="rocket_phone">' . esc_html__('Rocket Phone Number', 'bangladeshi-payments-mobile') . ' <span class="required">*</span></label>
            <input type="text" name="rocket_phone" id="rocket_phone" placeholder="' . esc_attr__('01XXXXXXXXX', 'bangladeshi-payments-mobile') . '" required>
          </div>';
    echo '<div>
            <label for="rocket_transaction_id">' . esc_html__('Rocket Transaction ID', 'bangladeshi-payments-mobile') . ' <span class="required">*</span></label>
            <input type="text" name="rocket_transaction_id" id="rocket_transaction_id" placeholder="' . esc_attr__('Transaction ID', 'bangladeshi-payments-mobile') . '" required>
          </div>';
    echo '<input type="hidden" name="rocket_nonce" value="' . esc_attr(wp_create_nonce('rocket_payment_nonce')) . '">';
}


    // Calculate total payment based on order total and rocket charge
private function calculate_total_payment() {
    global $woocommerce;
    $order_total = $woocommerce->cart->total; 
    $rocket_charge_percentage = $this->get_option('rocket_charge');
    
    // Check if the charge should be applied
    $apply_rocket_charge = $this->get_option('apply_rocket_charge') === 'yes';

    $rocket_fee = $apply_rocket_charge ? ($order_total * ($rocket_charge_percentage / 100)) : 0;
    $total_payment = $order_total + $rocket_fee;

    return number_format($total_payment, 2) . ' BDT';
}

// Calculate rocket fees
private function calculate_rocket_fees() {
    global $woocommerce;
    $order_total = $woocommerce->cart->total; 
    $rocket_charge_percentage = $this->get_option('rocket_charge');

    // Check if the charge should be applied
    $apply_rocket_charge = $this->get_option('apply_rocket_charge') === 'yes';

    $rocket_fee = $apply_rocket_charge ? ($order_total * ($rocket_charge_percentage / 100)) : 0;
    return number_format($rocket_fee, 2) . ' BDT';
}


    // Validate rocket fields (checkout)// Validate rocket fields (checkout)
public function validate_fields() {
    if (isset($_POST['rocket_nonce'])) {
        $nonce = sanitize_text_field(wp_unslash($_POST['rocket_nonce']));
        if (!wp_verify_nonce($nonce, 'rocket_payment_nonce')) {
            wc_add_notice(__('Nonce verification failed.', 'bangladeshi-payments-mobile'), 'error');
            return false;
        }
    } else {
        wc_add_notice(__('Nonce is missing.', 'bangladeshi-payments-mobile'), 'error');
        return false;
    }

    // Check for rocket phone number
    if (isset($_POST['rocket_phone']) && !empty($_POST['rocket_phone'])) {
        $rocket_phone = sanitize_text_field(wp_unslash($_POST['rocket_phone']));
        if (!preg_match('/^01[0-9]{9}$/', $rocket_phone)) {
            wc_add_notice(__('Please enter a valid rocket phone number.', 'bangladeshi-payments-mobile'), 'error');
            return false;
        }
    } else {
        wc_add_notice(__('rocket phone number is required.', 'bangladeshi-payments-mobile'), 'error');
        return false;
    }

    // Check for rocket transaction ID
    if (isset($_POST['rocket_transaction_id']) && !empty($_POST['rocket_transaction_id'])) {
        $rocket_transaction_id = sanitize_text_field(wp_unslash($_POST['rocket_transaction_id']));
        if (empty($rocket_transaction_id)) {
            wc_add_notice(__('Please enter your rocket transaction ID.', 'bangladeshi-payments-mobile'), 'error');
            return false;
        }
    } else {
        wc_add_notice(__('rocket transaction ID is required.', 'bangladeshi-payments-mobile'), 'error');
        return false;
    }

    return true;
}

// Process the payment (checkout)
public function process_payment($order_id) {
    if (!isset($_POST['rocket_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rocket_nonce'])), 'rocket_payment_nonce')) {
        wc_add_notice(__('Nonce verification failed.', 'bangladeshi-payments-mobile'), 'error');
        return false;
    }

    // Check for rocket phone number
    if (isset($_POST['rocket_phone']) && !empty($_POST['rocket_phone'])) {
        $rocket_phone = sanitize_text_field(wp_unslash($_POST['rocket_phone']));
        if (!preg_match('/^01[0-9]{9}$/', $rocket_phone)) {
            wc_add_notice(__('Please enter a valid rocket phone number starting with 01 and containing 11 digits.', 'bangladeshi-payments-mobile'), 'error');
            return false;
        }
    } else {
        wc_add_notice(__('rocket phone number is required.', 'bangladeshi-payments-mobile'), 'error');
        return false;
    }

    // Check for rocket transaction ID
    if (isset($_POST['rocket_transaction_id'])) {
        $rocket_transaction_id = sanitize_text_field(wp_unslash($_POST['rocket_transaction_id']));
    } else {
        wc_add_notice(__('Rocket transaction ID is required.', 'bangladeshi-payments-mobile'), 'error');
        return false;
    }

    $order = wc_get_order($order_id);
    
    update_post_meta($order_id, '_rocket_phone', $rocket_phone);
    update_post_meta($order_id, '_rocket_transaction_id', $rocket_transaction_id);
    
    $order->update_status('on-hold', __('Waiting for rocket payment confirmation.', 'bangladeshi-payments-mobile'));

    wc_reduce_stock_levels($order_id);
    WC()->cart->empty_cart();

    return array(
        'result' => 'success',
        'redirect' => $this->get_return_url($order),
    );
}


    // Display rocket information on the order page
    public function display_rocket_info_on_order($order_id) {
        $rocket_phone = get_post_meta($order_id, '_rocket_phone', true);
        $rocket_transaction_id = get_post_meta($order_id, '_rocket_transaction_id', true);
        
        if ($rocket_phone || $rocket_transaction_id) {
            echo '<h3>' . esc_html('Rocket Payment Information', 'bangladeshi-payments-mobile') . '</h3>';
            echo '<p><strong>' . esc_html('Phone Number:', 'bangladeshi-payments-mobile') . '</strong> ' . esc_html($rocket_phone) . '</p>';
            echo '<p><strong>' . esc_html('Transaction ID:', 'bangladeshi-payments-mobile') . '</strong> ' . esc_html($rocket_transaction_id) . '</p>';
        }
    }
}

// Add the gateway to WooCommerce
add_filter('woocommerce_payment_gateways', 'add_rocket_gateway');
function add_rocket_gateway($methods) {
    $methods[] = 'WC_Gateway_rocket';
    return $methods;
}

// Display rocket information under Billing column on the order page
add_action('woocommerce_admin_order_data_after_billing_address', 'display_rocket_info_admin_order', 10, 1);
function display_rocket_info_admin_order($order) {
    $rocket_phone = get_post_meta($order->get_id(), '_rocket_phone', true);
    $rocket_transaction_id = get_post_meta($order->get_id(), '_rocket_transaction_id', true);
    
    if ($rocket_phone || $rocket_transaction_id) {
        ?>
            <div class="payment-order-page">
                <table>
                    <tr>
                        <td colspan="2">
                            <div class="payment-order-page-heading">
                                <div class="rocket-image bpm-bg-image"></div>
                                <h4><?php echo esc_html('Rocket Payment Information', 'bangladeshi-payments-mobile'); ?></h4>
                            </div>     
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo esc_html('Phone Number:', 'bangladeshi-payments-mobile');?></td>
                        <td><?php echo esc_html($rocket_phone);?></td>
                    </tr>
                    <tr>
                        <td><?php echo esc_html('Transaction ID:', 'bangladeshi-payments-mobile');?></td>
                        <td><?php echo esc_html($rocket_transaction_id);?></td>
                    </tr>
                </table>
            </div>
        <?php 
    }
}
