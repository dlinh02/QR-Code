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

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        $this->bank = $this->get_option('bank');
        $this->accountNo = $this->get_option('accountNo');

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
                'label' => 'Enable VietQR Payment Gateway',
                'default' => 'yes',
            ),
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default' => 'QR Code Payment Method',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => 'Description',
                'type' => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default' => 'Pay via QR Code Payment Gateway.',
                'required' => true,
            ),
            'clientID' => array(
                'title' => 'Client ID',
                'type' => 'text',
                'default' => '',
                'required' => true,
            ),
            'apiKey' => array(
                'title' => 'API Key',
                'type' => 'text',
                'default' => '',
                'required' => true,
            ),
            'bank' => array(
                'title' => 'Bank',
                'type' => 'select',
                'description' => 'Select Bank.',
                'default' => 'default',
                'options' => $options,
                'required' => true,
                'desc_tip' => true,
            ),
            'accountNo' => array(
                'title' => 'Account Number',
                'type' => 'text',
                'default' => '',
                'required' => true,
            ),
            'accountName' => array(
                'title' => 'Account Name',
                'type' => 'text',
                'default' => '',
                'required' => true,
            ),
        );
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        // Gọi API của VietQR để tạo mã QR và lưu thông tin vào đơn hàng
        $client_id = $this->get_option('clientID');
        $api_key = $this->get_option('apiKey'); 

        $data = array(
            'accountNo' => $this->get_option('accountNo'),
            'accountName' => $this->get_option('accountName'),
            'acqId' => $this->get_option('bank'),
            'addInfo' => "Don hang " . $order_id,
            'amount' => $order->get_total(),
            'recipient' => $this->get_option('accountName'),
            "template" => "print"
        );

        $api = new API();
        $response = $api->get_qr_code($data, $client_id, $api_key);

        // Xử lý phản hồi từ API
        if ($response) {
            $response_data = json_decode($response, true);

            if (isset($response_data['data']['qrDataURL'])) {
                // Lưu thông tin mã QR vào đơn hàng để hiển thị hoặc tải về sau này
                $data_order = update_post_meta($order_id, 'qr_code_url', $response_data['data']['qrDataURL']);

                $order = wc_get_order($order_id);

                if ($order->get_total() > 0) {
                    // cập nhật trạng thái tạm giữ.
                    $order->update_status(apply_filters('woocommerce_' . $this->id . '_process_payment_order_status', 'on-hold', $order), __('Awaiting BACS payment', 'woocommerce'));
                } else {
                    $order->payment_complete();
                }

                // Remove cart
                WC()->cart->empty_cart();

                // Return thankyou redirect.
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order),
                );

            } else {
                // Xử lý lỗi nếu có
                wc_add_notice('Đã xảy ra lỗi khi tạo mã QR. Vui lòng thử lại.', 'error');
                return;
            }
        } else {
            // Xử lý lỗi nếu không nhận được phản hồi từ API
            wc_add_notice('Không thể kết nối với VietQR API. Vui lòng thử lại sau.', 'error');
            return;
        }
    }

    public function qr_code($order_id)
    {
        $this->payment_details($order_id);
    }

    public function payment_details($order_id)
    {
        $order = wc_get_orders($order_id);
        $qr_code_url = get_post_meta($order_id, 'qr_code_url', true);

        echo '
        <h3>Vui lòng thanh toán đơn hàng #' . $order_id . ':</h3>
        <img src="' . $qr_code_url . '" alt="QR Code" width="300px">
        ';
    }
}
