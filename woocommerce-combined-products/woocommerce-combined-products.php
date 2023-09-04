<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://#
 * @since             1.0.0
 * @package           Woocommerce_Combined_Products
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Combined Products
 * Plugin URI:        https://#
 * Description:       This is a description of the plugin.
 * Version:           1.0.0
 * Author:            Mark Vikhrov
 * Author URI:        https://#
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woocommerce-combined-products
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) || !is_woocommerce_active() ) {
	exit;
}

define('WCCP_VERSION', '1.0.0');

if(!function_exists('wccp_init')) {
	add_action( 'plugins_loaded', 'wccp_init', 11 );

	function wccp_init() {
		require_once plugin_dir_path(__FILE__) . 'includes/class-wccp-product-type.php';
		require_once plugin_dir_path(__FILE__) . 'includes/class-wccp-product-fields.php';
	}
}

add_action('admin_enqueue_scripts', 'wccp_load_admin_styles');

function wccp_load_admin_styles() {
	wp_enqueue_style( 'wccp-admin-main', plugins_url( 'admin/css/main.css', __FILE__ ), array(), WCCP_VERSION);
}


function activate_woocommerce_combined_products() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-combined-products-activator.php';
	Woocommerce_Combined_Products_Activator::activate();
}

function deactivate_woocommerce_combined_products() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-combined-products-deactivator.php';
	Woocommerce_Combined_Products_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_woocommerce_combined_products' );
register_deactivation_hook( __FILE__, 'deactivate_woocommerce_combined_products' );


