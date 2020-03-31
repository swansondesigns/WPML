<?php
/**
 * Frontend Container
 *
 * @since 1.7.0
 *
 * @package Envira_Gallery
 * @author  Envira Gallery Team <support@enviragallery.com>
 */

namespace Envira\Frontend;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

use Envira\Frontend\Posttypes;
use Envira\Frontend\Standalone;
use Envira\Frontend\Shortcode;
use Envira\Frontend\Background;
use Envira\Frontend\Rest;

use Envira\Widgets\Widget;
use Envira\Utils\Capabilities;

/**
 * Frontend Container
 *
 * @since 1.7.0
 *
 * @package Envira_Gallery
 * @author  Envira Gallery Team <support@enviragallery.com>
 */
class Frontend_Container {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$posttypes  = new Posttypes();
		$standalone = new Standalone();
		$shortcode  = new Shortcode();
		$background = new Background();
		$rest       = new Rest();

		/* Yoast SEO */
		add_filter( 'wpseo_sitemap_urlimages', array( $this, 'filter_wpseo_sitemap_urlimages' ), 10, 2 );

		add_filter( 'widget_text', 'do_shortcode' );

		add_filter( 'envira_gallery_shortcode_start', array( $this, 'filter_shortcode_start' ), 10, 1 );

		// Load the plugin widget.
		add_action( 'widgets_init', array( $this, 'widget' ) );

		// Load any custom admin bar additions.
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 99999 );

	}

	/**
	 * Registers the Envira Gallery widgets.
	 *
	 * @since 1.7.0
	 */
	public function widget() {

		register_widget( 'Envira\Widgets\Widget' );
		register_widget( 'Envira\Widgets\EnviraWidgetRandom' );

	}

	/**
	 * Prevents Envira shortcodes from being rendered due to third-party conflicts
	 *
	 * @since 1.8.0
	 *
	 * @param array $atts shortcode attributes.
	 *
	 * @return array        Updated Array w/ action and reason
	 */
	public function filter_shortcode_start( $atts ) {

		if ( ! is_admin() ) {
			/* these are the things to watch for the frontend... mostly just intercepting do_shortcode() attempts. */

			// All-in-One SEO Pro.
			global $aioseop_options;
			if ( isset( $aioseop_options ) && ! empty( $aioseop_options['aiosp_run_shortcodes'] ) ) {
				return ( array(
					'action' => 'warning',
					'reason' => 'All In One SEO: Must deactivate aiosp_run_shortcodes',
				) );
			}
			return;
		}

		// Prevent Yoast And Divi From Previewing, rendering JSON breaking / causing JS errors.
		if ( isset( $_REQUEST['action'] ) ) { // @codingStandardsIgnoreLine
			switch ( $_REQUEST['action'] ) { // @codingStandardsIgnoreLine
				case 'wpseo_filter_shortcodes':
					// Yoast.
					return ( array(
						'action' => 'bail',
						'reason' => $_REQUEST['action'], // @codingStandardsIgnoreLine
					) );
				case 'et_pb_execute_content_shortcodes':
					// Divi.
					return ( array(
						'action' => 'bail',
						'reason' => $_REQUEST['action'], // @codingStandardsIgnoreLine
					) );
				case 'edit':
				case 'editpost':
					// Divi + Yoast.
					if ( defined( 'WPSEO_VERSION' ) && ( function_exists( 'et_setup_theme' ) || function_exists( 'et_divi_load_scripts_styles' ) ) ) {
						return ( array(
							'action' => 'bail',
							'reason' => $_REQUEST['action'], // @codingStandardsIgnoreLine
						) );
					}
					break;
			}
		}

	}

	/**
	 * Adds custom links to admin bar
	 *
	 * @since 1.8.4
	 *
	 * @param array $wp_admin_bar Admin bar.
	 */
	public function admin_bar( $wp_admin_bar ) {

		$admin_option = get_option( 'eg_admin_bar' );
		$support_bar  = ! empty( $admin_option ) && true === $admin_option ? true : false;

		if ( $support_bar ) {

			$args = array(
				'id'     => 'envira-support',
				'title'  => '<span style="color:#FFA500">Envira Support</span>',
				'parent' => 'top-secondary',
				'href'   => admin_url(
					add_query_arg(
						array(
							'post_type' => 'envira',
							'page'      => 'envira-gallery-support-general',
						),
						'edit.php'
					)
				),
			);
			$wp_admin_bar->add_node( $args );

		}

	}

	/**
	 * Inserts images into Yoast SEO Sitexml.
	 *
	 * @since 1.7.0
	 *
	 * @param array $yoast_images Current incoming array of images.
	 * @param int   $post_id WP ID.
	 *
	 * @return array        Updated Yoast Array.
	 */
	public function filter_wpseo_sitemap_urlimages( $yoast_images, $post_id ) {

		// make filter magic happen here... if the post_id is an envira gallery or album, great. if not, go back.
		if ( ! get_post_type( $post_id ) === 'envira' && ! get_post_type( $post_id ) === 'envira_album' ) {
			return $yoast_images;
		}

		// If defaults addon is activated, make sure we returning a number of images for a dynamic or default gallery/album.
		if ( class_exists( 'Envira_Defaults' ) && ( intval( $post_id ) === intval( get_option( 'envira_default_gallery' ) ) || intval( $post_id ) === intval( get_option( 'envira_default_album' ) ) ) ) {
			return $yoast_images;
		}

		// If defaults addon is activated, make sure we returning a number of images for a dynamic or default gallery/album.
		if ( class_exists( 'Envira_Dynamic' ) && ( intval( $post_id ) === intval( get_option( 'envira_dynamic_gallery' ) ) || intval( $post_id ) === intval( get_option( 'envira_dynamic_album' ) ) ) ) {
			return $yoast_images;
		}

		if ( get_post_type( $post_id ) === 'envira' ) { // if this is a gallery get all the images and add them to the array.

			$gallery = envira_get_gallery( $post_id );
			if ( $gallery && ! empty( $gallery['gallery'] ) ) {
				foreach ( $gallery['gallery'] as $image ) {
					if ( ! empty( $image['src'] ) ) {
						$yoast_images[] = array( 'src' => $image['src'] );
					}
				}
			}
		} else { // if this is an album get all the gallerys, then images,  and add them to the array.

			if ( ! class_exists( 'Envira_Albums' ) ) {
				return $yoast_images;
			}

			$instance_albums = \ Envira_Albums::get_instance();
			$album           = $instance_albums->_get_album( $post_id );

			// go through all the galleries, limit to 50 for now to ensure most sites don't timeout.
			$counter = 0;
			if ( ! empty( $album['galleries'] ) ) {
				foreach ( $album['galleries'] as $album_gallery ) {
					if ( $counter <= 50 && ! empty( $album_gallery['id'] ) ) {

						$gallery = envira_get_gallery( $album_gallery['id'] );
						if ( $gallery && ! empty( $gallery['gallery'] ) ) {
							foreach ( $gallery['gallery'] as $image ) {
								if ( ! empty( $image['src'] ) ) {
									$yoast_images[] = array( 'src' => $image['src'] );
								}
							}
							$counter++;
						}
					}
				}
			}
		}

		return $yoast_images;
	}

}
