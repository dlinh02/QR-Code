<?php
if (!defined('ABSPATH')) {
    exit;
}

$plugin_dir = dirname(__DIR__) ; // Lấy đường dẫn của thư mục cha 

// include gateways class
function init_qr_code_gateway_class()
{
    require_once plugin_dir_path(__FILE__) . '/class-wc-gateway-qr-code.php';
}

// Add the gateway to WooCommerce settings methods
function add_qr_code_gateway($gateways)
{
    $gateways[] = 'WC_Gateway_QRCode';
    return $gateways;
}

// Add View details for plugin
function add_plugin_meta_links($links, $file)
{
    global $plugin_dir;
    $qr_code_payment_path = $plugin_dir . '/qr-code-payment.php';
    if (plugin_basename($qr_code_payment_path) == $file) {
        $row_meta = array(
            'view_details' => '<a href="#" id="qr-code-details-link">' . __('View details') . '</a>',
        );
        return array_merge($links, $row_meta);
    }

    return $links;
}

// Add action click view details
function qr_code_enqueue_scripts()
{
    global $plugin_dir;

    wp_enqueue_script('jquery-ui-dialog');
    wp_enqueue_style('wp-jquery-ui-dialog');
    wp_enqueue_script('qr-code-popup-script', $plugin_dir . '/assets/js/qr-code.js', array('jquery', 'jquery-ui-dialog'), null, true);
    wp_enqueue_style('qr-code-popup-style', $plugin_dir . '/assets/css/qr-code-details.css');

}

function add_qr_code_details_popup()
{
    global $plugin_dir;

    $template_path = $plugin_dir . '/templates/qr-code-usage-guide.html';
    $content = file_get_contents($template_path);
    echo '<div id="qr-code-details-dialog" title="Usage Guide for QR Code Payment Gateway Plugin">';
    echo $content;
    echo '</div>';
    
}