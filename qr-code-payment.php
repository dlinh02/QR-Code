<?php
/**
 * Plugin Name:       QR Code Payment 
 * Description:       Create QR Code Payment method for WooCommerce
 * Version:           1.0
 * Author:            Linh
 */

// Ensure WooCommerce is active
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/qr-code-functions.php';

// Include gateway class
add_action('plugins_loaded', 'init_qr_code_gateway_class');

// Add the gateway to WooCommerce settings methods
add_filter('woocommerce_payment_gateways', 'add_qr_code_gateway');

// Add View details for plugin
add_filter('plugin_row_meta', 'add_plugin_meta_links', 10, 2);

// Add action click view details
add_action('admin_enqueue_scripts', 'qr_code_enqueue_scripts');

// Add add_qr_code_details_popup before admin end loading
add_action('admin_footer', 'add_qr_code_details_popup');