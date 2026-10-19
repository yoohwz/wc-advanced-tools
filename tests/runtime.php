<?php
/** Run only with wp eval-file on a disposable installation; creates synthetic orders/users. */
use YoOhw\AdvancedTools\Quick_Order_Export as Export;
use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! getenv( 'AT_DISPOSABLE_TESTS' ) ) {
	throw new RuntimeException( 'Requires WP-CLI and AT_DISPOSABLE_TESTS=1 on a disposable site.' );
}

function at_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function at_rows( $stream ) {
	$rows = array();
	while ( false !== ( $row = fgetcsv( $stream, 0, ',', '"', '' ) ) ) {
		$rows[] = $row;
	}
	fclose( $stream );
	return $rows;
}

function at_input( $overrides = array() ) {
	return array_merge( array( 'start_date' => '2020-03-08', 'end_date' => '2020-03-08', 'status' => 'wc-completed' ), $overrides );
}

function at_throws( $callback, $class ) {
	try {
		$callback();
	} catch ( Throwable $error ) {
		at_assert( $error instanceof $class, 'Unexpected exception: ' . get_class( $error ) . ': ' . $error->getMessage() );
		return;
	}
	throw new RuntimeException( 'Expected exception: ' . $class );
}

$mode = $args[0] ?? '';
at_assert( in_array( $mode, array( 'hpos', 'legacy' ), true ), 'Specify hpos or legacy.' );
at_assert( OrderUtil::custom_orders_table_usage_is_enabled() === ( 'hpos' === $mode ), 'Storage mode mismatch.' );
update_option( 'timezone_string', 'America/New_York' );
// Avoid email and external delivery in synthetic fixture setup.
remove_all_actions( 'woocommerce_order_status_changed' );
$ids = array();
foreach ( array(
	array( '2020-03-07 23:59:59', 'completed', '1.00' ),
	array( '2020-03-08 00:00:00', 'completed', '12.30' ),
	array( '2020-03-08 23:59:59', 'completed', '45.67' ),
	array( '2020-03-09 00:00:00', 'completed', '2.00' ),
	array( '2020-03-08 12:00:00', 'processing', '8.00' ),
) as $fixture ) {
	$order = new WC_Order();
	$order->set_date_created( ( new DateTimeImmutable( $fixture[0], wp_timezone() ) )->getTimestamp() );
	$order->set_status( $fixture[1] );
	$order->set_currency( 'USD' );
	$order->set_total( $fixture[2] );
	$order->set_billing_email( 'private-fixture@example.invalid' );
	$order->set_billing_address_1( 'Private fixture address' );
	$order->save();
	$ids[] = $order->get_id();
}

$query = Export::query_args( at_input() );
at_assert( $query['limit'] === 1001 && $query['return'] === 'ids', 'Query must be bounded before hydration.' );
$writes = array();
$watch = static function ( $sql ) use ( &$writes ) {
	if ( preg_match( '/^\s*(INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|ALTER|TRUNCATE)\b/i', $sql ) ) {
		$writes[] = $sql;
	}
	return $sql;
};
add_filter( 'query', $watch );
$rows = at_rows( Export::build_csv( $query ) );
$all = at_rows( Export::build_csv( Export::query_args( at_input( array( 'status' => 'all' ) ) ) ) );
$empty = at_rows( Export::build_csv( Export::query_args( at_input( array( 'start_date' => '2010-01-01', 'end_date' => '2010-01-01' ) ) ) ) );
remove_filter( 'query', $watch );
at_assert( array() === $writes, 'Export issued database writes.' );
at_assert( $rows[0] === array( 'order_id', 'order_number', 'created_at', 'status', 'currency', 'order_total' ), 'Unexpected CSV fields.' );
at_assert( count( $rows ) === 3 && count( $all ) === 4 && count( $empty ) === 1, 'Date/status/empty filtering failed.' );
at_assert( $rows[1] === array( (string) $ids[1], (string) $ids[1], '2020-03-08T00:00:00-05:00', 'completed', 'USD', '12.30' ), 'First row data or start boundary mismatch.' );
at_assert( $rows[2] === array( (string) $ids[2], (string) $ids[2], '2020-03-08T23:59:59-04:00', 'completed', 'USD', '45.67' ), 'Last row data or DST/end boundary mismatch.' );
at_assert( ! str_contains( json_encode( $rows ), 'private-fixture' ), 'Private fields leaked.' );

foreach ( array(
	array( 'start_date' => '2020-02-30' ), array( 'start_date' => array() ),
	array( 'start_date' => '2020-03-09' ), array( 'end_date' => '' ),
	array( 'status' => array( 'wc-completed' ) ), array( 'status' => 'trash' ),
) as $bad ) {
	at_throws( static fn() => Export::query_args( at_input( $bad ) ), InvalidArgumentException::class );
}
foreach ( array( '=1+1', '+1', '-1', '@SUM(A1)', '  =1', "\tdata", "\rdata", "\ndata" ) as $formula ) {
	at_assert( Export::csv_cell( $formula ) === "'" . $formula, 'Unsafe CSV cell.' );
}
$number_filter = static fn() => '=HYPERLINK("https://example.invalid","test"),"quoted"';
add_filter( 'woocommerce_order_number', $number_filter );
$formula_rows = at_rows( Export::build_csv( $query ) );
remove_filter( 'woocommerce_order_number', $number_filter );
at_assert( $formula_rows[1][1] === "'" . $number_filter(), 'Formula escaping or CSV quoting failed.' );
$failed_query = static fn() => array( PHP_INT_MAX );
add_filter( 'woocommerce_order_query', $failed_query );
at_throws( static fn() => Export::build_csv( $query ), RuntimeException::class );
remove_filter( 'woocommerce_order_query', $failed_query );

class AT_Test_Die extends RuntimeException {}
add_filter( 'wp_die_handler', static fn() => static function ( $message, $title, $options ) {
	throw new AT_Test_Die( wp_strip_all_tags( $message ), $options['response'] ?? 500 );
} );
function at_denied( $code ) {
	try {
		Export::download();
	} catch ( AT_Test_Die $error ) {
		at_assert( $error->getCode() === $code, 'Unexpected HTTP error: ' . $error->getCode() );
		return;
	}
	throw new RuntimeException( 'Expected download refusal.' );
}
$manager = username_exists( 'at3manager' ) ?: wp_insert_user( array( 'user_login' => 'at3manager', 'user_pass' => 'at3-disposable-only', 'role' => 'shop_manager' ) );
$subscriber = username_exists( 'at3subscriber' ) ?: wp_insert_user( array( 'user_login' => 'at3subscriber', 'user_pass' => 'at3-disposable-only', 'role' => 'subscriber' ) );
wp_set_current_user( 0 );
$_SERVER['REQUEST_METHOD'] = 'POST';
at_denied( 403 );
wp_set_current_user( $subscriber );
$_REQUEST['_wpnonce'] = wp_create_nonce( 'wc_advanced_tools_export' );
at_denied( 403 );
wp_set_current_user( $manager );
$_SERVER['REQUEST_METHOD'] = 'GET';
at_denied( 405 );
$_SERVER['REQUEST_METHOD'] = 'POST';
$_REQUEST['_wpnonce'] = 'invalid';
at_denied( 403 );
$_REQUEST['_wpnonce'] = wp_create_nonce( 'wc_advanced_tools_export' );
$_POST = at_input( array( 'status' => 'unregistered' ) );
at_denied( 400 );
$_POST = at_input();
add_filter( 'woocommerce_order_query', $failed_query );
at_denied( 500 );
remove_filter( 'woocommerce_order_query', $failed_query );

// Real oversized result through the selected WooCommerce datastore, not a query mock.
for ( $i = 0; $i <= Export::MAX_ORDERS; ++$i ) {
	$order = new WC_Order();
	$order->set_date_created( '2021-01-01T12:00:00+00:00' );
	$order->set_status( 'pending' );
	$order->save();
}
$large = Export::query_args( at_input( array( 'start_date' => '2021-01-01', 'end_date' => '2021-01-01', 'status' => 'all' ) ) );
at_throws( static fn() => Export::build_csv( $large ), LengthException::class );
$_POST = array( 'start_date' => '2021-01-01', 'end_date' => '2021-01-01', 'status' => 'all' );
at_denied( 400 );
WP_CLI::success( "$mode: real storage/date/DST/status/empty/oversize, CSV formulas/quoting, permission/nonce/failure and read-only checks passed." );
