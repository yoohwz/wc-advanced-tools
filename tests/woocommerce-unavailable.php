<?php
/** Run with WooCommerce inactive/absent on a disposable installation. */
use YoOhw\AdvancedTools\Quick_Order_Export as Export;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! getenv( 'AT_DISPOSABLE_TESTS' ) ) {
	throw new RuntimeException( 'Disposable WP-CLI test only.' );
}
if ( Export::available() ) {
	throw new RuntimeException( 'WooCommerce must be inactive/absent.' );
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/template.php';
wp_set_current_user( get_user_by( 'login', 'at3admin' )->ID );
ob_start();
Export::render_page();
$html = ob_get_clean();
if ( ! str_contains( $html, 'requires WooCommerce 9.0' ) || str_contains( $html, '<form' ) ) {
	throw new RuntimeException( 'Missing dependency state is unclear.' );
}
add_filter( 'wp_die_handler', static fn() => static function ( $message, $title, $options ) {
	throw new RuntimeException( wp_strip_all_tags( $message ), $options['response'] ?? 500 );
} );
// Simulate a manager capability retained after WooCommerce deactivation.
$user = wp_get_current_user();
$user->add_cap( 'manage_woocommerce' );
$_SERVER['REQUEST_METHOD'] = 'POST';
$_REQUEST['_wpnonce'] = wp_create_nonce( 'wc_advanced_tools_export' );
try {
	Export::download();
	throw new LogicException( 'Export unexpectedly available.' );
} catch ( RuntimeException $error ) {
	if ( 503 !== $error->getCode() ) {
		throw $error;
	}
}
WP_CLI::success( 'WooCommerce unavailable: plugin loaded safely, clear admin state, export refused.' );
