<?php
namespace uncanny_learndash_reporting;

/**
 *
 */
class Cache {
	/**
	 * Create Cache
	 *
	 * @param $key
	 * @param $data
	 */
	public static function create( $key, $data ) {

		if ( isset( $_GET['group_id'] ) ) {
			$key .= '_' . $_GET['group_id'];
		} else {
			$key .= '_all';
		}

		wp_cache_set( $key, $data, 'tincanny-reporting', 60 * HOUR_IN_SECONDS );
	}

	/**
	 * Get Cache
	 *
	 * @param $key
	 *
	 * @return array|false|mixed
	 */
	public static function get( $key ) {
		$cache_disabled_by_filter = apply_filters( 'uo_tincanny_reporting_disable_cache', false );

		if ( true === $cache_disabled_by_filter ) {
			return array();
		}

		if ( 'cached' !== get_option( 'tincanny_user_report_report_mode' ) ) {
			return array();
		}

		if ( isset( $_GET['group_id'] ) ) {
			$key .= '_' . $_GET['group_id'];
		} else {
			$key .= '_all';
		}

		return wp_cache_get( $key, 'tincanny-reporting' );
	}
}
