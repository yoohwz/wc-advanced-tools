<?php
/**
 * Plugin Name: WooCommerce Advanced Tools
 * Description: Focused WooCommerce administration tools, starting with Quick Order Export.
 * Version: 0.1.0
 * Author: YoOhw
 * Requires at least: 6.5
 * Requires PHP: 8.2
 * WC requires at least: 9.0
 * Text Domain: wc-advanced-tools
 */

defined( 'ABSPATH' ) || exit;

add_action( 'before_woocommerce_init', static function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

require_once __DIR__ . '/includes/class-quick-order-export.php';

add_action( 'admin_menu', array( 'YoOhw\AdvancedTools\Quick_Order_Export', 'register_menu' ) );
add_action( 'admin_post_wc_advanced_tools_export', array( 'YoOhw\AdvancedTools\Quick_Order_Export', 'download' ) );
add_action( 'admin_post_nopriv_wc_advanced_tools_export', array( 'YoOhw\AdvancedTools\Quick_Order_Export', 'download' ) );
