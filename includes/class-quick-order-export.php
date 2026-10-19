<?php
namespace YoOhw\AdvancedTools;

defined( 'ABSPATH' ) || exit;

final class Quick_Order_Export {
	const MAX_ORDERS = 1000;
	const MAX_BYTES  = 2097152;
	const MAX_SECONDS = 15;

	public static function available() {
		return function_exists( 'wc_get_orders' ) && defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '9.0', '>=' );
	}

	public static function register_menu() {
		if ( self::available() ) {
			add_submenu_page( 'woocommerce', __( 'Advanced Tools', 'wc-advanced-tools' ), __( 'Advanced Tools', 'wc-advanced-tools' ), 'manage_woocommerce', 'wc-advanced-tools', array( __CLASS__, 'render_page' ) );
		} else {
			add_management_page( __( 'Advanced Tools', 'wc-advanced-tools' ), __( 'Advanced Tools', 'wc-advanced-tools' ), 'activate_plugins', 'wc-advanced-tools', array( __CLASS__, 'render_page' ) );
		}
	}

	public static function render_page() {
		if ( ! current_user_can( self::available() ? 'manage_woocommerce' : 'activate_plugins' ) ) {
			wp_die( esc_html__( 'You are not allowed to use Advanced Tools.', 'wc-advanced-tools' ), '', array( 'response' => 403 ) );
		}
		echo '<div class="wrap"><h1>' . esc_html__( 'Advanced Tools', 'wc-advanced-tools' ) . '</h1>';
		if ( ! self::available() ) {
			echo '<p>' . esc_html__( 'Quick Order Export requires WooCommerce 9.0 or newer to be installed and active.', 'wc-advanced-tools' ) . '</p></div>';
			return;
		}
		$today = current_datetime();
		?>
		<h2><?php esc_html_e( 'Quick Order Export', 'wc-advanced-tools' ); ?></h2>
		<p><?php esc_html_e( 'Download order ID, public order number, creation date, status, currency and total. Customer details and line items are excluded. Orders are not changed.', 'wc-advanced-tools' ); ?></p>
		<p><?php esc_html_e( 'Choose creation dates in the store timezone (inclusive). Maximum 1,000 orders per download; narrow the dates or status for larger exports. No matches produces a CSV with column headings only.', 'wc-advanced-tools' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="wc_advanced_tools_export">
			<?php wp_nonce_field( 'wc_advanced_tools_export' ); ?>
			<table class="form-table" role="presentation">
				<tr><th><label for="at-start"><?php esc_html_e( 'From date', 'wc-advanced-tools' ); ?></label></th><td><input id="at-start" name="start_date" type="date" required value="<?php echo esc_attr( $today->modify( '-29 days' )->format( 'Y-m-d' ) ); ?>"></td></tr>
				<tr><th><label for="at-end"><?php esc_html_e( 'Through date', 'wc-advanced-tools' ); ?></label></th><td><input id="at-end" name="end_date" type="date" required value="<?php echo esc_attr( $today->format( 'Y-m-d' ) ); ?>"></td></tr>
				<tr><th><label for="at-status"><?php esc_html_e( 'Order status', 'wc-advanced-tools' ); ?></label></th><td><select id="at-status" name="status">
					<option value="all"><?php esc_html_e( 'All registered order statuses', 'wc-advanced-tools' ); ?></option>
					<?php foreach ( wc_get_order_statuses() as $status => $label ) : ?>
						<option value="<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select></td></tr>
			</table>
			<?php submit_button( __( 'Download CSV', 'wc-advanced-tools' ) ); ?>
		</form></div>
		<?php
	}

	/** Validate before querying; timestamps preserve store-timezone day boundaries on both stores. */
	public static function query_args( array $input ) {
		$dates = array();
		foreach ( array( 'start_date', 'end_date' ) as $key ) {
			$value = $input[ $key ] ?? null;
			if ( ! is_string( $value ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/D', $value ) ) {
				throw new \InvalidArgumentException( __( 'Enter valid start and end dates (YYYY-MM-DD).', 'wc-advanced-tools' ) );
			}
			$date = \DateTimeImmutable::createFromFormat( '!Y-m-d', $value, wp_timezone() );
			if ( ! $date || $date->format( 'Y-m-d' ) !== $value ) {
				throw new \InvalidArgumentException( __( 'Enter valid start and end dates (YYYY-MM-DD).', 'wc-advanced-tools' ) );
			}
			$dates[ $key ] = $date;
		}
		if ( $dates['start_date'] > $dates['end_date'] ) {
			throw new \InvalidArgumentException( __( 'The start date must be on or before the end date.', 'wc-advanced-tools' ) );
		}
		$status   = $input['status'] ?? null;
		$statuses = array_keys( wc_get_order_statuses() );
		if ( ! is_string( $status ) || ( 'all' !== $status && ! in_array( $status, $statuses, true ) ) ) {
			throw new \InvalidArgumentException( __( 'Choose a registered order status or all statuses.', 'wc-advanced-tools' ) );
		}
		return array(
			'type'         => 'shop_order',
			'status'       => 'all' === $status ? $statuses : array( $status ),
			'date_created' => $dates['start_date']->getTimestamp() . '...' . ( $dates['end_date']->modify( '+1 day' )->getTimestamp() - 1 ),
			'orderby'      => 'ID',
			'order'        => 'ASC',
			'return'       => 'ids',
			'limit'        => self::MAX_ORDERS + 1,
			'paginate'     => false,
		);
	}

	/** Keep spreadsheet formulas inert, including formulas after leading ASCII whitespace. */
	public static function csv_cell( $value ) {
		$value = (string) $value;
		if ( strlen( $value ) > 4096 ) {
			throw new \RuntimeException( 'Export field too large.' );
		}
		return preg_match( '/^[\x00-\x20]*[=+\-@]|^[\t\r\n]/', $value ) ? "'" . $value : $value;
	}

	private static function write_row( $stream, array $row ) {
		if ( false === fputcsv( $stream, array_map( array( __CLASS__, 'csv_cell' ), $row ), ',', '"', '', "\r\n" ) || ftell( $stream ) > self::MAX_BYTES ) {
			throw new \RuntimeException( 'CSV could not be completed within the size limit.' );
		}
	}

	/** Build privately before sending any download headers, so failures cannot look like a complete CSV. */
	public static function build_csv( array $args ) {
		global $wpdb;
		$started = microtime( true );
		$ids     = wc_get_orders( $args );
		if ( ! is_array( $ids ) || $wpdb->last_error ) {
			throw new \RuntimeException( 'Order query failed.' );
		}
		if ( count( $ids ) > self::MAX_ORDERS ) {
			throw new \LengthException( __( 'More than 1,000 orders match. Narrow the date range or status and try again. No CSV was downloaded.', 'wc-advanced-tools' ) );
		}
		$stream = fopen( 'php://temp/maxmemory:2097152', 'w+b' );
		if ( false === $stream ) {
			throw new \RuntimeException( 'Cannot prepare CSV.' );
		}
		try {
			self::write_row( $stream, array( 'order_id', 'order_number', 'created_at', 'status', 'currency', 'order_total' ) );
			foreach ( $ids as $id ) {
				if ( microtime( true ) - $started > self::MAX_SECONDS ) {
					throw new \RuntimeException( 'Export time limit exceeded.' );
				}
				$order = wc_get_order( $id );
				if ( ! $order instanceof \WC_Order || 'shop_order' !== $order->get_type() || ! $order->get_date_created( 'edit' ) || $wpdb->last_error ) {
					throw new \RuntimeException( 'An order could not be read.' );
				}
				$created = clone $order->get_date_created( 'edit' );
				self::write_row( $stream, array(
					$order->get_id(),
					$order->get_order_number(),
					$created->setTimezone( wp_timezone() )->format( 'Y-m-d\TH:i:sP' ),
					$order->get_status( 'edit' ),
					$order->get_currency( 'edit' ),
					$order->get_total( 'edit' ),
				) );
			}
			if ( microtime( true ) - $started > self::MAX_SECONDS || ! rewind( $stream ) ) {
				throw new \RuntimeException( 'Export could not be completed.' );
			}
			return $stream;
		} catch ( \Throwable $error ) {
			fclose( $stream );
			throw $error;
		}
	}

	public static function download() {
		nocache_headers();
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to export orders.', 'wc-advanced-tools' ), '', array( 'response' => 403 ) );
		}
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			wp_die( esc_html__( 'Use the export form to request a download.', 'wc-advanced-tools' ), '', array( 'response' => 405 ) );
		}
		check_admin_referer( 'wc_advanced_tools_export' );
		if ( ! self::available() ) {
			wp_die( esc_html__( 'Quick Order Export requires WooCommerce 9.0 or newer to be installed and active.', 'wc-advanced-tools' ), '', array( 'response' => 503 ) );
		}
		try {
			$args   = self::query_args( wp_unslash( $_POST ) );
			$stream = self::build_csv( $args );
		} catch ( \InvalidArgumentException | \LengthException $error ) {
			wp_die( esc_html( $error->getMessage() ), '', array( 'response' => 400, 'back_link' => true ) );
		} catch ( \Throwable $error ) {
			wp_die( esc_html__( 'Export failed. No CSV was downloaded. Try a smaller date range; if it still fails, ask your administrator to check the store. No orders were changed.', 'wc-advanced-tools' ), '', array( 'response' => 500, 'back_link' => true ) );
		}
		if ( headers_sent() || ob_get_length() ) {
			fclose( $stream );
			wp_die( esc_html__( 'The download could not start because another component sent output. No orders were changed.', 'wc-advanced-tools' ) );
		}
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="wc-advanced-tools-orders.csv"' );
		header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Length: ' . fstat( $stream )['size'] );
		fpassthru( $stream );
		fclose( $stream );
		exit;
	}
}
