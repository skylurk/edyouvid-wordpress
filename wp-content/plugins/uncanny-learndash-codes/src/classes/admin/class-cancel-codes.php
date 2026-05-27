<?php

namespace uncanny_learndash_codes;

/**
 * Class CancelCodes
 *
 * @package uncanny_learndash_codes
 */
class CancelCodes extends Config {

	/**
	 * @var array|null|object
	 */
	public static $successfull_codes;

	/**
	 * @var array|null|object
	 */
	public static $failed_codes;

	/**
	 * @var mixed|void
	 */
	public static $notice;

	/**
	 * @var mixed|void
	 */
	public static $used_codes;

	/**
	 * @var mixed|void
	 */
	public static $invalid_codes;
	/**
	 * @var
	 */
	public static $already_cancelled;

	/**
	 * @var mixed|void
	 */
	public static $cancelled_codes;

	/**
	 * CancelCodes constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'process_submit' ), 9 );
	}


	/**
	 * @return void
	 */
	public static function success_notice() {
		?>
		<div class="updated notice">
			<h2>
				<strong><?php echo __( 'Upload summary', 'uncanny-learndash-codes' ); ?></strong>
			</h2>
			<div class="uc_cancelled_codes">
				<?php
				if ( self::$cancelled_codes ) {
					echo sprintf( '<h3><strong>%d</strong> %s</h3>', count( self::$cancelled_codes ), __( 'codes were successfully cancelled:', 'uncanny-learndash-codes' ) );
					echo '<pre>';
					foreach ( self::$cancelled_codes as $cancelled_code ) {
						echo $cancelled_code . '<br />';
					}
					echo '</pre>';
				}
				?>
			</div>
			<div class="uc_invalid_codes">
				<?php
				if ( ! empty( self::$already_cancelled ) ) {
					echo sprintf( '<h4><strong>%d</strong> %s</h4>', count( self::$already_cancelled ), __( 'codes were already cancelled', 'uncanny-learndash-codes' ) );
					echo '<pre>';
					foreach ( self::$already_cancelled as $invalid_code ) {
						echo sprintf( '%s<br />', $invalid_code );
					}
					echo '</pre>';
				}
				if ( ! empty( self::$invalid_codes ) ) {
					echo sprintf( '<h4><strong>%d</strong> %s</h4>', count( self::$invalid_codes ), __( 'codes do not exist', 'uncanny-learndash-codes' ) );
					echo '<pre>';
					foreach ( self::$invalid_codes as $invalid_code ) {
						echo sprintf( '%s<br />', $invalid_code );
					}
					echo '</pre>';
				}
				if ( ! empty( self::$used_codes ) ) {
					echo sprintf( '<h4><strong>%d</strong> %s</h4>', count( self::$used_codes ), __( 'codes were redeemed and are cancelled now', 'uncanny-learndash-codes' ) );
					echo '<pre>';
					foreach ( self::$used_codes as $used_code ) {
						echo sprintf( '%s<br/>', $used_code );
					}
					echo '</pre>';
				}
				?>
			</div>

		</div>
		<?php
	}

	/**
	 *
	 */
	public static function failed_notice() {
		$message = '<h2>' . self::$notice . '</h2>';
		?>
		<div class="notice notice-error is-dismissible">
			<?php echo $message; ?>
		</div>
		<?php
	}

	/**
	 *
	 */
	public function process_submit() {

		if ( SharedFunctionality::ulc_filter_has_var( '_cancel_codes_wpnonce', INPUT_POST ) && wp_verify_nonce( SharedFunctionality::ulc_filter_input( '_cancel_codes_wpnonce', INPUT_POST ), Config::get_project_name() ) ) {

			if ( isset( $_FILES['cancel_codes_csv'] ) ) {

				if ( ! function_exists( 'wp_handle_upload' ) ) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
				}

				$allowed  = array( 'csv' );
				$filename = $_FILES['cancel_codes_csv']['name'];
				$ext      = pathinfo( $filename, PATHINFO_EXTENSION );
				if ( ! in_array( $ext, $allowed ) ) {
					self::$notice = esc_html__( 'Invalid file format uploaded. Only CSV file type is allowed.', 'uncanny-learndash-codes' );
					add_action(
						'admin_notices',
						array(
							__CLASS__,
							'failed_notice',
						)
					);

					return;
				}

				$uploaded = move_uploaded_file( $_FILES['cancel_codes_csv']['tmp_name'], WP_CONTENT_DIR . '/uploads/cancel_codes.csv' );

				// Error checking using WP functions
				if ( ! $uploaded ) {
					self::$notice = esc_html__( 'Something went wrong! Please re-upload the file again.', 'uncanny-learndash-codes' );
					add_action(
						'admin_notices',
						array(
							__CLASS__,
							'failed_notice',
						)
					);
				} else {

					$path = WP_CONTENT_DIR . '/uploads/cancel_codes.csv';

					$file       = fopen( $path, 'r' );
					$index_zero = fgetcsv( $file );

					if ( is_array( $index_zero ) && isset( $index_zero ) ) {
						if (
							isset( $index_zero[0] )
							&& ! in_array(
								strtolower( $index_zero[0] ),
								array(
									'code',
									'codes',
								),
								true
							)
						) {
							self::$notice = esc_html__( 'The required "Code" header row is missing from the CSV. Please verify the CSV before uploading it again.', 'uncanny-learndash-codes' );
							add_action(
								'admin_notices',
								array(
									__CLASS__,
									'failed_notice',
								)
							);
						}

						if (
							isset( $index_zero[0] )
							&& in_array(
								strtolower( $index_zero[0] ),
								array(
									'code',
									'codes',
								),
								true
							)
						) {
							$max_rows_per_run = apply_filters( 'ulc_cancel_codes_max_per_batch', 2000 );
							$row              = 0;
							while ( ( $data = fgetcsv( $file, $max_rows_per_run, ',' ) ) !== false ) {
								$row ++;
							}
							fclose( $file );

							if ( absint( $row ) > $max_rows_per_run ) {
								self::$notice = printf( esc_html( 'The CSV file has too many rows. Please decrease rows to %d per upload.', 'uncanny-learndash-codes' ), $max_rows_per_run );
								add_action(
									'admin_notices',
									array(
										__CLASS__,
										'failed_notice',
									)
								);

								return;
							}

							$file            = fopen( $path, 'r' );
							$i               = 0;
							$codes_to_cancel = array();
							while ( $row = fgetcsv( $file, $max_rows_per_run, ',' ) ) {
								if ( $i !== 0 ) {
									$code_to_cancel    = trim( $row[0] );
									$codes_to_cancel[] = $code_to_cancel;
								}
								$i ++;
							}

							if ( isset( $codes_to_cancel ) && is_array( $codes_to_cancel ) ) {
								self::codes_cancelled( $codes_to_cancel );
							}
						}
					}
					fclose( $file );
				}
			} else {
				self::$notice = esc_html__( 'File object is not found', 'uncanny-learndash-codes' );
				add_action(
					'admin_notices',
					array(
						__CLASS__,
						'failed_notice',
					)
				);
			}
		}
	}


	/**
	 * @param $codes
	 */
	public static function codes_cancelled( $codes ) {
		global $wpdb;

		$tbl_codes = $wpdb->prefix . Config::$tbl_codes;
		$tbl_usage = $wpdb->prefix . Config::$tbl_codes_usage;

		if ( ! is_array( $codes ) || empty( $codes ) ) {
			self::$notice = esc_html__( 'No codes were found to cancel.', 'uncanny-learndash-codes' );
			add_action(
				'admin_notices',
				array(
					__CLASS__,
					'failed_notice',
				)
			);

			return;
		}
		$used_codes_db = $wpdb->get_results( "SELECT `code` FROM {$tbl_codes} INNER JOIN {$tbl_usage} ON {$tbl_usage}.code_id = {$tbl_codes}.ID", ARRAY_N );

		foreach ( $codes as $code ) {
			// Is code invalid?
			$is_valid = Database::is_coupon_valid( $code );
			if ( empty( $is_valid ) ) {
				// yes, add and continue to the next one
				self::$invalid_codes[] = $code;
				continue;
			}
			if ( 0 === absint( $is_valid->is_active ) ) {
				// yes, add and continue to the next one
				self::$already_cancelled[] = $code;
				continue;
			}
			// Has code been used before?
			if ( in_array( (string) $code, $used_codes_db ) ) {
				// yes, add in the list but move on to cancel code
				self::$used_codes[] = $code;
			}
			Database::update_coupon_status( $is_valid->ID, 0 );
			self::$cancelled_codes[] = $code;
		}

		if ( ! empty( self::$cancelled_codes ) ) {
			self::$notice = esc_html__( 'Codes cancelled successfully.', 'uncanny-learndash-codes' );
			add_action(
				'admin_notices',
				array(
					__CLASS__,
					'success_notice',
				)
			);

			return;
		}
		self::$notice = esc_html__( 'Uploaded codes were already cancelled or are invalid.', 'uncanny-learndash-codes' );
		add_action(
			'admin_notices',
			array(
				__CLASS__,
				'failed_notice',
			)
		);

	}

}
