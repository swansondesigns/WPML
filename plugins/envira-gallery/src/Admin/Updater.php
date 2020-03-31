<?php
/**
 * Updater class.
 *
 * @since 1.0.0
 *
 * @package Envira_Gallery
 * @author  Envira Gallery Team
 */

namespace Envira\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Updater class.
 *
 * @since 1.0.0
 *
 * @package Envira_Gallery
 * @author  Envira Gallery Team
 */
class Updater {

	/**
	 * API Url.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $api_url = 'https://enviragallery.com/wp-json/enivra-api/update_check';

	/**
	 * Plugins.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $plugins = array();

	/**
	 * Beta.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $beta = false;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.7.0
	 * @param mixed $api_key API Key.
	 */
	public function __construct( $api_key ) {

		// If the user cannot update plugins, stop processing here.
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		// Get the stored Envira Plugins.
		$envira_plugins = get_transient( '_eg_plugins' );
		$beta           = get_option( 'eg_beta', false );

		$all_plugins = get_plugins();

		error_log( print_r( $all_plugins, true ) );

		// Load the updater hooks and filters.
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );

	}

	/**
	 * Check update.
	 *
	 * @since 1.7.0
	 * @param mixed $_transient_data Transient Data.
	 */
	public function check_update( $_transient_data ) {

		global $pagenow;

		error_log( print_r( $_transient_data, true ) );

		// If no update object exists, return early.
		if ( empty( $_transient_data ) ) {
			return $_transient_data;
		}

	}

	/**
	 * HTTP Request Args.
	 *
	 * @since 1.7.0
	 */
	public function http_request_args() {

	}

	/**
	 * Plugins API.
	 *
	 * @since 1.7.0
	 */
	public function plugins_api() {

	}

	/**
	 * Set Plugins API.
	 *
	 * @since 1.7.0
	 */
	public function set_plugins_api(){}

	/**
	 * Call It.
	 *
	 * @since 1.7.0
	 */
	public function callit() {

		// Build the body of the request.
		$body           = wp_parse_args(
			$body,
			array(
				'tgm-updater-action'     => $action,
				'tgm-updater-key'        => $this->key,
				'tgm-updater-wp-version' => get_bloginfo( 'version' ),
				'tgm-updater-referer'    => site_url(),
			)
		);
		$body           = http_build_query( $body, '', '&' );
		$content_length = strlen( $body );

		// Build the headers of the request.
		$headers = wp_parse_args(
			$headers,
			array(
				'Content-Type'   => 'application/x-www-form-urlencoded',
				'Content-Length' => $content_length,
			)
		);

		// Setup variable for wp_remote_post.
		$post = array(
			'headers' => $headers,
			'body'    => $body,
		);

		// Perform the query and retrieve the response.
		$response      = wp_remote_post( esc_url_raw( $this->api_url ), $post );
		$response_code = wp_remote_retrieve_response_code( $response ); /* log this for API issues */
		$response_body = wp_remote_retrieve_body( $response );

		// Bail out early if there are any errors.
		if ( 200 !== $response_code || is_wp_error( $response_body ) ) {
			return false;
		}

		// Return the json decoded content.
		return json_decode( $response_body );

	}

	/**
	 * Cached Info
	 *
	 * @since 1.7.0
	 * @param string $cache_key Cache key.
	 */
	public function get_cached_info( $cache_key = '' ) {

		if ( empty( $cache_key ) ) {
			$cache_key = $this->cache_key;
		}

		$cache = get_option( $cache_key );

		if ( empty( $cache['timeout'] ) || current_time( 'timestamp' ) > $cache['timeout'] ) {
			return false; // Cache is expired.
		}

		return json_decode( $cache['value'] );

	}

	/**
	 * Set Cached Info
	 *
	 * @since 1.7.0
	 * @param string $value Cache value.
	 * @param string $cache_key Cache key.
	 */
	public function set_cache_info( $value = '', $cache_key = '' ) {

		if ( empty( $cache_key ) ) {
			$cache_key = $this->cache_key;
		}

		$data = array(
			'timeout' => strtotime( '+3 hours', current_time( 'timestamp' ) ),
			'value'   => wp_json_encode( $value ),
		);

		update_option( $cache_key, $data, 'no' );

	}

	/**
	 * Verify SSL
	 *
	 * @since 1.7.0
	 */
	public function verify_ssl() {
		return (bool) apply_filters( 'envira_api_request_verify_ssl', true, $this );
	}
}
