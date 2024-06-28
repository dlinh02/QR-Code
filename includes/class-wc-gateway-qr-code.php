<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'api-qr-code.php';

class WC_Gateway_QRCode extends WC_Payment_Gateway {

    public function __construct() {
        $this->id = 'qrcode'; // ID gateway
        $this->has_fields = true; // interacting from user

        $this->method_title = 'QR Code';
        $this->method_description = 'QR Code Payment Method';

        // appear in checkout
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        $this->init_form_fields();
        $this->init_settings(); // selet value from data base for fields

        // save changes settings in payments settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'qr_code'));

        // Enqueue script
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function enqueue_admin_scripts() {
        $plugin_dir_url = plugin_dir_url(dirname(__FILE__));
        wp_enqueue_script('qrcode-admin-script', $plugin_dir_url . 'assets/js/qr-code.js', array('jquery'), null, true);
    }

    // create fiels setting in payment gateway woocommerce
    public function init_form_fields() {

        // get options from function call api list info banks
        $api = new API();
        $options = $api->get_info_banks();

        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable',
                'type' => 'checkbox',
                'label' => 'Enable QR Code Payment Method.',
                'default' => 'yes',
            ),
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default' => 'QR Code Payment Method',
                // 'desc_tip' => true,
            ),
            'description' => array(
                'title' => 'Description',
                'type' => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default' => 'Pay via QR Code Payment Method.',
                'required' => true,
            ),
            'clientID' => array(
                'title' => 'Client ID',
                'type' => 'text',
                'description' => 'Enter your Client ID.',
                'default' => '',
                'required' => true,
            ),
            'apiKey' => array(
                'title' => 'API Key',
                'type' => 'text',
                'description' => 'Enter your API key.',
                'default' => '',
                'required' => true,
            ),
            'bank' => array(
                'title' => 'Bank',
                'type' => 'select',
                'description' => 'Select your bank.',
                'default' => 'default',
                'options' => $options,
                'required' => true,
            ),
            'accountNo' => array(
                'title' => 'Account Number',
                'type' => 'text',
                'description' => 'Enter your Account number.',
                'default' => '',
                'required' => true,
            ),
            'accountName' => array(
                'title' => 'Account Name',
                'type' => 'text',
                'default' => '',
                'required' => true,
            ),
            'addInfo' => array(
                'title' => 'Message',
                'type' => 'text',
                'description' => 'Enter your message, this will appear in bank transfer information. <br> Example: Payment for Order xxx (xxx is automatically generated)',
                'default' => '',
                'required' => true,
            ),
        );
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        $client_id = $this->get_option('clientID');
        $api_key = $this->get_option('apiKey'); 

        $data = array(
            'accountNo' => $this->get_option('accountNo'),
            'accountName' => $this->get_option('accountName'),
            'acqId' => $this->get_option('bank'),
            'addInfo' => $this->get_option('addInfo'). $order_id,
            'amount' => $order->get_total(),
            'recipient' => $this->get_option('accountName'),
            "template" => "print"
        );
        
        //call function generate qr code
        $api = new API();
        $response = $api->get_qr_code($data, $client_id, $api_key);

        // Xử lý phản hồi từ API
        if ($response) {
            $response_data = json_decode($response, true);

            if (isset($response_data['data']['qrDataURL'])) {
                // save into database
                update_post_meta($order_id, 'qr_code_url', $response_data['data']['qrDataURL']);

                // Remove cart
                WC()->cart->empty_cart();

                // Return thankyou redirect.
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order),
                );

            } else {
                // error
                wc_add_notice('There was an error while generating the QR code. Please try again.', 'error');
                return;
            }
        } else {
            // Handle the error if no response is received from the API.
            wc_add_notice('Unable to connect to the VietQR API. Please try again later.', 'error');
            return;
        }
    }

    public function qr_code($order_id)
    {
        $qr_code_url = get_post_meta($order_id, 'qr_code_url', true);

        echo '
        <h3>Please proceed to payment for your order  #' . $order_id . ' here:</h3>
        <img src="' . $qr_code_url . '" alt="QR Code" width="300px">
        ';
    }

}
