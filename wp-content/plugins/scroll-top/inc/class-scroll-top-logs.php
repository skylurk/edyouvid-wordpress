<?php

class Scroll_Top_Logs {
	private static $logs_dir = ST_PATH . '/logs';

	public static function log( $str ) {
		if ( ! file_exists( self::$logs_dir ) ) {
			if ( ! mkdir( $concurrentDirectory = self::$logs_dir ) && ! is_dir( $concurrentDirectory ) ) {
				wp_die( sprintf( 'Directory "%s" was not created', $concurrentDirectory ) );
			}
		}

		if ( $fh = @fopen( trailingslashit( self::$logs_dir ) . "/log-" . date( 'Y-m-d' ) . ".log", 'ab' ) ) {
			$time = date( 'Y-m-d H:i:s' );

			fwrite( $fh, "[== $time ==] $str" . "\n" );
			fclose( $fh );

			return $str;
		}
	}
}