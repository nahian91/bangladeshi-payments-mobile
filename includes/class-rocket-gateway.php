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
        $this->method_description = __('Pay via rocket by entering your phone number and transaction ID.', 'bangladeshi-payments-mobile');
        
        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
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
                'default' => 'yes',
            ),
            'title' => array(
                'title' => __('Title', 'bangladeshi-payments-mobile'),
                'type' => 'text',
                'description' => __('This controls the title the user sees during checkout.', 'bangladeshi-payments-mobile'),
                'default' => __('Rocket Payment', 'bangladeshi-payments-mobile'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'bangladeshi-payments-mobile'),
                'type' => 'textarea',
                'description' => __('Payment method description that the customer will see during checkout.', 'bangladeshi-payments-mobile'),
                'default' => __('Pay with rocket. Enter your rocket phone number and transaction ID.', 'bangladeshi-payments-mobile'),
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

    // Payment fields
    public function payment_fields() {
        echo '<p>' . sprintf(esc_html__('You need to send us %1$s (Fees %2$s)', 'bangladeshi-payments-mobile'), esc_html($this->calculate_total_payment()), esc_html($this->calculate_rocket_fees())) . '</p>';
        echo '<p>' . esc_html($this->description) . '</p>';
        
        echo '<div>
                <label for="rocket_phone">' . esc_html__('rocket Phone Number', 'bangladeshi-payments-mobile') . ' <span class="required">*</span></label>
                <input type="text" name="rocket_phone" id="rocket_phone" placeholder="' . esc_attr__('01XXXXXXXXX', 'bangladeshi-payments-mobile') . '" required>
              </div>';
        echo '<div>
                <label for="rocket_transaction_id">' . esc_html__('rocket Transaction ID', 'bangladeshi-payments-mobile') . ' <span class="required">*</span></label>
                <input type="text" name="rocket_transaction_id" id="rocket_transaction_id" placeholder="' . esc_attr__('Transaction ID', 'bangladeshi-payments-mobile') . '" required>
              </div>';
        echo '<input type="hidden" name="rocket_nonce" value="' . esc_attr(wp_create_nonce('rocket_payment_nonce')) . '">';
    }

    // Calculate total payment based on order total and rocket charge
    private function calculate_total_payment() {
        global $woocommerce;
        $order_total = $woocommerce->cart->total; 
        $rocket_charge_percentage = $this->get_option('rocket_charge');
        $rocket_fee = ($order_total * ($rocket_charge_percentage / 100));
        $total_payment = $order_total + $rocket_fee;
        return number_format($total_payment, 2) . ' BDT';
    }

    // Calculate rocket fees
    private function calculate_rocket_fees() {
        global $woocommerce;
        $order_total = $woocommerce->cart->total; 
        $rocket_charge_percentage = $this->get_option('rocket_charge');
        $rocket_fee = ($order_total * ($rocket_charge_percentage / 100));
        return number_format($rocket_fee, 2) . ' BDT';
    }

    // Validate rocket fields (checkout)
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

        if (isset($_POST['rocket_phone'])) {
            $rocket_phone = sanitize_text_field(wp_unslash($_POST['rocket_phone']));
            if (empty($rocket_phone) || !preg_match('/^01[0-9]{9}$/', $rocket_phone)) {
                wc_add_notice(__('Please enter a valid rocket phone number.', 'bangladeshi-payments-mobile'), 'error');
                return false;
            }
        } else {
            wc_add_notice(__('Rocket phone number is required.', 'bangladeshi-payments-mobile'), 'error');
            return false;
        }

        if (isset($_POST['rocket_transaction_id'])) {
            $rocket_transaction_id = sanitize_text_field(wp_unslash($_POST['rocket_transaction_id']));
            if (empty($rocket_transaction_id)) {
                wc_add_notice(__('Please enter your rocket transaction ID.', 'bangladeshi-payments-mobile'), 'error');
                return false;
            }
        } else {
            wc_add_notice(__('Rocket transaction ID is required.', 'bangladeshi-payments-mobile'), 'error');
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

        $rocket_phone = sanitize_text_field(wp_unslash($_POST['rocket_phone']));
        $rocket_transaction_id = sanitize_text_field(wp_unslash($_POST['rocket_transaction_id']));

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
            <div class="paymen-order-page">
                <?php 
                    echo '<h3>' . esc_html('Rocket Payment Information', 'bangladeshi-payments-mobile') . '</h3>';
                    echo '<p><strong>' . esc_html('Phone Number:', 'bangladeshi-payments-mobile') . '</strong> ' . esc_html($rocket_phone) . '</p>';
                    echo '<p><strong>' . esc_html('Transaction ID:', 'bangladeshi-payments-mobile') . '</strong> ' . esc_html($rocket_transaction_id) . '</p>';
                ?>
            </div>
        <?php 
    }
}