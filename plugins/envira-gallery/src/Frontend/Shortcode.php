<?php
/**
 * Shortcode class.
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

/**
 * Envira Gallery Shortcode Class.
 *
 * @since 1.7.0
 */
class Shortcode {

	/**
	 * Holds the gallery data.
	 *
	 * @since 1.7.0
	 *
	 * @var array
	 */
	public $data;

	/**
	 * Iterator for galleries on the page.
	 *
	 * @since 1.7.0
	 *
	 * @var int
	 */
	public $counter = 1;

	/**
	 * Array of gallery ids on the page.
	 *
	 * @since 1.7.0
	 *
	 * @var array
	 */
	public $gallery_ids = array();

	/**
	 * Array of gallery item ids on the page.
	 *
	 * @since 1.7.0
	 *
	 * @var array
	 */
	public $gallery_item_ids = array();

	/**
	 * Holds image URLs for indexing.
	 *
	 * @since 1.7.0
	 *
	 * @var array
	 */
	public $index = array();

	/**
	 * Holds the sort order of the gallery for addons like Pagination
	 *
	 * @since 1.5.6
	 *
	 * @var array
	 */
	public $gallery_sort = array();

	/**
	 * Gallery data
	 *
	 * (default value: array())
	 *
	 * @var array
	 * @access public
	 */
	public $gallery_data = array();

	/**
	 * Link Data
	 *
	 * @var mixed
	 * @access public
	 */
	public $link_data = array();

	/**
	 * Is mobile
	 *
	 * @var mixed
	 * @access public
	 */
	public $is_mobile;

	/**
	 * Item
	 *
	 * @var mixed
	 * @access public
	 */
	public $item;

	/**
	 * Gallery markup
	 *
	 * @var mixed
	 * @access public
	 */
	public $gallery_markup;

	/**
	 * Dynamic images
	 *
	 * @var mixed
	 * @access public
	 */
	public $dynamic_images = false;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.7.0
	 */
	public function __construct() {

		$this->is_mobile = envira_mobile_detect()->isMobile();

		$this->init();
	}

	/**
	 * Init.
	 *
	 * @since 1.7.0
	 */
	public function init() {

		if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			// if it's the admin BUT it's not admin ajax from the frontend, then return.
			return;
		}

		// Load hooks and filters.
		add_action( 'init', array( &$this, 'register_scripts' ) );

		add_shortcode( 'envira-gallery', array( &$this, 'shortcode' ) );
		add_shortcode( 'envira-link', array( &$this, 'shortcode_link' ) );

		add_filter( 'style_loader_tag', array( $this, 'add_stylesheet_property_attribute' ) );
		add_action( 'envira_gallery_output_caption', array( $this, 'gallery_image_caption_titles' ), 10, 5 );

		add_filter( 'envirabox_gallery_thumbs_position', array( $this, 'envirabox_gallery_thumbs_position' ), 10, 2 );
		add_filter( 'envirabox_dynamic_margin', array( $this, 'envirabox_dynamic_margin' ), 10, 2 );
		add_filter( 'envira_gallery_title_type', array( $this, 'envira_gallery_title_type' ), 10, 2 );
		add_filter( 'envira_gallery_output_before_container', array( $this, 'envira_add_gallery_description_above' ), 1, 2 );
		add_filter( 'envira_gallery_output_end', array( $this, 'envira_add_gallery_description_below' ), 1, 2 );

	}

	/**
	 * Register scripts
	 *
	 * @since 1.7.0
	 *
	 * @access public
	 * @return void
	 */
	public function register_scripts() {

		$version = ( defined( 'ENVIRA_DEBUG' ) && 'true' === ENVIRA_DEBUG ) ? $version = time() . '-' . ENVIRA_VERSION : ENVIRA_VERSION;

		// Register main gallery style.
		wp_register_style( ENVIRA_SLUG . '-style', plugins_url( 'assets/css/envira.css', ENVIRA_FILE ), array(), $version );

		// Register Justified Gallery style.
		wp_register_style( ENVIRA_SLUG . '-jgallery', plugins_url( 'assets/css/justifiedGallery.css', ENVIRA_FILE ), array(), ENVIRA_VERSION );

		// Register main gallery script.
		wp_register_script( ENVIRA_SLUG . '-script', plugins_url( 'assets/js/min/envira-min.js', ENVIRA_FILE ), array( 'jquery' ), $version, true );

		// Run a hook so that third party plugins can add additional JS scripts only for Envira.
		do_action( 'envira_gallery_after_register_scripts', $version );
	}

	/**
	 * I'm sure some plugins mean well, but they go a bit too far trying to reduce
	 * conflicts without thinking of the consequences.
	 *
	 * 1. Prevents Foobox from completely borking envirabox as if Foobox rules the world.
	 *
	 * @since 1.7.0
	 */
	public function plugin_humility() {

		if ( class_exists( 'fooboxV2' ) ) {

			remove_action( 'wp_footer', array( $GLOBALS['foobox'], 'disable_other_lightboxes' ), 200 );

		}

	}

	/**
	 * Creates the shortcode for the plugin.
	 *
	 * @since 1.7.0
	 *
	 * @global object $post The current post object.
	 *
	 * @param array $atts Array of shortcode attributes.
	 * @return string        The gallery output.
	 */
	public function shortcode( $atts ) {

		if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			// if it's the admin BUT it's not admin ajax from the frontend, then return.
			return;
		}

		// hook that would allow any initial checks and bails (such as yoast snippet previews).
		$envira_shortcode_start = apply_filters( 'envira_gallery_shortcode_start', true, $atts );
		// for warning or bails, we add a note in server logs if ENVIRA_DEBUG is true.
		if ( 'bail' === $envira_shortcode_start['action'] || 'warning' === $envira_shortcode_start['action'] ) {
			if ( defined( 'ENVIRA_DEBUG' ) && 'true' === ENVIRA_DEBUG ) {
				error_log( 'envira_shortcode_start - bail' ); // @codingStandardsIgnoreLine
				error_log( print_r( $envira_shortcode_start, true ) ); // @codingStandardsIgnoreLine
			}
		}
		// for bails, we bail.
		if ( 'bail' === $envira_shortcode_start['action'] ) {
			return;
		}

		// Run a hook before the gallery output begins but after scripts and inits have been set.
		do_action( 'envira_gallery_start_shortcode', $atts );

		// If no attributes have been passed, the gallery should be pulled from the current post.
		global $post;
		$gallery_id         = false;
		$gallery_images_raw = false;
		$gallery_type       = false;

		if ( empty( $atts ) ) {

			$gallery_id = $post->ID;
			$data       = is_preview() ? _envira_get_gallery( $gallery_id ) : envira_get_gallery( $gallery_id );

		} elseif ( isset( $atts['id'] ) && isset( $atts['type'] ) ) {

			// new filter for being able to maniuplate data for custom scenarios (widgets, Gutenberg, alien attacks, etc).
			$gallery_id   = (int) $atts['id'];
			$gallery_type = (string) $atts['type'];
			$data         = apply_filters( 'envira_gallery_custom_gallery_data_by_' . $gallery_type, _envira_get_gallery( $gallery_id ), $atts, $post, $gallery_id );

		} elseif ( isset( $atts['id'] ) && ! isset( $atts['dynamic'] ) ) {

			$gallery_id = (int) $atts['id'];
			$data       = is_preview() ? _envira_get_gallery( $gallery_id ) : envira_get_gallery( $gallery_id );

		} elseif ( isset( $atts['slug'] ) ) {

			$gallery_id = $atts['slug'];
			$data       = is_preview() ? _envira_get_gallery_by_slug( $gallery_id ) : envira_get_gallery_by_slug( $gallery_id );
			// we have the gallery data, now just translate slug into the ID.
			if ( intval( $data['id'] ) ) {
				$gallery_id = intval( $data['id'] );
			}
		} else {

			// a custom attribute must have been passed. Allow it to be filtered to grab data from a custom source.
			$data = apply_filters( 'envira_gallery_custom_gallery_data', false, $atts, $post );

			$gallery_id = $data['config']['id'];
			if ( ! empty( $data['gallery_images_raw '] ) ) {
				$gallery_images_raw = $data['gallery_images_raw'];
			}
			if ( ! empty( $data['gallery'] ) ) {
				$this->dynamic_images = $data['gallery'];
			}
		}

		// Check if we've passed the cahce atts.
		$should_cache = isset( $atts['cache'] ) ? filter_var( $atts['cache'], FILTER_VALIDATE_BOOLEAN ) : true;

		// Don't cache if limit is set.
		if ( isset( $atts['limit'] ) ) {
			$should_cache = false;
		}

		// Allow the data to be filtered before it is stored and used to create the gallery output.
		$flush_cache = ( false === $should_cache ) ? true : false;
		$data        = apply_filters( 'envira_gallery_pre_data', $data, $gallery_id, $flush_cache );

		// If there is no data to output or the gallery is inactive, do nothing.
		if ( ! $data || empty( $data['gallery'] ) || isset( $data['status'] ) && 'inactive' === $data['status'] && ! is_preview() ) {
			return;
		}

		// Lets check if this gallery has already been output on the page.
		$data['gallery_id'] = ( isset( $data['config']['type'] ) && 'dynamic' === $data['config']['type'] && isset( $data['config']['id'] ) ) ? $data['config']['id'] : $data['id'];
		$main_id            = ( isset( $data['dynamic_id'] ) ) ? $data['dynamic_id'] : $data['id'];

		if ( ! empty( $atts['counter'] ) ) {

			// we are forcing a counter so lets force the object in the gallery_ids.
			$this->counter       = $atts['counter'];
			$this->gallery_ids[] = $data['id'];

		}

		if ( ! empty( $data['id'] ) && ! in_array( $data['id'], $this->gallery_ids, true ) ) {

			$this->gallery_ids[] = $data['id'];

		} elseif ( $this->counter > 1 && ! empty( $data['id'] ) ) {

			$data['id'] = $data['id'] . '_' . $this->counter;

		}

		if ( ! empty( $data['id'] ) && empty( $atts['presorted'] ) ) {

			$this->gallery_sort[ $data['id'] ] = false; // reset this to false, otherwise multiple galleries on the same page might get other ids, or other wackinesses.

		}

		// Limit the number of images returned, if specified.
		// [envira-gallery id="123" limit="10"] would only display 10 images.
		if ( isset( $atts['limit'] ) && is_numeric( $atts['limit'] ) ) {

			// check for existence of gallery, if there's nothing it could be an instagram or blank gallery.
			if ( ! empty( $data['gallery'] ) ) {

				$images          = array_slice( $data['gallery'], 0, absint( $atts['limit'] ), true );
				$data['gallery'] = $images;

			}
		}

		// This filter detects if something needs to be displayed BEFORE a gallery is displayed, such as a password form.
		$pre_gallery_html = apply_filters( 'envira_abort_gallery_output', false, $data, $gallery_id, $atts );

		if ( false !== $pre_gallery_html ) {

			// If there is HTML, then we stop trying to display the gallery and return THAT HTML.
			return apply_filters( 'envira_gallery_output', $pre_gallery_html, $data );

		}

		$this->gallery_data = $data;

		// If this is a feed view, customize the output and return early.
		if ( is_feed() ) {

			return $this->do_feed_output( $this->gallery_data );

		}

		// Get rid of any external plugins trying to jack up our stuff where a gallery is present.
		$this->plugin_humility();

		// Prepare variables.
		$this->index[ $this->gallery_data['id'] ] = array();
		$this->gallery_markup                     = '';
		$i                                        = 1;

		// Load scripts and styles.
		wp_enqueue_style( ENVIRA_SLUG . '-style' );

		wp_enqueue_style( ENVIRA_SLUG . '-jgallery' );

		wp_enqueue_script( ENVIRA_SLUG . '-script' );

		wp_localize_script(
			ENVIRA_SLUG . '-script',
			'envira_gallery',
			array(
				'debug'      => ( defined( 'ENVIRA_DEBUG' ) && ENVIRA_DEBUG ? true : false ),
				'll_delay'   => isset( $this->gallery_data['config']['lazy_loading_delay'] ) ? intval( $this->gallery_data['config']['lazy_loading_delay'] ) : 500,
				'll_initial' => 'false',
				'll'         => envira_get_config( 'lazy_loading', $data ) === 1 ? 'true' : 'false',
				'mobile'     => $this->is_mobile,

			)
		);

		// Load custom gallery themes if necessary.
		if ( 'base' !== envira_get_config( 'gallery_theme', $this->gallery_data ) && envira_get_config( 'columns', $this->gallery_data ) > 0 ) {

			// if columns is zero, then it's automattic which means we do not load gallery themes because it will mess up the new javascript layout.
			envira_load_gallery_theme( envira_get_config( 'gallery_theme', $this->gallery_data ) );

		}

		// Load custom lightbox themes if necessary, don't load if user hasn't enabled lightbox.
		if ( envira_get_config( 'lightbox_enabled', $this->gallery_data ) ) {

			envira_load_lightbox_theme( envira_get_config( 'lightbox_theme', $this->gallery_data ) );

		}

		// Run a hook before the gallery output begins but after scripts and inits have been set.
		do_action( 'envira_gallery_before_output', $this->gallery_data );

		// Allow caching to be filtered here, some addons might force caching off regardless of any other situations.
		$should_cache = apply_filters( 'envira_gallery_should_cache', $should_cache, $this->gallery_data );

		if ( $this->is_mobile ) {

			$markup = apply_filters( 'envira_gallery_get_transient_markup', get_transient( '_eg_fragment_mobile_' . $data['gallery_id'] ), $this->gallery_data );

		} else {

			$markup = apply_filters( 'envira_gallery_get_transient_markup', get_transient( '_eg_fragment_' . $data['gallery_id'] ), $this->gallery_data );

		}

		if ( $markup && $should_cache && ( ! defined( 'ENVIRA_DEBUG' ) || ! ENVIRA_DEBUG ) ) {

			$this->gallery_markup = $markup;

		} else {

			$this->gallery_markup = apply_filters( 'envira_gallery_output_start', $this->gallery_markup, $this->gallery_data ); // Apply a filter before starting the gallery HTML.
			$schema_microdata     = apply_filters( 'envira_gallery_output_shortcode_schema_microdata', 'itemscope itemtype="http://schema.org/ImageGallery"', $this->gallery_data ); // Schema.org microdata ( Itemscope, etc. ) interferes with Google+ Sharing... so we are adding this via filter rather than hardcoding.

			// Build out the gallery HTML.
			$this->gallery_markup .= '<div id="envira-gallery-wrap-' . sanitize_html_class( $this->gallery_data['id'] ) . '" class="' . $this->get_gallery_classes( $this->gallery_data ) . '" ' . $schema_microdata . '>';
			$this->gallery_markup  = apply_filters( 'envira_gallery_output_before_container', $this->gallery_markup, $this->gallery_data );
			$temp_gallery_markup   = apply_filters( 'envira_gallery_temp_output_before_container', '', $this->gallery_data );

			$extra_css               = envira_get_config( 'columns', $this->gallery_data ) > 0 ? false : 'envira-gallery-justified-public'; // add justified CSS?
			$row_height              = false;
			$justified_gallery_theme = false;
			$justified_margins       = false;

			if ( envira_get_config( 'columns', $this->gallery_data ) > 0 ) {

				// add isotope if the user has it enabled.
				$isotope = envira_get_config( 'isotope', $this->gallery_data ) ? ' enviratope' : false;

			} else {

				$row_height              = ! $this->is_mobile ? envira_get_config( 'justified_row_height', $this->gallery_data ) : envira_get_config( 'mobile_justified_row_height', $this->gallery_data );
				$justified_gallery_theme = envira_get_config( 'justified_gallery_theme', $this->gallery_data );
				$justified_margins       = envira_get_config( 'justified_margins', $this->gallery_data );

				// this is a justified layout, no isotope even if it's selected in the DB.
				$isotope = false;

			}

			$extra_css = apply_filters( 'envira_gallery_output_extra_css', $extra_css, $this->gallery_data );

			// Grab the raw data.
			if ( 'dynamic' === $data['config']['type'] ) {
				$data['gallery'] = $this->dynamic_images;
			}

			// Make sure were grabbing the proper settings
			// Experiment: For performance reasons, pull the raw gallery image instead of calling envira_get_gallery_images twice.
			if ( false === $gallery_images_raw ) {
				$gallery_images_raw = envira_get_gallery_images( $gallery_id, true, $data, false, false, $gallery_type, false );
			}

			$fix_json = array();
			foreach ( $gallery_images_raw as $key => $value ) {
				$fix_json[] = $value;
			}

			$gallery_images_json = wp_json_encode( $fix_json, JSON_UNESCAPED_UNICODE );

			$options_id       = 'dynamic' === $data['config']['type'] ? $data['dynamic_id'] : $gallery_id;
			$gallery_config   = "data-gallery-config='" . envira_get_gallery_config( $options_id, false, $data ) . "'";
			$gallery_images   = "data-gallery-images='" . $gallery_images_json . "'";
			$lb_theme_options = "data-lightbox-theme='" . htmlentities( envira_load_lightbox_config( $main_id, false, $gallery_type ) ) . "'"; // using main id for Dynamic to make sure we load the proper data.
			$gallery_id       = 'data-envira-id="' . $gallery_id . '"';

			$temp_gallery_markup .= '<div ' . $gallery_id . ' ' . $gallery_config . ' ' . $gallery_images . ' ' . $lb_theme_options . ' data-row-height="' . $row_height . '" data-justified-margins="' . $justified_margins . '" data-gallery-theme="' . $justified_gallery_theme . '" id="envira-gallery-' . sanitize_html_class( $this->gallery_data['id'] ) . '" class="envira-gallery-public ' . $extra_css . ' envira-gallery-' . sanitize_html_class( envira_get_config( 'columns', $this->gallery_data ) ) . '-columns envira-clear' . $isotope . '" data-envira-columns="' . envira_get_config( 'columns', $this->gallery_data ) . '">';

			// Start image loop.
			if ( 'gutenberg' === strtolower( $gallery_type ) ) {
				$this->gallery_data = envira_sort_gallery( $this->gallery_data, $this->gallery_data['config']['sort_order'], $this->gallery_data['config']['sorting_direction'] );
			}

			foreach ( (array) $this->gallery_data['gallery'] as $id => $item ) {

				// Skip over images that are pending (ignore if in Preview mode).
				if ( isset( $item['status'] ) && 'pending' === $item['status'] && ! is_preview() ) {
					continue;
				}

				// Lets check if this gallery has already been output on the page.
				if ( ! in_array( $id, $this->gallery_item_ids, true ) ) {
					$this->gallery_item_ids[] = $id;
				}

				// Add the gallery item to the markup.
				$temp_gallery_markup = $this->generate_gallery_item_markup( $temp_gallery_markup, $this->gallery_data, $item, $id, $i, $gallery_images_raw );

				// Check the counter - if we are an instagram gallery AND there's a limit, then stop here.
				if ( isset( $atts['limit'] ) && is_numeric( $atts['limit'] ) && 'instagram' === $this->gallery_data['config']['type'] && $i >= $atts['limit'] ) {
					break;
				}

				// Increment the iterator.
				$i++;

			}
			// End image loop
			// Filter output before starting this gallery item.
			$temp_gallery_markup  = apply_filters( 'envira_gallery_output_before_item', $temp_gallery_markup, $id, $item, $data, $i );
			$temp_gallery_markup .= '</div>';

			$temp_gallery_markup   = apply_filters( 'envira_gallery_temp_output_after_container', $temp_gallery_markup, $this->gallery_data );
			$this->gallery_markup  = apply_filters( 'envira_gallery_output_after_container', $this->gallery_markup .= $temp_gallery_markup, $this->gallery_data );
			$this->gallery_markup .= '</div>';
			$this->gallery_markup  = apply_filters( 'envira_gallery_output_end', $this->gallery_markup, $this->gallery_data );

			// Remove any contextual filters so they don't affect other galleries on the page.
			if ( envira_get_config( 'mobile', $this->gallery_data ) ) {
				remove_filter( 'envira_gallery_output_image_attr', array( $this, 'mobile_image' ), 999, 4 );
			}

			// Add no JS fallback support.
			$no_js  = '<noscript>';
			$no_js .= $this->get_indexable_images( $data['id'] );
			$no_js .= '</noscript>';

			$this->gallery_markup .= apply_filters( 'envira_gallery_output_noscript', $no_js, $this->gallery_data );

			// Check for mobile.
			$is_mobile = envira_is_mobile();

			if ( $is_mobile ) {

				$transient = set_transient( '_eg_fragment_mobile_' . $data['gallery_id'], $this->gallery_markup, DAY_IN_SECONDS );

			} else {

				$transient = set_transient( '_eg_fragment_' . $data['gallery_id'], $this->gallery_markup, DAY_IN_SECONDS );

			}

			// Increment the counter.
			$this->counter++;

		}

		$this->data[ $data['id'] ] = $this->gallery_data;

		// Run a hook before the gallery output begins but after scripts and inits have been set.
		do_action( 'envira_gallery_end_shortcode', $atts, $this->data );

		// Return the gallery HTML.
		return apply_filters( 'envira_gallery_output', $this->gallery_markup, $this->gallery_data );

	}

	/**
	 * Shortcode link function.
	 *
	 * @access public
	 * @param mixed  $atts Attributes.
	 * @param string $content The content.
	 * @return void
	 */
	public function shortcode_link( $atts, $content = null ) {

		if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			// if it's the admin BUT it's not admin ajax from the frontend, then return.
			return;
		}

		global $post;

		// If no attributes have been passed, the gallery should be pulled from the current post.
		$gallery_id = false;

		if ( empty( $atts ) ) {

			$gallery_id = $post->ID;
			$data       = is_preview() ? _envira_get_gallery( $gallery_id ) : envira_get_gallery( $gallery_id );

		} elseif ( isset( $atts['id'] ) && ! isset( $atts['dynamic'] ) ) {

			$gallery_id = (int) $atts['id'];
			$data       = is_preview() ? _envira_get_gallery( $gallery_id ) : envira_get_gallery( $gallery_id );

		} elseif ( isset( $atts['slug'] ) ) {

			$gallery_id = $atts['slug'];
			$data       = is_preview() ? _envira_get_gallery_by_slug( $gallery_id ) : envira_get_gallery_by_slug( $gallery_id );

		} else {

			// A custom attribute must have been passed. Allow it to be filtered to grab data from a custom source.
			$data       = apply_filters( 'envira_gallery_custom_gallery_data', false, $atts, $post );
			$gallery_id = $data['config']['id'];

		}

		$this->link_data = $data;

		// Run a hook before the gallery output begins but after scripts and inits have been set.
		do_action( 'envira_gallery_link_before_output', $this->link_data );

		// Load custom lightbox themes.
		envira_load_lightbox_theme( envira_get_config( 'lightbox_theme', $this->link_data ) );

		$lazy_loading_delay = isset( $this->link_data['config']['lazy_loading_delay'] ) ? intval( $this->link_data['config']['lazy_loading_delay'] ) : 500;

		// Load scripts and styles.
		wp_enqueue_style( ENVIRA_SLUG . '-style' );
		wp_enqueue_script( ENVIRA_SLUG . '-script' );
		wp_localize_script(
			ENVIRA_SLUG . '-script',
			'envira_gallery',
			array(
				'debug'      => ( defined( 'ENVIRA_DEBUG' ) && ENVIRA_DEBUG ? true : false ),
				'll_delay'   => (string) $lazy_loading_delay,
				'll_initial' => 'false',
				'll'         => envira_get_config( 'lazy_loading', $data ) === 1 ? 'true' : 'false',
				'mobile'     => $this->is_mobile,

			)
		);

		$gallery_config   = "data-gallery-config='" . envira_get_gallery_config( $gallery_id ) . "'";
		$lb_theme_options = "data-lightbox-theme='" . envira_load_lightbox_config( $gallery_id ) . "'";

		$gallery_images_data               = envira_get_gallery_images( $gallery_id, null, $data, true );
		$gallery_images                    = $gallery_images_data['gallery_images'];
		$sorted_ids                        = $gallery_images_data['sorted_ids'];
		$gallery_images_attribute          = "data-gallery-images='" . $gallery_images . "' ";
		$gallery_images_sort_ids_attribute = "data-gallery-sort-ids='" . $sorted_ids . "' ";

		// Run a hook before the gallery output begins but after scripts and inits have been set.
		do_action( 'envira_link_before_output', $this->link_data );

		$output = '<a id="envira-links-' . $gallery_id . '" class="envira-gallery-links" href="#" ' . $gallery_config . ' ' . $gallery_images_attribute . ' ' . $gallery_images_sort_ids_attribute . ' ' . $lb_theme_options . ' >';

		$output .= $content;

		$output .= '</a>';

		return apply_filters( 'envira_link_shortcode_output', $output );

	}

	/**
	 * Outputs an individual gallery item in the grid
	 *
	 * @since 1.7.1
	 *
	 * @param    string $gallery    Gallery HTML.
	 * @param    array  $data       Gallery Config.
	 * @param    array  $item       Gallery Item (Image).
	 * @param    int    $id         Gallery Image ID.
	 * @param    int    $i          Index.
	 * @param    array  $images     Gallery images.
	 * @return   string              Gallery HTML
	 */
	public function generate_gallery_item_markup( $gallery, $data, $item, $id, $i, $images = false ) {

		// Skip over images that are pending (ignore if in Preview mode).
		if ( isset( $item['status'] ) && 'pending' === $item['status'] && ! is_preview() ) {

			return $gallery;

		}

		// Grab the raw data.
		if ( 'dynamic' === $data['config']['type'] ) {
			$data['gallery'] = $this->dynamic_images;
		}

		if ( ! $images ) {
			$images = envira_get_gallery_images( $data['id'], true, $data );
		}

		$raw    = false;
		$layout = isset( $data['config']['gallery_layout'] ) ? $data['config']['layout'] : $data['config']['columns'];

		if ( is_array( $images ) && isset( $images[ $id ] ) ) {

			$raw = $images[ $id ];

		} elseif ( is_object( $images ) && isset( $images->$id ) ) {

			$raw = get_object_vars( $images->$id );

		} else {
			/*
			This catch exists because envira-index="" was not being populated.
			$id was 0, 1, 2, etc. when the keys in the raw $images were the image ids.
			No population of envira-index means "display all images" for pagination would pull up index 0 image in lightbox. GH 1675.
			*/

			foreach ( $images as $image_id => $image_data ) {
				if ( $image_data['index'] === $id ) {
					$raw = $image_data;
				}
			}
		}

		if ( isset( $data['config']['sort_order'] ) && '1' === $data['config']['sort_order'] && false !== $raw ) {
			$raw['index'] = $i;
		}

		$item             = apply_filters( 'envira_gallery_output_item_data', $item, $id, $data, $i );
		$imagesrc         = envira_get_image_src( $id, $item, $data ); // Get image and image retina URLs.
		$image_src_retina = envira_get_image_src( $id, $item, $data, false, true );
		$placeholder      = wp_get_attachment_image_src( $id, 'medium' ); // $placeholder is null because $id is 0 for instagram?
		$output_item      = '';
		$lightbox_caption = false;
		$lightbox_title   = false;

		// If we don't get an imagesrc, it's likely because of an error w/ dynamic
		// So to prevent JS errors or not rendering the gallery at all, return the gallery HTML because we can't render without it.
		if ( ! $imagesrc ) {

			return $gallery;

		}

		// Get some config values that we'll reuse for each image.
		$padding          = absint( round( envira_get_config( 'gutter', $data ) / 2 ) );
		$gallery          = apply_filters( 'envira_gallery_output_before_item', $gallery, $id, $item, $data, $i ); // Filter output before starting this gallery item.
		$item             = $this->maybe_change_link( $id, $item, $data ); // Maybe change the item's link if it is an image and we have an image size defined for the Lightbox.
		$schema_microdata = apply_filters( 'envira_gallery_output_schema_microdata_imageobject', 'itemscope itemtype="http://schema.org/ImageObject"', $data ); // Schema.org microdata ( Itemscope, etc. ) interferes with Google+ Sharing... so we are adding this via filter rather than hardcoding.

		$output = '<div id="envira-gallery-item-' . sanitize_html_class( $id ) . '" class="' . $this->get_gallery_item_classes( $item, $i, $data ) . '" style="padding-left: ' . $padding . 'px; padding-bottom: ' . envira_get_config( 'margin', $data ) . 'px; padding-right: ' . $padding . 'px;" ' . apply_filters( 'envira_gallery_output_item_attr', '', $id, $item, $data, $i ) . ' ' . $schema_microdata . '>';

		$output .= '<div class="envira-gallery-item-inner">';
		$output  = apply_filters( 'envira_gallery_output_before_link', $output, $id, $item, $data, $i );

		// Top Left box.
		$output .= '<div class="envira-gallery-position-overlay envira-gallery-top-left">';
		$output  = apply_filters( 'envira_gallery_output_dynamic_position', $output, $id, $item, $data, $i, 'top-left' );
		$output .= '</div>';

		// Top Right box.
		$output .= '<div class="envira-gallery-position-overlay envira-gallery-top-right">';
		$output  = apply_filters( 'envira_gallery_output_dynamic_position', $output, $id, $item, $data, $i, 'top-right' );
		$output .= '</div>';

		// Bottom Left box.
		$output .= '<div class="envira-gallery-position-overlay envira-gallery-bottom-left">';
		$output  = apply_filters( 'envira_gallery_output_dynamic_position', $output, $id, $item, $data, $i, 'bottom-left' );
		$output .= '</div>';

		// Bottom Right box.
		$output .= '<div class="envira-gallery-position-overlay envira-gallery-bottom-right">';
		$output  = apply_filters( 'envira_gallery_output_dynamic_position', $output, $id, $item, $data, $i, 'bottom-right' );
		$output .= '</div>';

		// check and see if we are using a certain gallery theme, so we can determine if caption is sent.
		$gallery_theme = envira_get_config( 'gallery_theme', $data );

		if (
			( 'captioned' === $gallery_theme || 'polaroid' === $gallery_theme ) &&
			(
				( envira_get_config( 'additional_copy_caption', $data ) && ( 0 !== intval( envira_get_config( 'columns', $data ) ) ) )
				||
				( $this->is_mobile && envira_get_config( 'additional_copy_caption_mobile', $data ) && ( 0 !== intval( envira_get_config( 'columns', $data ) ) ) )
			)
		) {

			// don't display the caption, because the gallery theme will take care of this.
			$caption = false;

		} else {

			$caption_array = array();

			if (
				( ! $this->is_mobile && envira_get_config( 'additional_copy_automatic_title', $data ) && isset( $item['title'] ) )
				||
				( $this->is_mobile && envira_get_config( 'additional_copy_automatic_title_mobile', $data ) === 1 && isset( $item['title'] ) )
			) {
				$caption_array[] = wp_strip_all_tags( htmlspecialchars( $item['title'] ) );
			}

			if (
				( ! $this->is_mobile && envira_get_config( 'additional_copy_automatic_caption', $data ) && isset( $item['caption'] ) )
				||
				( $this->is_mobile && envira_get_config( 'additional_copy_automatic_caption_mobile', $data ) === 1 && isset( $item['caption'] ) )
			) {
				$caption_array[] = str_replace( array( "\r\n", "\r", "\n", "\\r", "\\n", "\\r\\n" ), '<br />', wp_strip_all_tags( esc_attr( $item['caption'] ) ) );
			}

			$caption = implode( ' - ', $caption_array );

		}

		// Show caption BUT if the user has overrided with the title, show that instead.
		$lightbox_caption = isset( $item['caption'] ) ? do_shortcode( str_replace( array( "\r\n", "\r", "\n", "\\r", "\\n", "\\r\\n" ), '<br />', $item['caption'] ) ) : false;
		$lightbox_caption = apply_filters( 'envira_gallery_output_lightbox_caption', $lightbox_caption, $data, $id, $item, $i );

		$lightbox_title = isset( $item['title'] ) ? do_shortcode( str_replace( array( "\r\n", "\r", "\n", "\\r", "\\n", "\\r\\n" ), '<br />', wp_strip_all_tags( htmlspecialchars( $item['title'] ) ) ) ) : false;
		$lightbox_title = apply_filters( 'envira_gallery_output_lightbox_title', $lightbox_title, $data, $id, $item, $i );

		// Cap the length of the lightbox caption for light/dark themes.
		if ( envira_get_config( 'lightbox_theme', $data ) === 'base_dark' || envira_get_config( 'lightbox_theme', $data ) === 'base_light' ) {

			$lightbox_caption_limit = apply_filters( 'envira_gallery_output_lightbox_caption_limit', 100, $data, $id, $item, $i );

			if ( strlen( $lightbox_caption ) > $lightbox_caption_limit ) {

				$lightbox_caption = substr( $lightbox_caption, 0, strrpos( substr( $lightbox_caption, 0, $lightbox_caption_limit ), ' ' ) ) . '...';

			}
		}

		$lightbox_caption     = envira_santitize_lightbox_caption( $lightbox_caption );
		$envira_gallery_class = 'envira-gallery-' . sanitize_html_class( $data['id'] );

		// Link Target.
		$external_link_array = false;

		// If this is a dynamic gallery and the user has passed in a comma-delimited list of URLS via "external" we should use these, otherwise go with normal.
		if ( isset( $data['config']['external'] ) && ! empty( $data['config']['external'] ) ) {

			$external_link_array = explode( ',', $data['config']['external'] );

			if ( is_array( $external_link_array ) ) {

				// determine where we are in the queue, override link if there's one.
				if ( isset( $external_link_array[ $i - 1 ] ) ) {

					$item['link']         = $external_link_array[ $i - 1 ]; // esc_url is filtered below.
					$item['link_type']    = 'external';
					$envira_gallery_class = false;

				}
			}
		}

		// add $item filter for any last minute adjustments, such as overriding
		// $item['link'] with instagram or change link_type.
		$item = apply_filters( 'envira_gallery_item_before_link', $item, $data, $id, $i, $this->is_mobile );

		// dynamic gallery isn't happening and there's an instagram_link, override.
		if ( ! $external_link_array && ! empty( $item['instagram_link'] ) ) {

			$item['link'] = $item['instagram_link'];

		}

		// if there is no link, assume it's the src.
		if ( empty( $item['link'] ) && ! empty( $item['src'] ) ) {
			$item['link'] = $item['src'];
		}

		$target = ! empty( $item['target'] ) ? 'target="' . $item['target'] . '"' : false;

		// Determine if we create a link.
		$create_link = ! empty( $item['link'] ) && (
						( envira_get_config( 'gallery_link_enabled', $data ) || envira_get_config( 'lightbox_enabled', $data ) )
					) ? true : false;

		// If this is a mobile device and the user has disabled lightbox, there should not be a link.
		if ( $this->is_mobile && ( ! envira_get_config( 'mobile_gallery_link_enabled', $data ) && ! envira_get_config( 'lightbox_enabled', $data ) ) ) {
			$create_link = false;
		}

		$create_link      = apply_filters( 'envira_gallery_create_link', $create_link, $data, $id, $item, $i, $this->is_mobile ); // Filter the ability to create a link.
		$schema_microdata = apply_filters( 'envira_gallery_output_schema_microdata_itemprop_contenturl', 'itemprop="contentUrl"', $data, $id, $item, $i ); // Schema.org microdata ( itemprop, etc. ) interferes with Google+ Sharing... so we are adding this via filter rather than hardcoding.

		if ( false !== $create_link ) {

			$this->is_mobile_thumb = ( isset( $item['mobile_thumb'] ) && ! empty( $item['mobile_thumb'] ) && ! is_wp_error( $item['mobile_thumb'] ) ) ? $item['mobile_thumb'] : esc_attr( $item['src'] );
			// If the image size is default (i.e. the user has input their own custom dimensions in the Gallery),
			// we may need to resize the image now
			// This is safe to call every time, as resize_image() will check if the image already exists, preventing thumbnails
			// from being generated every single time.
			$thumb_args = array(
				'position' => envira_get_config( 'crop_position', $data ),
				'width'    => false === envira_get_config( 'thumbnails_custom_size', $data ) ? envira_get_config_default( 'thumbnails_width' ) : envira_get_config( 'thumbnails_width', $data ),
				'height'   => false === envira_get_config( 'thumbnails_custom_size', $data ) ? envira_get_config_default( 'thumbnails_height' ) : envira_get_config( 'thumbnails_height', $data ),
				'quality'  => 100,
				'retina'   => false,
			);

			if ( 'instagram' !== $data['config']['type'] ) {
				$thumb = envira_resize_image( $item['src'], $thumb_args['width'], $thumb_args['height'], true, envira_get_config( 'crop_position', $data ), $thumb_args['quality'], $thumb_args['retina'], $data );
			} else {
				$thumb = ( isset( $item['thumb'] ) ) ? $item['thumb'] : $item['src'];
			}

			// If there's a WP_Error (maybe a 'No image URL specified for cropping.'), fall back to $item['thumb'].
			if ( is_wp_error( $thumb ) ) {
				// If WP_DEBUG is enabled, and we're logged in, output an error to the user
				// if ( defined( 'WP_DEBUG' ) && WP_DEBUG && is_user_logged_in() ) {
				// echo '<pre>Envira: Error occured resizing image (these messages are only displayed to logged in WordPress users):<br />';
				// echo 'Error: ' . $thumb->get_error_message() . '<br />';
				// echo 'Thumb Args: ' . var_export( $thumb_args, true ) . '</pre>';
				// }.
				$thumb = ( isset( $item['thumb'] ) ) ? $item['thumb'] : $item['src'];
			}

			if ( isset( $data['config']['mobile_thumbnails_width'] ) && isset( $data['config']['mobile_thumbnails_width'] ) ) {

				$item['mobile_thumb'] = envira_resize_image( $item['src'], $data['config']['mobile_thumbnails_width'], $data['config']['mobile_thumbnails_height'], true, envira_get_config( 'crop_position', $data ), 100, false, $data, false );

			}

			// setup misc attributes.
			$thumb              = $this->is_mobile && ! is_wp_error( $item['mobile_thumb'] ) ? $item['mobile_thumb'] : $thumb;
			$video_placeholder  = ( isset( $item['src'] ) && isset( $item['video']['type'] ) ) ? ' data-video-placeholder="' . $item['src'] . '"' : false;
			$title              = str_replace( '<', '&lt;', $item['title'] ); // not sure why this was not encoded.
			$ig                 = envira_get_config( 'type', $data ) === 'instagram' ? ' data-src="' . $item['src'] . '"' : '';
			$envia_gallery_link = envira_get_config( 'lightbox_enabled', $data ) && ( envira_get_config( 'type', $data ) !== 'instagram' || ( envira_get_config( 'type', $data ) === 'instagram' && ! empty( $data['config']['instagram_link'] ) ) ) ? 'envira-gallery-link' : 'envira-gallery-link';

			// allow addons to change the link.
			$link_href = apply_filters( 'envira_gallery_link_href', $item['link'], $data, $id, $item, $i, $this->is_mobile );

			$output .= '<a ' . ( ( isset( $item['link_type'] ) && 'external' === $item['link_type'] ) ? '' : 'data-envirabox="' . sanitize_html_class( $data['id'] ) . '"' ) . $target . ' href="' . esc_url( $link_href ) . '" class="envira-gallery-' . sanitize_html_class( $data['id'] ) . ' ' . $envia_gallery_link . ' " title="' . wp_strip_all_tags( htmlspecialchars( $title ) ) . '" data-envira-item-id="' . $id . '" data-title="' . $lightbox_title . '" data-caption="' . $lightbox_caption . '" data-envira-retina="' . ( isset( $item['lightbox_retina_image'] ) ? $item['lightbox_retina_image'] : '' ) . '" data-thumb="' . esc_attr( $thumb ) . '"' . ( ( isset( $item['link_new_window'] ) && 1 === $item['link_new_window'] ) ? ' target="_blank"' : '' ) . ' ' . apply_filters( 'envira_gallery_output_link_attr', '', $id, $item, $data, $i ) . ' ' . $schema_microdata . ' ' . $ig . $video_placeholder . '>';

		}

		$output               = apply_filters( 'envira_gallery_output_before_image', $output, $id, $item, $data, $i );
		$gallery_theme        = envira_get_config( 'columns', $data ) === 0 ? ' envira-' . envira_get_config( 'justified_gallery_theme', $data ) : '';
		$gallery_theme_suffix = ( envira_get_config( 'justified_gallery_theme_detail', $data ) ) === 'hover' ? '-hover' : false;
		$schema_microdata     = apply_filters( 'envira_gallery_output_schema_microdata_itemprop_thumbnailurl', 'itemprop="thumbnailUrl"', $data, $id, $item, $i ); // Schema.org microdata ( itemprop, etc. ) interferes with Google+ Sharing... so we are adding this via filter rather than hardcoding.
		$envira_lazy_load     = envira_get_config( 'lazy_loading', $data ) === 1 ? 'envira-lazy' : ''; // Check if user has lazy loading on - if so, we add the css class.

		// Determine/confirm the width/height of the image
		// $placeholder should hold it but not for instagram.
		if ( 'mobile' === $this->is_mobile && ! envira_get_config( 'mobile', $data ) ) { // if the user is viewing on mobile AND user unchecked 'Create Mobile Gallery Images?' .in mobile tab.

			$output_src = $item['src'];

		} elseif ( envira_get_config( 'crop', $data ) ) { // the user has selected the image to be cropped.

			$output_src = $imagesrc;

		} elseif ( envira_get_config( 'image_size', $data ) && $imagesrc ) { // use the image being provided thanks to the user selecting a unique image size.

			$output_src = $imagesrc;

		} elseif ( ! empty( $item['src'] ) ) {

			$output_src = $item['src'];

		} elseif ( ! empty( $placeholder[0] ) ) {

			$output_src = $placeholder[0];

		} else {

			$output_src = false;

		}

		if ( envira_get_config( 'crop', $data ) && envira_get_config( 'crop_width', $data ) && envira_get_config( 'image_size', $data ) !== 'full' ) {

			$output_width = envira_get_config( 'crop_width', $data );

		} elseif ( intval( envira_get_config( 'columns', $data ) ) !== 0 && envira_get_config( 'image_size', $data ) && envira_get_config( 'image_size', $data ) !== 'full' && envira_get_config( 'crop_width', $data ) && envira_get_config( 'crop_height', $data ) ) {

			$output_width = envira_get_config( 'crop_width', $data );

		} elseif ( isset( $data['config']['type'] ) && 'instagram' === $data['config']['type'] && strpos( $imagesrc, 'cdninstagram' ) !== false ) {

			// if this is an instagram image, @getimagesize might not work
			// therefore we should try to extract the size from the url itself.
			if ( ! empty( $item['width'] ) ) {

				$output_width = $item['width'];

			} elseif ( strpos( $imagesrc, '150x150' ) ) {

				$output_width = '150';

			} elseif ( strpos( $imagesrc, '640x640' ) ) {

				$output_width = '640';

			} else {

				$output_width = '150';

			}
		} elseif ( ! empty( $placeholder[1] ) ) {

			$output_width = $placeholder[1];

		} elseif ( ! empty( $item['width'] ) ) {

			$output_width = $item['width'];

		} else {

			$output_width = false;

		}

		if ( envira_get_config( 'crop', $data ) && envira_get_config( 'crop_width', $data ) && envira_get_config( 'image_size', $data ) !== 'full' ) {

			$output_height = envira_get_config( 'crop_height', $data );

		} elseif ( intval( envira_get_config( 'columns', $data ) ) !== 0 && envira_get_config( 'image_size', $data ) && envira_get_config( 'image_size', $data ) !== 'full' && envira_get_config( 'crop_width', $data ) && envira_get_config( 'crop_height', $data ) ) {

			$output_height = envira_get_config( 'crop_height', $data );

		} elseif ( isset( $data['config']['type'] ) && 'instagram' === $data['config']['type'] && strpos( $imagesrc, 'cdninstagram' ) !== false ) {

			// if this is an instagram image, @getimagesize might not work
			// therefore we should try to extract the size from the url itself.
			if ( ! empty( $item['height'] ) ) {

				$output_height = $item['height'];

			} elseif ( strpos( $imagesrc, '150x150' ) ) {

				$output_height = '150';

			} elseif ( strpos( $imagesrc, '640x640' ) ) {

				$output_height = '640';

			} else {
				$output_height = '150';

			}
		} elseif ( ! empty( $placeholder[2] ) ) {

			$output_height = $placeholder[2];

		} elseif ( ! empty( $item['height'] ) ) {

			$output_height = $item['height'];

		} else {

			$output_height = false;

		}

		$gallery_image_width  = ( isset( $this->gallery_data['config']['type'] ) && 'instagram' === $this->gallery_data['config']['type'] ) ? $output_width : envira_get_config( 'crop_width', $data );
		$gallery_image_height = ( isset( $this->gallery_data['config']['type'] ) && 'instagram' === $this->gallery_data['config']['type'] ) ? $output_height : envira_get_config( 'crop_height', $data );

		/* add filters for width and height, primarily so dynamic can add width and height */
		$output_width  = apply_filters( 'envira_gallery_output_width', $output_width, $id, $item, $data, $i, $output_src );
		$output_height = apply_filters( 'envira_gallery_output_height', $output_height, $id, $item, $data, $i, $output_src );

		/* if $raw is an object, convert to array, although this shouldn't be possible? */
		if ( is_object( $raw ) && isset( $raw->index ) ) {
			$raw = get_object_vars( $raw );
		}

		// use $i for index if there's a filtering of tags (envira-tag and envira-category).
		$envira_index = ! empty( $_REQUEST[ 'envira-tag' ] ) || ! empty( $_REQUEST[ 'envira-category' ] ) ? $i - 1 : $raw['index']; // @codingStandardsIgnoreLine - nonce

		// set tab index to -1 if there's a link, which was already set to 0.
		$tabindex = $create_link ? -1 : 0;

		if ( intval( envira_get_config( 'columns', $data ) ) === 0 ) {

			// Automatic!
			$output_item = '<img id="envira-gallery-image-' . sanitize_html_class( $id ) . '" class="envira-gallery-image envira-gallery-image-' . $i . $gallery_theme . $gallery_theme_suffix . ' ' . $envira_lazy_load . '" data-envira-index="' . $envira_index . '" src="' . esc_url( $output_src ) . '" width="' . envira_get_config( 'crop_width', $data ) . '" height="' . envira_get_config( 'crop_height', $data ) . '" tabindex="' . $output_width . '" data-envira-src="' . esc_url( $output_src ) . '" data-envira-gallery-id="' . $data['id'] . '" data-envira-item-id="' . $id . '" data-automatic-caption="' . $caption . '" data-caption="' . $lightbox_caption . '" data-title="' . $lightbox_title . '" alt="' . esc_attr( $item['alt'] ) . '" title="' . wp_strip_all_tags( esc_attr( $item['title'] ) ) . '" ' . apply_filters( 'envira_gallery_output_image_attr', '', $id, $item, $data, $i ) . ' ' . $schema_microdata . ' data-envira-srcset="' . esc_url( $output_src ) . ' 400w,' . esc_url( $output_src ) . ' 2x" data-envira-width="' . $output_width . '" data-envira-height="' . $output_height . '" srcset="' . ( ( $envira_lazy_load ) ? 'data:image/gif;base64,R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==' : esc_url( $image_src_retina ) . ' 2x' ) . '" data-safe-src="' . ( ( $envira_lazy_load ) ? 'data:image/gif;base64,R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==' : esc_url( $output_src ) ) . '" />';

		} else {

			if ( $envira_lazy_load ) {

				if ( $output_height > 0 && $output_width > 0 ) {
					$padding_bottom = ( $output_height / $output_width ) * 100;
				} else {
					// this shouldn't be happening, but this avoids a debug message.
					$padding_bottom = 100;
				}
				$output_item .= '<div class="envira-lazy" data-envira-changed="false" data-width="' . $output_width . '" data-height="' . $output_height . '" style="padding-bottom:' . $padding_bottom . '%;">';

			}

			// If the user has asked to set width and height dimensions to images, let's determine what those values should be.
			if ( envira_get_config( 'image_size', $data ) && envira_get_config( 'image_size', $data ) === 'default' ) {

				$width_attr_value  = envira_get_config( 'crop_width', $data );
				$height_attr_value = envira_get_config( 'crop_height', $data );

			} elseif ( envira_get_config( 'image_size', $data ) && envira_get_config( 'image_size', $data ) === 'full' ) {

				// is there a way to get the oringial width/height outside of this?
				$src               = apply_filters( 'envira_gallery_retina_image_src', wp_get_attachment_image_src( $id, 'full' ), $id, $item, $data );
				$width_attr_value  = $src[1];
				$height_attr_value = $src[2];

			} elseif ( $output_width && $output_height ) {

				$width_attr_value  = $output_width;
				$height_attr_value = $output_height;

			} else {

				$width_attr_value  = false;
				$height_attr_value = false;

			}

			$output_item .= '<img id="envira-gallery-image-' . sanitize_html_class( $id ) . '" tabindex="' . $tabindex . '" class="envira-gallery-image envira-gallery-image-' . $i . $gallery_theme . $gallery_theme_suffix . ' ' . $envira_lazy_load . '" data-envira-index="' . $envira_index . '" src="' . esc_url( $output_src ) . '" width="' . $width_attr_value . '" height="' . $height_attr_value . '" data-envira-src="' . esc_url( $output_src ) . '" data-envira-gallery-id="' . $data['id'] . '" data-envira-item-id="' . $id . '" data-caption="' . $lightbox_caption . '" data-title="' . $lightbox_title . '"  alt="' . esc_attr( $item['alt'] ) . '" title="' . wp_strip_all_tags( esc_attr( $item['title'] ) ) . '" ' . apply_filters( 'envira_gallery_output_image_attr', '', $id, $item, $data, $i ) . ' ' . $schema_microdata . ' data-envira-srcset="' . esc_url( $output_src ) . ' 400w,' . esc_url( $output_src ) . ' 2x" srcset="' . ( ( $envira_lazy_load ) ? 'data:image/gif;base64,R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==' : esc_url( $image_src_retina ) . ' 2x' ) . '" />';

			if ( $envira_lazy_load ) {

				$output_item .= '</div>';

			}
		}

		$output_item = apply_filters( 'envira_gallery_output_image', $output_item, $id, $item, $data, $i );

		// Add image to output.
		$output .= $output_item;
		$output  = apply_filters( 'envira_gallery_output_after_image', $output, $id, $item, $data, $i );

		if ( $create_link ) {
			$output .= '</a>';
		}

		$filtered_output = apply_filters( 'envira_gallery_output_caption', $output, $id, $item, $data, $i );

		$after_link = apply_filters( 'envira_gallery_output_after_link', $filtered_output, $id, $item, $data, $i );

		$output .= $after_link . '</div>';

		$output .= '</div>';

		$output = apply_filters( 'envira_gallery_output_single_item', $output, $id, $item, $data, $i );

		// Append the image to the gallery output.
		$gallery .= $output;

		// Filter the output before returning.
		$gallery = apply_filters( 'envira_gallery_output_after_item', $gallery, $id, $item, $data, $i );

		return $gallery;

	}

	/**
	 * Add the 'property' tag to stylesheets enqueued in the body
	 *
	 * @since 1.4.1.1
	 *
	 * @param string $tag HTML tag.
	 */
	public function add_stylesheet_property_attribute( $tag ) {

		// If the <link> stylesheet is any Envira-based stylesheet, add the property attribute.
		if ( strpos( $tag, "id='envira-" ) !== false ) {

			$tag = str_replace( '/>', 'property="stylesheet" />', $tag );

		}

		return $tag;

	}

	/**
	 * Builds HTML for the Gallery Description
	 *
	 * @since 1.3.0.2
	 *
	 * @param string $gallery Gallery HTML (THIS IS BEING USED IN THIS FUNCTION?).
	 * @param array  $data Data.
	 * @return HTML
	 */
	public function description( $gallery, $data ) {

		// Get description.
		$description = $data['config']['description'];

		$gallery_markup = '<div class="envira-gallery-description envira-gallery-description-above" style="padding-bottom: ' . envira_get_config( 'margin', $data ) . 'px;">';
		$gallery_markup = apply_filters( 'envira_gallery_output_before_description', $gallery_markup, $data );

		// If the WP_Embed class is available, use that to parse the content using registered oEmbed providers.
		if ( isset( $GLOBALS['wp_embed'] ) ) {

			$description = $GLOBALS['wp_embed']->autoembed( $description );

		}

		// Get the description and apply most of the filters that apply_filters( 'the_content' ) would use
		// We don't use apply_filters( 'the_content' ) as this would result in a nested loop and a failure.
		$description = wptexturize( $description );
		$description = convert_smilies( $description );
		$description = wpautop( $description );
		$description = prepend_attachment( $description );
		$description = wp_make_content_images_responsive( $description );

		// Append the description to the gallery output.
		$gallery_markup .= $description;

		// Filter the gallery HTML.
		$gallery_markup = apply_filters( 'envira_gallery_output_after_description', $gallery_markup, $data );

		$gallery_markup .= '</div>';

		return $gallery_markup;

	}

	/**
	 * Set the title display per theme
	 *
	 * @since 1.7.0
	 *
	 * @param string $title_display CSS Class I think.
	 * @param array  $data this is either a string or an integer and can be set accordingly.
	 */
	public function envira_gallery_title_type( $title_display, $data ) {

		// Get gallery theme.
		$lightbox_theme = envira_get_config( 'lightbox_theme', $data );

		switch ( $lightbox_theme ) {

			case 'base_dark':
				$title_display = 'fixed';
				break;

		}

		return $title_display;

	}

	/**
	 * Helper method for adding custom gallery classes.
	 *
	 * @since 1.7.0
	 *
	 * @param array $data The gallery data to use for retrieval.
	 * @return string        String of space separated gallery classes.
	 */
	public function get_gallery_classes( $data ) {

		// Set default class.
		$classes = array(
			'envira-gallery-wrap',
			'envira-gallery-theme-' . envira_get_config( 'gallery_theme', $data ),
		);

		// If we have custom classes defined for this gallery, output them now.
		foreach ( (array) envira_get_config( 'classes', $data ) as $class ) {
			$classes[] = $class;
		}

		// If the gallery has RTL support, add a class for it.
		if ( envira_get_config( 'rtl', $data ) ) {
			$classes[] = 'envira-gallery-rtl';
		}

		// Allow filtering of classes and then return what's left.
		$classes = apply_filters( 'envira_gallery_output_classes', $classes, $data );

		return trim( implode( ' ', array_map( 'trim', array_map( 'sanitize_html_class', array_unique( $classes ) ) ) ) );

	}

	/**
	 * Helper method for adding custom gallery classes.
	 *
	 * @since 1.0.4
	 *
	 * @param array $item Array of item data.
	 * @param int   $i        The current position in the gallery.
	 * @param array $data The gallery data to use for retrieval.
	 * @return string        String of space separated gallery item classes.
	 */
	public function get_gallery_item_classes( $item, $i, $data ) {

		// Set default classes.
		$classes = array(
			'envira-gallery-item',
			'envira-gallery-item-' . $i,
		);

		if ( isset( $item['video_in_gallery'] ) && 1 === $item['video_in_gallery'] ) {

			$classes[] = 'envira-video-in-gallery';

		}

		// If istope exists, add that.
		if ( isset( $data['config']['isotope'] ) && 1 === $data['config']['isotope'] ) {

			$classes[] = 'enviratope-item';

		}

		// If lazy load exists, add that.
		if ( isset( $data['config']['lazy_loading'] ) && $data['config']['lazy_loading'] ) {

			$classes[] = 'envira-lazy-load';

		}

		// Allow filtering of classes and then return what's left.
		$classes = apply_filters( 'envira_gallery_output_item_classes', $classes, $item, $i, $data );

		return trim( implode( ' ', array_map( 'trim', array_map( 'sanitize_html_class', array_unique( $classes ) ) ) ) );

	}

	/**
	 * Changes the link attribute of an image, if the Lightbox config
	 * requires a different sized image to be displayed.
	 *
	 * @since 1.3.6
	 *
	 * @param int   $id       The image attachment ID to use.
	 * @param array $item   Gallery item data.
	 * @param array $data   The gallery data to use for retrieval.
	 * @return array        Image array
	 */
	public function maybe_change_link( $id, $item, $data ) {

		// Check gallery config.
		$image_size = envira_get_config( 'lightbox_image_size', $data );

		// check if the url is a valid image if not return it.
		if ( ! envira_is_image( $item['link'] ) ) {
			return $item;
		}

		// Get media library attachment at requested size.
		$image = wp_get_attachment_image_src( $id, $image_size );

		// Determine if the image url resides on a third-party site.
		$url = preg_replace( '/(?:https?:\/\/)?(?:www\.)?(.*)\/?$/i', '$1', network_site_url() );
		if ( strpos( $item['link'], $url ) === false ) {
			return $item;
		}

		// Determine if the image is entered by the user as an overide in the image modal
		// We are doing this by comparing the first few characters to see if the filename is a possible match
		// This covers the scenario of a filename being in the same upload folder as the oringial image, something probably rare.
		// We can't compare the entire string because the end of the string might contain misc characters, dropping, etc.
		$filename_image = basename( $image[0] );
		$filename_link  = basename( $item['link'] );
		$pos            = strspn( $filename_image ^ $filename_link, "\0" );

		// First few characters don't match, likely this is a different image in the same upload directory
		// The number can be changed to literally anything.
		if ( $pos <= apply_filters( 'envira_gallery_check_image_file_name', 10, $filename_image, $filename_link, $data ) ) {
			return $item;
		}

		if ( ! is_array( $image ) ) {
			return $item;
		}

		// Inject new image size into $item.
		$item['link'] = $image[0];

		return $item;

	}

	/**
	 * Find Clostest Size function.
	 *
	 * @access public
	 * @param mixed $data Image Data.
	 * @return boolean
	 */
	public function find_clostest_size( $data ) {

		$image_sizes = envira_get_shortcode_image_sizes();
		$dimensions  = envira_get_config( 'dimensions', $data );
		$width       = envira_get_config( 'crop_width', $data );
		$height      = envira_get_config( 'crop_height', $data );
		$match       = false;

		usort( $image_sizes, array( $this, 'usort_callback' ) );

		foreach ( $image_sizes as $num ) {

			$num['width']  = (int) $num['width'];
			$num['height'] = (int) $num['height'];

			// skip over sizes that are smaller.
			if ( $num['height'] < $height || $num['width'] < $width ) {
				continue;
			}

			if ( $num['width'] > $width && $num['height'] > $height ) {

				if ( false === $match ) {

					$match = true;
					$size  = $num['name'];

					return $size;
				}
			}
		}

		return false;

	}

	/**
	 * Helper function for usort and php 5.3 >.
	 *
	 * @access public
	 * @param mixed $a First sort.
	 * @param mixed $b Second sort.
	 * @return int
	 */
	public function usort_callback( $a, $b ) {

		return intval( $a['width'] ) - intval( $b['width'] );

	}

	/**
	 * Outputs only the first image of the gallery inside a regular <div> tag
	 * to avoid styling issues with feeds.
	 *
	 * @since 1.0.5
	 *
	 * @param array $data         Array of gallery data.
	 * @return string $gallery Custom gallery output for feeds.
	 */
	public function do_feed_output( $data ) {

		$gallery = '<div class="envira-gallery-feed-output">';

			$gallery_data = (array) $data['gallery'];

		foreach ( $gallery_data as $id => $item ) {

			// Skip over images that are pending (ignore if in Preview mode).
			if ( isset( $item['status'] ) && 'pending' === $item['status'] && ! is_preview() ) {

				continue;

			}

			$imagesrc = envira_get_image_src( $id, $item, $data );
			$gallery .= '<img class="envira-gallery-feed-image" tabindex="0" src="' . esc_url( $imagesrc ) . '" title="' . trim( esc_html( $item['title'] ) ) . '" alt="' . trim( esc_html( $item['alt'] ) ) . '" />';
			break;

		}

		$gallery .= '</div>';

		return apply_filters( 'envira_gallery_feed_output', $gallery, $data );

	}

	/**
	 * Returns a set of indexable image links to allow SEO indexing for preloaded images.
	 *
	 * @since 1.7.0
	 *
	 * @param mixed $id         The slider ID to target.
	 * @return string $images String of indexable image HTML.
	 */
	public function get_indexable_images( $id ) {

		// If there are no images, don't do anything.
		$images = '';
		$i      = 1;

		if ( empty( $this->index[ $id ] ) ) {

			return $images;

		}

		// potentially assign a CSS class because some lazy loaders (like Jetpack) will try to process these images
		// and a CSS class might be used as a blacklist.
		$css_classes   = apply_filters( 'envira_gallery_indexable_image_css', false, $id );
		$css_attribute = $css_classes ? ' class="' . $css_classes . '"' : false;

		foreach ( (array) $this->index[ $id ] as $attach_id => $data ) {

			$images .= '<img src="' . esc_url( $data['src'] ) . '" alt="' . esc_attr( $data['alt'] ) . '" ' . $css_attribute . ' />';
			$i++;

		}

		return apply_filters( 'envira_gallery_indexable_images', $images, $this->index, $id );

	}

	/**
	 * Turns adding gallery description into a hook, so it can be placed above tags
	 *
	 * @since 1.7.0
	 *
	 * @param string $gallery Output HTML.
	 * @param array  $data Gallery Data.
	 * @return string Output HTML.
	 */
	public function envira_add_gallery_description_above( $gallery, $data ) {

		// Description.
		if ( isset( $data['config']['description_position'] ) && 'above' === $data['config']['description_position'] ) {
			$gallery .= $this->description( $gallery, $data );
		}

		return $gallery;

	}

	/**
	 * Turns adding gallery description into a hook, so it can be placed above tags
	 *
	 * @since 1.7.0
	 *
	 * @param string $gallery_markup Output HTML.
	 * @param array  $data Gallery Data.
	 * @return string Output HTML.
	 */
	public function envira_add_gallery_description_below( $gallery_markup, $data ) {

		// Description.
		if ( isset( $data['config']['description_position'] ) && 'below' === $data['config']['description_position'] ) {
			$gallery_markup = $gallery_markup . $this->description( $gallery_markup, $data );

		}

		return $gallery_markup;

	}

	/**
	 * Allow users to add a title or caption under an image for legacy galleries
	 *
	 * @since 1.7.0
	 *
	 * @param string $output Output HTML.
	 * @param int    $id Image Attachment ID.
	 * @param array  $item Image Data.
	 * @param array  $data Gallery Data.
	 * @param int    $i Image Count.
	 * @return string Output HTML
	 */
	public function gallery_image_caption_titles( $output, $id, $item, $data, $i ) {

		// for some reason - probably ajax - the $this->gallery_data is
		// empty on "load more" ajax pagination but $data comes through...
		if ( ! $this->gallery_data ) {
			$this->gallery_data = $data;
		}

		// this only applies to legacy, not dark/light themes, etc.
		if ( 0 === intval( envira_get_config( 'columns', $this->gallery_data ) ) ) {
			return false; // used to be return false.
		}

		// get the gallery theme.
		$gallery_theme = envira_get_config( 'gallery_theme', $data );

		// start the revised output.
		$gallery_theme  = envira_get_config( 'gallery_theme', $data );
		$revised_output = '<div class="envira-gallery-captioned-data envira-gallery-captioned-data-' . $gallery_theme . '">';

		$allowed_html_tags = apply_filters(
			'envira_gallery_image_caption_allowed_html_tags',
			array(
				'a'      => array(
					'href'   => array(),
					'title'  => array(),
					'class'  => array(),
					'target' => array(),
				),
				'br'     => array(),
				'em'     => array(),
				'strong' => array(),
				'p'      => array(),
				'strike' => array(),
				'object' => array(),
			),
			$id,
			$item,
			$data,
			$i
		);

		// has user checked off title and is there a title in $item?
		if (
			( ! $this->is_mobile && envira_get_config( 'additional_copy_title', $this->gallery_data ) === 1 && ! empty( $item['title'] ) )
			||
			( $this->is_mobile && envira_get_config( 'additional_copy_title_mobile', $this->gallery_data ) === 1 && ! empty( $item['title'] ) )
		) {

			$revised_output .= '<span class="envira-title envira-gallery-captioned-text title-' . $id . '">' . wp_kses( $item['title'], $allowed_html_tags ) . '</span>';

		}

		// has user checked off title and is there a title in $item?
		if (
			( ! $this->is_mobile && envira_get_config( 'additional_copy_caption', $this->gallery_data ) === 1 && ! empty( $item['caption'] ) )
			||
			( $this->is_mobile && envira_get_config( 'additional_copy_caption_mobile', $this->gallery_data ) === 1 && ! empty( $item['caption'] ) )

		) {

				$revised_output .= '<span class="envira-caption envira-gallery-captioned-text caption-' . $id . '">' . wp_kses( $item['caption'], $allowed_html_tags ) . '</span>';

		}

		$revised_output .= '</div>';

		// check for line breaks, convert them to <br/>.
		$revised_output = nl2br( $revised_output );

		return apply_filters( 'envira_gallery_image_caption_titles', $revised_output, $id, $item, $data, $i );

	}

}
