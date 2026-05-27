<?php
/**
 * Function include all files in folder
 *
 * @param $path   Directory address
 * @param $ext    array file extension what will include
 * @param $prefix string Class prefix
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! function_exists( 'villatheme_include_folder' ) ) {
	function villatheme_include_folder( $path, $prefix = '', $ext = array( 'php' ) ) {
		/*Include all files in payment folder*/
		if ( ! is_array( $ext ) ) {
			$ext = explode( ',', $ext );
			$ext = array_map( 'trim', $ext );
		}
		$sfiles = scandir( $path );
		foreach ( $sfiles as $sfile ) {
			if ( $sfile != '.' && $sfile != '..' ) {
				if ( is_file( $path . "/" . $sfile ) ) {
					$ext_file  = pathinfo( $path . "/" . $sfile );
					$file_name = $ext_file['filename'];
					if ( $ext_file['extension'] ) {
						if ( in_array( $ext_file['extension'], $ext ) ) {
							$class = preg_replace( '/\W/i', '_', $prefix . ucfirst( $file_name ) );
							if ( ! class_exists( $class ) ) {
								require_once $path . $sfile;
								if ( class_exists( $class ) ) {
									new $class;
								}
							}
						}
					}
				}
			}
		}
	}
}
if ( ! function_exists( 'villatheme_sanitize_fields' ) ) {
	function villatheme_sanitize_fields( $data ) {
		if ( is_array( $data ) ) {
			return array_map( 'villatheme_sanitize_fields', $data );
		} else {
			return is_scalar( $data ) ? sanitize_text_field( wp_unslash( $data ) ) : $data;
		}
	}
}
if ( ! function_exists( 'villatheme_sanitize_kses' ) ) {
	function villatheme_sanitize_kses( $data ) {
		if ( is_array( $data ) ) {
			return array_map( 'villatheme_sanitize_kses', $data );
		} else {
			return is_scalar( $data ) ? wp_kses_post( wp_unslash( $data ) ) : $data;
		}
	}
}
if ( ! function_exists( 'villatheme_convert_time' ) ) {
	function villatheme_convert_time( $time ) {
		if ( ! $time ) {
			return 0;
		}
		$temp = explode( ":", $time );
		if ( count( $temp ) == 2 ) {
			return ( absint( $temp[0] ) * 3600 + absint( $temp[1] ) * 60 );
		} else {
			return 0;
		}
	}
}
if ( ! function_exists( 'villatheme_revert_time' ) ) {
	function villatheme_revert_time( $time ) {
		$hour = floor( $time / 3600 );
		$min  = floor( ( $time - 3600 * $hour ) / 60 );
		return implode( ':', array( zeroise( $hour, 2 ), zeroise( $min, 2 ) ) );
	}
}
if ( ! function_exists( 'villatheme_json_decode' ) ) {
	function villatheme_json_decode( $json, $assoc = true, $depth = 512, $options = 2 ) {
		if ( function_exists( 'mb_convert_encoding' ) ) {
			$json = mb_convert_encoding( $json, 'UTF-8', 'UTF-8' );
		}
		return json_decode( is_string( $json ) ? $json : '{}', $assoc, $depth, $options );
	}
}

