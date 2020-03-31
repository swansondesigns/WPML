<?php
/**
 * Envira Cache Functions.
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

/**
 * Helper method to flush gallery caches once a gallery is updated.
 *
 * @since 1.0.0
 *
 * @param int    $post_id The current post ID.
 * @param string $slug The unique gallery slug.
 */
function envira_flush_gallery_caches( $post_id, $slug = '' ) {

	// Delete known gallery caches.
	delete_transient( '_eg_cache_' . $post_id );
	delete_transient( '_eg_cache_all' );
	delete_transient( '_eg_fragment_' . $post_id );
	delete_transient( '_eg_fragment_mobile_' . $post_id );

	delete_transient( '_eg_fragment_json_' . $post_id );

	// Possibly delete slug gallery cache if available.
	if ( ! empty( $slug ) ) {
		delete_transient( '_eg_cache_' . $slug );
	}

	// Run a hook for Addons to access.
	do_action( 'envira_gallery_flush_caches', $post_id, $slug );

}

/**
 * Helper method to flush all cache.
 *
 * @since 1.8.0
 *
 * @return void
 */
function envira_flush_all_cache() {

	global $wpdb;
	$transient_pattern_1 = '_transient__eg_%';
	$transient_pattern_2 = '_transient_timeout__eg__%';
	$transient_pattern_3 = '_transient_timeout__eg_%';

	$query = $wpdb->get_results( $wpdb->prepare( "SELECT option_name AS name, option_value AS value FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s", $transient_pattern_1, $transient_pattern_2, $transient_pattern_3 ), OBJECT ); // @codingStandardsIgnoreLine

	foreach ( $query as $result ) {

		$transient = $result->name;

		$key = str_replace( '_transient_timeout_', '', $transient );
		$key = str_replace( '_transient_', '', $transient );

		if ( wp_using_ext_object_cache() ) {

			if ( function_exists( 'wp_cache_delete' ) ) {

				wp_cache_delete( $key, 'transient' );

			}
		} else {

			delete_transient( $key );

		}
	}

}

add_filter( 'envira_gallery_get_transient_markup', 'envira_troubleshoot_turn_off_gallery_transients', 10, 1 );

/**
 * Helper Method to troulbeshoot Gallery
 *
 * !!! Todo move to envira-albums
 *
 * @since 1.8.5
 *
 * @param string $transient Transient String.
 * @return bool|string
 */
function envira_troubleshoot_turn_off_gallery_transients( $transient ) {

	if ( get_option( 'eg_t_gallery_status' ) === true ) {
		return false;
	} else {
		return $transient;
	}

}
add_filter( 'envira_albums_get_transient_markup', 'envira_troubleshoot_turn_off_album_transients', 10, 1 );

/**
 * Helper Method to troulbeshoot Albums
 *
 * !!! Todo move to envira-albums
 *
 * @since 1.8.5
 *
 * @param string $transient Transient String.
 * @return bool|string
 */
function envira_troubleshoot_turn_off_album_transients( $transient ) {

	if ( get_option( 'eg_t_album_status' ) === true ) {
		return false;
	} else {
		return $transient;
	}

}
