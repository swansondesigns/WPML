<?php
/**
 * Envira Utility Functions.
 *
 * @since 1.7.0
 *
 * @package Envira_Gallery
 * @author  Envira Gallery Team <support@enviragallery.com>
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

use Envira\Frontend\Background;
use Envira\Utils\Mobile_Detect;

if ( ! function_exists( 'array_column' ) ) :
	/**
	 * Fallback for array_column for < php 5.5
	 *
	 * @param array  $input Input Array.
	 * @param string $column_key Column Key.
	 * @param mixed  $index_key Key index.
	 * @return bool|mixed
	 */
	function array_column( array $input, $column_key, $index_key = null ) {

		$array = array();

		foreach ( $input as $value ) {

			if ( ! array_key_exists( $column_key, $value ) ) {

				return false;

			}

			if ( is_null( $index_key ) ) {

				$array[] = $value[ $column_key ];

			} else {

				if ( ! array_key_exists( $index_key, $value ) || ! is_scalar( $value[ $index_key ] ) ) {

					return false;

				}

				$array[ $value[ $index_key ] ] = $value[ $column_key ];

			}
		}

		return $array;

	}

endif;

/**
 * Helper Method for Size Conversions
 *
 * @author Chris Christoff
 * @since 1.7.0
 *
 * @param  unknown $v Ketter.
 * @return int|string
 */
function envira_let_to_num( $v ) {

	$l   = substr( $v, -1 );
	$ret = substr( $v, 0, -1 );

	switch ( strtoupper( $l ) ) {

		case 'P': // fall-through.
		case 'T': // fall-through.
		case 'G': // fall-through.
		case 'M': // fall-through.
		case 'K':
			$ret *= 1024;
			break;

		default:
			break;

	}

	return $ret;
}

/**
 * Helper function to detect mobile.
 *
 * @since 1.7.0
 *
 * @access public
 * @return object
 */
function envira_mobile_detect() {
	return new Envira\Utils\Mobile_Detect();
}

/**
 * Helper Method to check if whitelable is enabled.
 *
 * @since 1.7.0
 *
 * @return bool
 */
function envira_is_whitelabel() {

	return apply_filters( 'envira_whitelabel', false );
}

/**
 * Helper Method to check if is mobile.
 *
 * @return bool
 */
function envira_is_mobile() {

	if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {

		return preg_match( '/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i', sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) );

	}

	return false;
}

/**
 * Utility function for debugging
 *
 * @since 1.7.0
 *
 * @access public
 * @param array $array (default: array().
 * @return void
 */
function envira_pretty_print( $array = array() ) {

	echo '<pre> ' . print_r( $array ) . '</pre>'; // @codingStandardsIgnoreLine

}

/**
 * Helper Method to call background requests
 *
 * @since 1.7.0
 *
 * @access public
 * @param mixed  $data Data to request.
 * @param string $type Type of request.
 * @return bool
 */
function envira_background_request( $data, $type ) {

	if ( ! is_array( $data ) || ! isset( $type ) ) {

		return false;

	}

	$background = new Envira\Frontend\Background();
	$background->background_request( $data, $type );

	return true;
}

/**
 * Utility Function to log errors.
 *
 * @since 1.8.0
 *
 * @access public
 * @param string $content Content to load.
 * @param mixed  $data Data to log.
 * @return bool
 */
function envira_log_error( $content = null, $data = null ) {

	if ( ! defined( 'ENVIRA_DEBUG' ) || ! ENVIRA_DEBUG ) {

		return false;

	} else {

		if ( ! is_array( $data ) ) {

			error_log( strtoupper( $content ) . ':' . PHP_EOL . $data ); // @codingStandardsIgnoreLine

		} else {

			error_log( strtoupper( $content ) . ':' . PHP_EOL . print_r( $data, true ) ); // @codingStandardsIgnoreLine

		}
	}

}

/**
 * Utility Function to return errors.
 *
 * @since 1.8.4
 *
 * @access public
 * @param string $wp_error_id Error ID.
 * @param string $text Error Text.
 * @return WP_Error
 */
function envira_wp_error( $wp_error_id = null, $text = null ) {

	global $wp_error;

	return ! isset( $wp_error ) ? new WP_Error( $wp_error_id, $text ) : $wp_error;

}
