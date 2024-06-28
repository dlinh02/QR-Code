<?php
if (!defined('ABSPATH')) {
    exit;
}
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
    $qr_code_payment_path = plugin_dir_url( __DIR__ ) . 'qr-code-payment.php';
    $basename = plugin_basename( dirname(__DIR__).'/'. basename($qr_code_payment_path) );

    if ($basename == $file) {
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
    wp_enqueue_script('jquery-ui-dialog');
    wp_enqueue_style('wp-jquery-ui-dialog');
    wp_enqueue_script('qr-code-script', plugin_dir_url( __DIR__ )  . 'assets/js/qr-code.js', array('jquery', 'jquery-ui-dialog'), null, true);
    wp_enqueue_style('qr-code-style', plugin_dir_url( __DIR__  ) . 'assets/css/qr-code.css');
}

function add_qr_code_details_popup()
{
    $template_path = plugin_dir_url( __DIR__  ) . 'templates/qr-code-usage-guide.html';
    $content = file_get_contents($template_path);
    echo '<div id="qr-code-details-dialog" title="Usage Guide for QR Code Payment Gateway Plugin">';
    echo $content;
    echo '</div>';
}