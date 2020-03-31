<?php
/**
 * Metabox class.
 *
 * @since 1.7.0
 *
 * @package Envira_Gallery
 * @author  Envira Gallery Team <support@enviragallery.com>
 */

namespace Envira\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Envira\Utils\Export;
use Envira\Utils\Import;
use Envira\Admin\Notices;

/**
 * Metabox class.
 *
 * @since 1.7.0
 *
 * @package Envira_Gallery
 * @author  Envira Gallery Team <support@enviragallery.com>
 */
class Metaboxes {

	/**
	 * Settings
	 *
	 * @var mixed
	 * @access public
	 */
	public $settings;

	/**
	 * Whitelabel
	 *
	 * @var mixed
	 * @access public
	 */
	public $whitelabel;

	/**
	 * Duplicate Post ID
	 *
	 * @var mixed
	 * @access public
	 */
	public $duplicate_post_id;


	/**
	 * Allowed HTML
	 *
	 * @var mixed
	 * @access public
	 */
	public $wp_kses_allowed_html = array(
		'a'      => array(
			'href'                => array(),
			'target'              => array(),
			'class'               => array(),
			'title'               => array(),
			'data-status'         => array(),
			'data-envira-tooltip' => array(),
			'data-id'             => array(),
		),
		'br'     => array(),
		'img'    => array(
			'src'   => array(),
			'class' => array(),
			'alt'   => array(),
		),
		'h1'     => array(
			'class' => array(),
		),
		'h2'     => array(
			'class' => array(),
		),
		'h3'     => array(
			'class' => array(),
		),
		'h4'     => array(
			'class' => array(),
		),
		'h5'     => array(
			'class' => array(),
		),
		'h6'     => array(
			'class' => array(),
		),
		'div'    => array(
			'class' => array(),
		),
		'li'     => array(
			'id'                              => array(),
			'class'                           => array(),
			'data-envira-gallery-image'       => array(),
			'data-envira-gallery-image-model' => array(),
		),
		'em'     => array(),
		'span'   => array(
			'class' => array(),
		),
		'strong' => array(),
	);

	/**
	 * Primary class constructor.
	 *
	 * @since 1.7.0
	 */
	public function __construct() {

		// Load the base class object.
		$this->whitelabel = apply_filters( 'envira_whitelabel', false );

		// Output a notice if missing cropping extensions because Envira needs them.
		if ( ! envira_has_gd_extension() && ! envira_has_imagick_extension() ) {
			add_action( 'admin_notices', array( $this, 'notice_missing_extensions' ) );
		}

		// Actions/filters related to third party plugins, to resolve conflicts.
		add_action( 'admin_enqueue_scripts', array( $this, 'fix_plugin_js_conflicts' ), 100 );
		add_filter( 'cta_excluded_post_types', array( $this, 'envira_excluded_post_types' ), 0, 1 );

		// Remove more conflicts.
		remove_action( 'admin_footer', 'wpsm_service_box_pro_editor_popup_content' ); /* service-showcase-pro */
		remove_action( 'admin_footer', 'wpsm_colorbox_pro_editor_popup_content' ); /* colorbox-pro */
		remove_action( 'admin_footer', 'rpp_add_rpg_inline_popup_content' ); /* Responsive Portfolio Pro */
		remove_action( 'media_buttons_context', 'wpsm_colorbox_pro_editor_popup_content_button' ); /* colorbox-pro */

		// Scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );

		// Set base css class if this is whitelabeled.
		if ( $this->whitelabel ) {
			add_filter( 'admin_body_class', array( $this, 'whitelabel_styles' ), 100 );
		}

		// Metaboxes.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 1 );

		// Add the envira-gallery class to the form, so our styles can be applied.
		add_action( 'post_edit_form_tag', array( $this, 'add_form_class' ) );

		// Modals.
		add_filter( 'media_view_strings', array( $this, 'media_view_strings' ) );

		// Load all tabs.
		add_action( 'envira_gallery_tab_images', array( $this, 'images_tab' ) );
		add_action( 'envira_gallery_tab_config', array( $this, 'config_tab' ) );
		add_action( 'envira_gallery_tab_lightbox', array( $this, 'lightbox_tab' ) );
		add_action( 'envira_gallery_tab_mobile', array( $this, 'mobile_tab' ) );
		add_action( 'envira_gallery_tab_standalone', array( $this, 'standalone_tab' ) );
		add_action( 'envira_gallery_tab_misc', array( $this, 'misc_tab' ) );

		// Save Gallery.
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 2 );

		// Output success notice.
		add_action( 'admin_notices', array( $this, 'notice_warnings' ) );
		add_filter( 'get_user_option_meta-box-order_envira', array( $this, 'metabox_order' ) );

		$export = new Export();
		$import = new Import();

	}
	/**
	 * Helper method to maintain metabox order
	 *
	 * @since 1.8.7
	 *
	 * @param array $order Metabox order.
	 * @return array
	 */
	public function metabox_order( $order ) {
		return array(
			'normal' => join(
				',',
				array(
					'envira-gallery',
					'envira-gallery-settings',
					'authordiv',
					'slugdiv',
				)
			),
		);
	}
	/**
	 * Outputs a notice when the GD and Imagick PHP extensions aren't installed.
	 *
	 * @since 1.7.0
	 */
	public function notice_missing_extensions() {

		?>
		<div class="error">
			<p><strong><?php esc_html_e( 'The GD or Imagick libraries are not installed on your server. Envira Gallery requires at least one (preferably Imagick) in order to crop images and may not work properly without it. Please contact your webhost and ask them to compile GD or Imagick for your PHP install.', 'envira-gallery' ); ?></strong></p>
		</div>
		<?php

	}

	/**
	 * Outputs a notice warning.
	 *
	 * @since 1.7.0
	 */
	public function notice_warnings() {

		$notices = new Notices();

		if ( isset( $_GET['envira_slug_exists'] ) ) { // @codingStandardsIgnoreLine

			$duplicate_post_id = intval( $_GET['envira_slug_exists'] ); // @codingStandardsIgnoreLine
			if ( $duplicate_post_id ) {
				$duplicate_post = get_edit_post_link( $duplicate_post_id );
				// Add a notice for the user that this changed, but saving anyway.
				// Translators: %1$s.
				$message = sprintf( __( '<strong>Envira Gallery</strong>: There was <a target="_blank" href="%1$s">already a post on your site</a> with the same slug. Envira generated a unique slug for this gallery.</a>', 'envira-gallery' ), $duplicate_post );
			} else {
				// Add a notice for the user that this changed, but saving anyway.
				$message = sprintf( __( '<strong>Envira Gallery</strong>: There was already a post on your site with the same slug. Envira generated a unique slug for this gallery.</a>', 'envira-gallery' ) );
			}

			$notices->display_inline_notice( 'warning-post-slug-exists', false, $message, 'warning', false, false, true, false );

		}
	}

	/**
	 * Add base CSS class for white labeling
	 *
	 * @since 1.7.0
	 * @param    array $classes    CSS Classes.
	 */
	public function whitelabel_styles( $classes ) {
		return $classes .= ' envira-whitelabel';
	}

	/**
	 * Changes strings in the modal image selector if we're editing an Envira Gallery
	 *
	 * @since 1.4.0
	 *
	 * @param    array $strings    Media View Strings.
	 * @return   array               Media View Strings
	 */
	public function media_view_strings( $strings ) {

		// Check if we can get a current screen
		// If not, we're not on an Envira screen so we can bail.
		if ( ! function_exists( 'get_current_screen' ) ) {
			return $strings;
		}

		// Get the current screen.
		$screen = get_current_screen();

		// Check we're editing an Envira CPT.
		if ( 'envira' !== $screen->post_type || ! $screen ) {
			return $strings;
		}

		// If here, we're editing an Envira CPT
		// Modify some of the media view's strings.
		$strings['insertIntoPost'] = __( 'Insert into Gallery', 'envira-gallery' );
		$strings['inserting']      = __( 'Inserting...', 'envira-gallery' );

		// Allow addons to filter strings.
		$strings = apply_filters( 'envira_gallery_media_view_strings', $strings, $screen );

		// Return.
		return $strings;

	}

	/**
	 * Appends the "Select Files From Other Sources" button to the Media Uploader, which is called using WordPress'
	 * media_upload_form() function.
	 *
	 * Also appends a hidden upload progress bar, which is displayed by js/media-upload.js when the user uploads images
	 * from their computer.
	 *
	 * CSS positions this button to improve the layout.
	 *
	 * @since 1.7.0
	 */
	public function append_media_upload_form() {

		// Load view.
		envira_load_admin_partial(
			'metabox-media-upload-form',
			array(
				'instance' => $this,
			)
		);

	}

	/**
	 * Loads styles for our metaboxes.
	 *
	 * @since 1.7.0
	 *
	 * @return null Return early if not on the proper screen.
	 */
	public function styles() {

		// Get current screen.
		$screen = get_current_screen();

		// Bail if we're not on the Envira Post Type screen.
		if ( 'envira' !== $screen->post_type || 'post' !== $screen->base ) {
			return;
		}

		// Load necessary metabox styles.
		wp_register_style( ENVIRA_SLUG . '-metabox-style', plugins_url( 'assets/css/metabox.css', ENVIRA_FILE ), array(), ENVIRA_VERSION );
		wp_enqueue_style( ENVIRA_SLUG . '-metabox-style' );

		// Fire a hook to load in custom metabox styles.
		do_action( 'envira_gallery_metabox_styles' );

	}

	/**
	 * Loads scripts for our metaboxes.
	 *
	 * @since 1.7.0
	 *
	 * @global int    $id   The current post ID.
	 * @global object $post The current post object.
	 * @param  mixed $hook The hook.
	 * @return null         Return early if not on the proper screen.
	 */
	public function scripts( $hook ) {

		global $id, $post;

		// Get current screen.
		$screen = get_current_screen();

		// Bail if we're not on the Envira Post Type screen.
		if ( 'envira' !== $screen->post_type || 'post' !== $screen->base ) {
			return;
		}

		// Set the post_id for localization.
		$post_id = isset( $post->ID ) ? $post->ID : (int) $id;

		// Sortables.
		wp_enqueue_script( 'jquery-ui-sortable' );

		// Image Uploader.
		wp_enqueue_media( array( 'post' => $post_id ) );

		add_filter( 'plupload_init', array( $this, 'plupload_init' ) );

		// Tabs.
		wp_register_script( ENVIRA_SLUG . '-tabs-script', plugins_url( 'assets/js/min/tabs-min.js', ENVIRA_FILE ), array( 'jquery' ), ENVIRA_VERSION, true );
		wp_enqueue_script( ENVIRA_SLUG . '-tabs-script' );

		// Clipboard.
		wp_register_script( ENVIRA_SLUG . '-clipboard-script', plugins_url( 'assets/js/min/clipboard-min.js', ENVIRA_FILE ), array( 'jquery' ), ENVIRA_VERSION, true );
		wp_enqueue_script( ENVIRA_SLUG . '-clipboard-script' );

		// Conditional Fields.
		wp_register_script( ENVIRA_SLUG . '-conditional-fields-script', plugins_url( 'assets/js/min/conditional-fields-min.js', ENVIRA_FILE ), array( 'jquery' ), ENVIRA_VERSION, true );
		wp_enqueue_script( ENVIRA_SLUG . '-conditional-fields-script' );

		// Gallery / Album Selection.
		wp_enqueue_script( ENVIRA_SLUG . '-gallery-select-script', plugins_url( 'assets/js/gallery-select.js', ENVIRA_FILE ), array( 'jquery' ), ENVIRA_VERSION, true );
		wp_localize_script(
			ENVIRA_SLUG . '-gallery-select-script',
			'envira_gallery_select',
			array(
				'get_galleries_nonce' => wp_create_nonce( 'envira-gallery-editor-get-galleries' ),
				'modal_title'         => __( 'Insert', 'envira-gallery' ),
				'insert_button_label' => __( 'Insert', 'envira-gallery' ),
			)
		);

		// Metaboxes.
		wp_register_script( ENVIRA_SLUG . '-metabox-script', plugins_url( 'assets/js/min/metabox-min.js', ENVIRA_FILE ), array( 'jquery', 'plupload-handlers', 'quicktags', 'jquery-ui-sortable' ), ENVIRA_VERSION, true );
		wp_enqueue_script( ENVIRA_SLUG . '-metabox-script' );
		wp_localize_script(
			ENVIRA_SLUG . '-metabox-script',
			'envira_gallery_metabox',
			array(
				'ajax'                           => admin_url( 'admin-ajax.php' ),
				'change_nonce'                   => wp_create_nonce( 'envira-gallery-change-type' ),
				'id'                             => $post_id,
				'import'                         => __( 'You must select a file to import before continuing.', 'envira-gallery' ),
				'insert_nonce'                   => wp_create_nonce( 'envira-gallery-insert-images' ),
				'inserting'                      => __( 'Inserting...', 'envira-gallery' ),
				'library_search'                 => wp_create_nonce( 'envira-gallery-library-search' ),
				'load_gallery'                   => wp_create_nonce( 'envira-gallery-load-gallery' ),
				'load_image'                     => wp_create_nonce( 'envira-gallery-load-image' ),
				'media_position'                 => envira_get_setting( 'media_position' ),
				'move_media_nonce'               => wp_create_nonce( 'envira-gallery-move-media' ),
				'move_media_modal_title'         => __( 'Move Media to Gallery', 'envira-gallery' ),
				'move_media_insert_button_label' => __( 'Move Media to Selected Gallery', 'envira-gallery' ),
				'preview_nonce'                  => wp_create_nonce( 'envira-gallery-change-preview' ),
				'refresh_nonce'                  => wp_create_nonce( 'envira-gallery-refresh' ),
				'remove'                         => __( 'Are you sure you want to remove this image from the gallery?', 'envira-gallery' ),
				'remove_multiple'                => __( 'Are you sure you want to remove these images from the gallery?', 'envira-gallery' ),
				'remove_nonce'                   => wp_create_nonce( 'envira-gallery-remove-image' ),
				'save_nonce'                     => wp_create_nonce( 'envira-gallery-save-meta' ),
				'set_user_setting_nonce'         => wp_create_nonce( 'envira-gallery-set-user-setting' ),
				'saving'                         => __( 'Saving...', 'envira-gallery' ),
				'saved'                          => __( 'Saved!', 'envira-gallery' ),
				'sort'                           => wp_create_nonce( 'envira-gallery-sort' ),
				'uploader_files_computer'        => __( 'Select Files from Your Computer', 'envira-gallery' ),
				'active'                         => esc_attr__( 'Active', 'envira-gallery' ),
				'draft'                          => esc_attr__( 'Draft', 'envira-gallery' ),
				'selected'                       => esc_attr__( 'Selected', 'envira-gallery' ),
				'select_all'                     => esc_attr__( 'Select All', 'envira-gallery' ),
				'whitelabel'                     => $this->whitelabel,

			)
		);

		wp_register_script( ENVIRA_SLUG . '-media-insert-third-party', plugins_url( 'assets/js/media-insert-third-party.js', ENVIRA_FILE ), array( 'jquery' ), ENVIRA_VERSION, true );
		wp_enqueue_script( ENVIRA_SLUG . '-media-insert-third-party' );
		wp_localize_script(
			ENVIRA_SLUG . '-media-insert-third-party',
			'envira_gallery_media_insert',
			array(
				'nonce'   => wp_create_nonce( 'envira-gallery-media-insert' ),
				'post_id' => $post_id,
				// Addons must add their slug/base key/value pair to this array to appear within the "Insert from Other Sources" modal.
				'addons'  => apply_filters( 'envira_gallery_media_insert_third_party_sources', array(), $post_id ),
			)
		);

		// Link Search.
		wp_enqueue_script( 'wp-link' );

		// Add custom CSS for hiding specific things.
		add_action( 'admin_head', array( $this, 'meta_box_css' ) );

		// Fire a hook to load custom metabox scripts.
		do_action( 'envira_gallery_metabox_scripts' );

	}

	/**
	 * Remove plugins scripts that break Envira's admin.
	 *
	 * @access public
	 * @return void
	 */
	public function fix_plugin_js_conflicts() {

		global $id, $post;

		// Get current screen.
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();

		// Bail if we're not on the Envira Post Type screen.
		if ( 'envira' !== $screen->post_type ) {
			return;
		}

		wp_dequeue_style( 'thrive-theme-options' );
		wp_dequeue_script( 'thrive-theme-options' );
		wp_dequeue_script( 'ngg-igw' );
		wp_dequeue_script( 'cherry_plugin_script' );
		wp_dequeue_script( 'yoast-seo-post-scraper' ); /* Prevents 'Uncaught TypeError: Cannot read property 'getEditedPostAttribute' of undefined' in Premium 7.3 GH: 3105 */
		wp_dequeue_script( 'yoast_ga_admin' ); /* Yoast Clicky Plugin */
		if ( function_exists( 'wpsm_ac_editor_popup_content' ) ) {
			remove_action( 'admin_footer', 'wpsm_ac_editor_popup_content' );
		}

	}

	/**
	 * Adds Envira CPT to array that third party plugins use to not implant their
	 * code into Envira admin screens, which results in breakage. Conflict solver.
	 *
	 * @since 1.7.0
	 *
	 * @param array $excluded Params.
	 * @return array Params
	 */
	public function envira_excluded_post_types( $excluded ) {
		$excluded[] = 'envira';
		$excluded[] = 'envira_album';
		return $excluded;
	}

	/**
	 * Amends the default Plupload parameters for initialising the Media Uploader, to ensure
	 * the uploaded image is attached to our Envira CPT
	 *
	 * @since 1.7.0
	 *
	 * @param array $params Params.
	 * @return array Params
	 */
	public function plupload_init( $params ) {

		global $post_ID;

		// Define the Envira Gallery Post ID, so Plupload attaches the uploaded images
		// to this Envira Gallery.
		$params['multipart_params']['post_id'] = $post_ID;

		// Build an array of supported file types for Plupload.
		$supported_file_types = envira_get_supported_filetypes();

		// Assign supported file types and return.
		$params['filters']['mime_types'] = $supported_file_types;

		// Return and apply a custom filter to our init data.
		$params = apply_filters( 'envira_gallery_plupload_init', $params, $post_ID );
		return $params;

	}

	/**
	 * Hides unnecessary meta box items on Envira post type screens.
	 *
	 * @since 1.7.0
	 */
	public function meta_box_css() {

		?>
		<style type="text/css">.misc-pub-section:not(.misc-pub-post-status) { display: none; }</style>
		<?php

		if ( envira_get_setting( 'standalone_enabled' ) && ! empty( get_current_screen()->post_type ) && get_current_screen()->post_type === 'envira' ) {
			?>
			<style type="text/css">
				#slugdiv { display: none; }
				.misc-pub-section { display: block !important; }
			</style>
			<?php
		}

		// Fire action for CSS on Envira post type screens.
		do_action( 'envira_gallery_admin_css' );

	}

	/**
	 * Creates metaboxes for handling and managing galleries.
	 *
	 * @since 1.7.0
	 */
	public function add_meta_boxes() {

		global $post;

		// bail if nothings there.
		if ( ! isset( $post ) ) {
			return;
		}

		// Check we're on an Envira Gallery.
		if ( 'envira' !== $post->post_type ) {
			return;
		}

		// Let's remove all of those dumb metaboxes from our post type screen to control the experience.
		$this->remove_all_the_metaboxes();

		$data = envira_get_gallery( $post->ID );

		// Types Metabox
		// Allows the user to upload images or choose an External Gallery Type
		// We don't display this if the Gallery is a Dynamic or Default Gallery, as these settings don't apply.
		$type = envira_get_config( 'type', $data );

		// Settings Metabox.
		add_meta_box( 'envira-gallery-settings', __( 'Envira Gallery Settings', 'envira-gallery' ), array( $this, 'meta_box_callback' ), 'envira', 'normal', 'high' );

		// If the default addon is active, check to see if this the default gallery - we don't need this screen on there!
		if ( class_exists( 'Envira_Defaults' ) && isset( $post->ID ) ) {
			$default_id_gallery = get_option( 'envira_default_gallery' );
			$default_id_album   = get_option( 'envira_default_album' );
			if ( $post->ID === $default_id_gallery || $post->ID === $default_id_album ) {
				return;
			}
		}

		// If the dynamic addon is active, check to see if this the dynamic gallery - we don't need this screen on there!
		if ( class_exists( 'Envira_Dynamic' ) && isset( $post->ID ) ) {
			$dynamic_id_gallery = get_option( 'envira_dynamic_gallery' );
			$dynamic_id_album   = get_option( 'envira_dynamic_album' );
			if ( $post->ID === $dynamic_id_gallery || $post->ID === $dynamic_id_album ) {
				return;
			}
		}

		if ( ! in_array( $type, array( 'defaults', 'dynamic' ), true ) ) {
			add_meta_box( 'envira-gallery', __( 'Envira Gallery', 'envira-gallery' ), array( $this, 'meta_box_gallery_callback' ), 'envira', 'normal', 'high' );
		}

		// Display the Gallery Code metabox if we're editing an existing Gallery.
		if ( 'auto-draft' !== $post->post_status ) {
			add_meta_box( 'envira-gallery-code', apply_filters( 'envira_whitelabel_name', 'Envira' ) . __( ' Code', 'envira-gallery' ), array( $this, 'meta_box_gallery_code_callback' ), 'envira', 'side', 'default' );
		}

		// Output 'Select Files from Other Sources' button on the media uploader form.
		add_action( 'post-plupload-upload-ui', array( $this, 'append_media_upload_form' ), 1 );
		add_action( 'post-html-upload-ui', array( $this, 'append_media_upload_form' ), 1 );

	}

	/**
	 * Removes all the metaboxes except the ones I want on MY POST TYPE. RAGE.
	 *
	 * @since 1.7.0
	 *
	 * @global array $wp_meta_boxes Array of registered metaboxes.
	 * @return void $for_my_buyers Happy customers with no spammy metaboxes!
	 */
	public function remove_all_the_metaboxes() {

		global $wp_meta_boxes;

		// This is the post type you want to target. Adjust it to match yours.
		$post_type = 'envira';

		// These are the metabox IDs you want to pass over. They don't have to match exactly. preg_match will be run on them.
		$pass_over_defaults = array( 'submitdiv', 'envira' );

		if ( envira_get_setting( 'standalone_enabled' ) ) {
			$pass_over_defaults[] = 'slugdiv';
			$pass_over_defaults[] = 'authordiv';
			$pass_over_defaults[] = 'wpseo_meta';
			$pass_over_defaults[] = 'postimagediv';
		}

		$pass_over = apply_filters( 'envira_gallery_metabox_ids', $pass_over_defaults );

		// All the metabox contexts you want to check.
		$contexts_defaults = array( 'normal', 'advanced', 'side' );
		$contexts          = apply_filters( 'envira_gallery_metabox_contexts', $contexts_defaults );

		// All the priorities you want to check.
		$priorities_defaults = array( 'high', 'core', 'default', 'low' );
		$priorities          = apply_filters( 'envira_gallery_metabox_priorities', $priorities_defaults );

		// Loop through and target each context.
		foreach ( $contexts as $context ) {
			// Now loop through each priority and start the purging process.
			foreach ( $priorities as $priority ) {
				if ( isset( $wp_meta_boxes[ $post_type ][ $context ][ $priority ] ) ) {
					foreach ( (array) $wp_meta_boxes[ $post_type ][ $context ][ $priority ] as $id => $metabox_data ) {
						// If the metabox ID to pass over matches the ID given, remove it from the array and continue.
						if ( in_array( $id, $pass_over, true ) ) {
							unset( $pass_over[ $id ] );
							continue;
						}

						// Otherwise, loop through the pass_over IDs and if we have a match, continue.
						foreach ( $pass_over as $to_pass ) {
							if ( preg_match( '#^' . $id . '#i', $to_pass ) ) {
								continue;
							}
						}

						// If we reach this point, remove the metabox completely.
						unset( $wp_meta_boxes[ $post_type ][ $context ][ $priority ][ $id ] );
					}
				}
			}
		}

	}

	/**
	 * Adds an envira-gallery class to the form when adding or editing an Album,
	 * so our plugin's CSS and JS can target a specific element and its children.
	 *
	 * @since 1.7.0
	 *
	 * @param   WP_Post $post   WordPress Post.
	 */
	public function add_form_class( $post ) {

		// Check the Post is a Gallery.
		if ( 'envira' !== get_post_type( $post ) ) {
			return;
		}

		echo ' class="envira-gallery"';

	}

	/**
	 * Callback for displaying the Gallery Type section.
	 *
	 * @since 1.7.0
	 *
	 * @param object $post The current post object.
	 */
	public function meta_box_gallery_callback( $post ) {

		// Load view.
		envira_load_admin_partial(
			'metabox-gallery-type',
			array(
				'post'     => $post,
				'types'    => $this->get_envira_types( $post ),
				'instance' => $this,
			)
		);

	}

	/**
	 * Callback for displaying the Gallery Settings section.
	 *
	 * @since 1.7.0
	 *
	 * @param object $post The current post object.
	 */
	public function meta_box_callback( $post ) {

		// Keep security first.
		wp_nonce_field( 'envira-gallery', 'envira-gallery' );

		// Load view.
		envira_load_admin_partial(
			'metabox-gallery-settings',
			array(
				'post' => $post,
				'tabs' => $this->get_envira_tab_nav(),
			)
		);

	}

	/**
	 * Callback for displaying the Preview metabox.
	 *
	 * @since 1.7.0
	 *
	 * @param object $post The current post object.
	 */
	public function meta_box_preview_callback( $post ) {

		// Get the gallery data.
		$data = get_post_meta( $post->ID, '_eg_gallery_data', true );

		// Output the display based on the type of slider being created.
		echo '<div id="envira-gallery-preview-main" class="envira-clear">';

		$this->preview_display( envira_get_config( 'type', $data ), $data );

		echo '</div><div class="spinner"></div>';

	}

	/**
	 * Callback for displaying the Gallery Code metabox.
	 *
	 * @since 1.7.0
	 *
	 * @param object $post The current post object.
	 */
	public function meta_box_gallery_code_callback( $post ) {

		// Load view.
		envira_load_admin_partial(
			'metabox-gallery-code',
			array(
				'post'         => $post,
				'gallery_data' => get_post_meta( $post->ID, '_eg_gallery_data', true ),
			)
		);

	}

	/**
	 * Returns the types of galleries available.
	 *
	 * @since 1.7.0
	 *
	 * @param object $post The current post object.
	 * @return array         Array of gallery types to choose.
	 */
	public function get_envira_types( $post ) {

		$types = array(
			'default' => __( 'Default', 'envira-gallery' ),
		);

		return apply_filters( 'envira_gallery_types', $types, $post );

	}

	/**
	 * Returns the tabs to be displayed in the settings metabox.
	 *
	 * @since 1.7.0
	 *
	 * @return array Array of tab information.
	 */
	public function get_envira_tab_nav() {

		$tabs = array(
			'images'   => __( 'Gallery', 'envira-gallery' ),
			'config'   => __( 'Configuration', 'envira-gallery' ),
			'lightbox' => __( 'Lightbox', 'envira-gallery' ),
			'mobile'   => __( 'Mobile', 'envira-gallery' ),
		);

		if ( envira_get_setting( 'standalone_enabled' ) ) {
			$tabs['standalone'] = __( 'Standalone', 'envira-gallery' );
		}

		$tabs = apply_filters( 'envira_gallery_tab_nav', $tabs );

		// "Misc" tab is required.
		$tabs['misc'] = __( 'Misc', 'envira-gallery' );

		return $tabs;

	}

	/**
	 * Callback for displaying the settings UI for the Gallery tab.
	 *
	 * @since 1.7.0
	 *
	 * @param object $post The current post object.
	 */
	public function images_tab( $post ) {

		$data = envira_get_gallery( $post->ID );

		// Output the display based on the type of slider being created.
		echo '<div id="envira-gallery-main" class="envira-clear">';

		// Allow Addons to display a WordPress-style notification message.
		echo wp_kses( apply_filters( 'envira_gallery_images_tab_notice', '', $post ), apply_filters( 'envira_gallery_allowed_html', $this->wp_kses_allowed_html, $data ) );

		// Output the tab panel for the Gallery Type.
		$this->images_display( envira_get_config( 'type', $data ), $post );

		echo '</div>
				 <div class="spinner"></div>';

	}

	/**
	 * Determines the Images tab display based on the type of gallery selected.
	 *
	 * @since 1.7.0
	 *
	 * @param string $type The type of display to output.
	 * @param object $post The current post object.
	 */
	public function images_display( $type = 'default', $post ) {

		// Output a unique hidden field for settings save testing for each type of slider.
		echo '<input type="hidden" name="_envira_gallery[type_' . esc_attr( $type ) . ']" value="1" />';

		// Output the display based on the type of slider available.
		switch ( $type ) {
			case 'default':
				$this->do_default_display( $post );
				break;
			default:
				do_action( 'envira_gallery_display_' . $type, $post );
				break;
		}

	}

	/**
	 * Determines the Preview metabox display based on the type of gallery selected.
	 *
	 * @since 1.7.0
	 *
	 * @param string $type The type of display to output.
	 * @param object $data Gallery Data.
	 */
	public function preview_display( $type = 'default', $data ) {

		// Output the display based on the type of slider available.
		switch ( $type ) {
			case 'default':
				// Don't preview anything.
				break;
			default:
				do_action( 'envira_gallery_preview_' . $type, $data );
				break;
		}

	}

	/**
	 * Callback for displaying the default gallery UI.
	 *
	 * @since 1.7.0
	 *
	 * @param object $post The current post object.
	 */
	public function do_default_display( $post ) {

		// Prepare output data.
		$gallery_data = get_post_meta( $post->ID, '_eg_gallery_data', true );

		if ( ! envira_get_config( 'sort_order', $gallery_data ) ) {
			// by default, sort is on because manual sort is on when gallery screen comes up.
			$is_sortable = 1;
		} else {
			$is_sortable = envira_get_config( 'sort_order', $gallery_data ) === '0' ? 1 : 0;
		}

		// Determine whether to use the list or grid layout, depending on the user's setting.
		$layout = get_user_setting( 'envira_gallery_image_view', 'grid' );
		$hide   = empty( $gallery_data['gallery'] ) ? ' envira-show' : ' envira-hidden';
		$show   = ! empty( $gallery_data['gallery'] ) ? ' envira-show' : ' envira-hidden';
		?>
		<div id="envira-empty-gallery" class="<?php echo esc_attr( $hide ); ?>">
			<div>
				<?php if ( $this->whitelabel ) : ?>
					<?php do_action( 'envira_whitelabel_default_display' ); ?>
				<?php else : ?>
					<img class="envira-item-img" src="<?php echo esc_url( plugins_url( 'assets/images/envira-logo-color.png', ENVIRA_FILE ) ); ?>" />
					<h3><?php esc_html_e( 'Create your Gallery by adding your media files above.', 'envira-gallery' ); ?></h3>
					<p class="envira-help-text"><?php esc_html_e( 'Need some help?', 'envira-gallery' ); ?> <a href="http://enviragallery.com/docs/creating-first-envira-gallery/" target="_blank"><?php esc_html_e( 'Learn how to add media and create a Gallery', 'envira-gallery' ); ?></a></p>
				<?php endif; ?>
				</div>
		</div>
		<div class="envira-content-images <?php echo esc_attr( $show ); ?>">
		<!-- Title and Help -->
		<p class="envira-intro">
			<?php esc_html_e( 'Currently in your Gallery', 'envira-gallery' ); ?>

			<small>
				<?php if ( $this->whitelabel ) : ?>
					<?php do_action( 'envira_whitelabel_tab_text_images' ); ?>
				<?php else : ?>
					<?php esc_html_e( 'Need some help?', 'envira-gallery' ); ?>
					<a href="http://enviragallery.com/docs/creating-first-envira-gallery/" class="envira-doc" target="_blank">
						<?php esc_html_e( 'Read the Documentation', 'envira-gallery' ); ?>
					</a>
					<?php esc_html_e( 'or', 'envira-gallery' ); ?>
					<a href="https://www.youtube.com/embed/F9_wOefuBaw?autoplay=1&amp;rel=0" class="envira-video" target="_blank">
						<?php esc_html_e( 'Watch a Video', 'envira-gallery' ); ?>
					</a>
				<?php endif; ?>
			</small>

		</p>

			<nav class="envira-tab-options">

				<label class="select-all-label">

					<input id="select-all" class="envira-select-all" type="checkbox">

					<span class="select-all"><?php esc_html_e( 'Select All', 'envira-gallery' ); ?></span> (<span class="envira-count"><?php echo intval( envira_get_gallery_image_count( $post->ID ) ); ?></span>)
					<a href="#" class="envira-clear-selected"><?php esc_html_e( 'Clear Selected', 'envira-gallery' ); ?></a>

				</label>

				<ul class="envira-right-options">
					<li class="envira-select filter-images">

						<label>
							<input id="envira-filter-images" type="text" placeholder="<?php esc_html_e( 'Filter', 'envira-gallery' ); ?>">
						</label>

					</li>

					<li class="envira-select sorting-options">
						<select id="envira-config-image-sort" name="_envira_gallery[random]" class="envira-chosen" data-envira-chosen-options='{ "disable_search":"true", "width": "100%" }'>
							<?php foreach ( (array) envira_get_sorting_options() as $i => $data ) : ?>
								<option value="<?php echo esc_html( $data['value'] ); ?>"<?php selected( $data['value'], envira_get_config( 'random', $gallery_data ) ); ?>><?php echo esc_html( $data['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</li>
					<li class="envira-select sorting-directions">
						<select id="envira-config-image-sort-dir" name="_envira_gallery[sorting_direction]" class="envira-chosen" data-envira-chosen-options='{ "disable_search":"true", "width": "100%" }'>

							<?php foreach ( (array) envira_get_sorting_directions() as $i => $data ) { ?>

								<option value="<?php echo esc_html( $data['value'] ); ?>"<?php selected( $data['value'], envira_get_config( 'sorting_direction', $gallery_data ) ); ?>><?php echo esc_html( $data['name'] ); ?></option>

							<?php } ?>

						</select>
					</li>

					<!-- List / Grid View -->
					<li class="grid-view">
						<a href="#" class="dashicons dashicons-grid-view<?php echo ( 'grid' === $layout ? ' selected' : '' ); ?>" data-view="#envira-gallery-output" data-view-style="grid">
							<span><?php esc_html_e( 'Grid View', 'envira-gallery' ); ?></span>
						</a>
					</li>

					<li class="list-view">
						<a href="#" class="dashicons dashicons-list-view<?php echo ( 'list' === $layout ? ' selected' : '' ); ?>" data-view="#envira-gallery-output" data-view-style="list">
							<span><?php esc_html_e( 'List View', 'envira-gallery' ); ?></span>
						</a>
					</li>


				</ul>
			</nav>
			<!-- Bulk Edit / Delete Buttons -->
			<nav class="envira-select-options">
				<div class="envira-label"><?php esc_html_e( 'Selected Actions:', 'envira-gallery' ); ?></div>
				<a href="#" class="button envira-gallery-images-edit"><?php esc_html_e( 'Edit', 'envira-gallery' ); ?></a>
				<a href="#" class="button envira-gallery-images-move" data-action="gallery"><?php esc_html_e( 'Move to another Gallery', 'envira-gallery' ); ?></a>
				<a href="#" class="button button-danger envira-gallery-images-delete"><?php esc_html_e( 'Delete from Gallery', 'envira-gallery' ); ?></a>
			</nav>
			<?php

			do_action( 'envira_gallery_do_default_display', $post );
			?>

		<ul id="envira-gallery-output" class="envira-gallery-images-output <?php echo esc_attr( $layout ); ?>" data-view="<?php echo esc_attr( $layout ); ?>" data-sortable="<?php echo esc_html( $is_sortable ); ?>">
			<?php
			if ( ! empty( $gallery_data['gallery'] ) ) {
				foreach ( $gallery_data['gallery'] as $id => $data ) {
					echo wp_kses( $this->get_gallery_item( $id, $data, $post->ID ), apply_filters( 'envira_gallery_allowed_html', $this->wp_kses_allowed_html, $data ) );
				}
			}
			?>
		</ul>

			<!-- Bulk Edit / Delete Buttons -->
			<nav class="envira-select-options">
				<div class="envira-label"><?php esc_html_e( 'Selected Actions:', 'envira-gallery' ); ?></div>
				<a href="#" class="button envira-gallery-images-edit"><?php esc_html_e( 'Edit', 'envira-gallery' ); ?></a>
				<a href="#" class="button envira-gallery-images-move" data-action="gallery"><?php esc_html_e( 'Move to another Gallery', 'envira-gallery' ); ?></a>
				<a href="#" class="button button-danger envira-gallery-images-delete"><?php esc_html_e( 'Delete from Gallery', 'envira-gallery' ); ?></a>
			</nav>

		</div>
		<?php

	}
	/**
	 * Helper method for retrieving the gallery layout for an item in the admin.
	 *
	 * Also defines the item's model which is used in assets/js/media-edit.js
	 *
	 * @since 1.7.0
	 *
	 * @param int   $id         The ID of the item to retrieve.
	 * @param array $item       The item data (i.e. image / video).
	 * @param int   $post_id    The current post ID.
	 * @return string               The HTML output for the gallery item.
	 */
	public function get_gallery_item( $id, $item, $post_id = 0 ) {

		// Get thumbnail.
		$thumbnail = wp_get_attachment_image_src( $id, 'thumbnail' );

		// Add id to $item for Backbone model.
		$item['id'] = $id;

		// Allow addons to populate the item's data - for example, tags which are stored against the attachment.
		$item               = apply_filters( 'envira_gallery_get_gallery_item', $item, $id, $post_id );
		$item['alt']        = str_replace( '&quot;', '\"', $item['alt'] );
		$item['_thumbnail'] = $thumbnail[0]; // Never saved against the gallery item, just used for the thumbnail output in the Edit Gallery screen.

		// JSON encode based on PHP version.
		$json = wp_json_encode( $item, JSON_HEX_APOS );

		// Buffer the output.
		ob_start();
		?>
		<li id="<?php echo esc_html( $id ); ?>" class="envira-gallery-image envira-gallery-status-<?php echo esc_attr( $item['status'] ); ?>" data-envira-gallery-image="<?php echo intval( $id ); ?>" data-envira-gallery-image-model='<?php echo esc_html( htmlspecialchars( $json, ENT_QUOTES, 'UTF-8' ) ); ?>'>
			<img src="<?php echo esc_url( $item['_thumbnail'] ); ?>" alt="<?php echo esc_attr( $item['alt'] ); ?>" />
			<div class="additional additional-list">
				<?php
					// Addons can add content to this meta section, which is displayed when in the List View.
					$output_html = apply_filters( 'envira_gallery_metabox_output_gallery_item_meta', '', $item, $id, $post_id, 'list' );
					echo wp_kses( $output_html, $this->wp_kses_allowed_html );
				?>
			</div>
			<div class="meta meta-grid">
				<div class="title">
					<span>
						<?php
						// Output Title.
						echo ( isset( $item['title'] ) ? esc_html( $item['title'] ) : '' );

						// If the title exceeds 20 characters, the grid view will deliberately only show the first line of the title.
						// Therefore we need to make it clear to the user that the full title is there by way of a hint.
						?>
					</span>
					<a class="hint <?php echo ( ( strlen( $item['title'] ) > 20 ) ? '' : ' hidden' ); ?>" title="<?php echo ( isset( $item['title'] ) ? esc_html( $item['title'] ) : '' ); ?>">...</a>
				</div>
				<div class="additional additional-grid">
					<?php
					// Addons can add content to this meta section, which is displayed when in the List View.
					$output_html = apply_filters( 'envira_gallery_metabox_output_gallery_item_meta', '', $item, $id, $post_id, 'grid' );
					echo wp_kses( $output_html, $this->wp_kses_allowed_html );
					?>

				</div>
			</div>

			<a href="#" class="check"><div class="media-modal-icon"></div></a>
			<a href="#" class="dashicons dashicons-trash envira-gallery-remove-image" title="<?php esc_html_e( 'Remove Image from Gallery?', 'envira-gallery' ); ?>"></a>
			<a href="#" class="dashicons dashicons-edit envira-gallery-modify-image" title="<?php esc_html_e( 'Modify Image', 'envira-gallery' ); ?>"></a>

					<?php if ( 'active' === $item['status'] ) : ?>

					<a href="#" class="dashicons envira-active-item envira-item-status grid-status" data-status="active" data-envira-tooltip="<?php esc_attr_e( 'Active', 'soliloquy' ); ?>" data-id="<?php echo intval( $id ); ?>" title="<?php esc_attr_e( 'Status: Published', 'soliloquy' ); ?>"><span class="dashicons dashicons-visibility"></span></a>

					<?php else : ?>

					<a href="#" class="dashicons envira-draft-item envira-item-status grid-status" data-status="draft" data-envira-tooltip="<?php esc_attr_e( 'Draft', 'soliloquy' ); ?>" data-id="<?php echo intval( $id ); ?>"  title="<?php esc_attr_e( 'Status: Draft', 'soliloquy' ); ?>"><span class="dashicons dashicons-hidden"></span></a>

					<?php endif; ?>
		</li>
		<?php
		return ob_get_clean();

	}

	/**
	 * Callback for displaying the settings UI for the Configuration tab.
	 *
	 * @since 1.7.0
	 *
	 * @param object $post The current post object.
	 */
	public function config_tab( $post ) {

		$gallery_data = get_post_meta( $post->ID, '_eg_gallery_data', true );
		?>

		<div id="envira-config">
			<!-- Title and Help -->
			<p class="envira-intro">

				<?php esc_html_e( 'Gallery Settings', 'envira-gallery' ); ?>

				<small>

				<?php esc_html_e( 'The settings below adjust the basic configuration options for the gallery.', 'envira-gallery' ); ?><br />

				<?php if ( $this->whitelabel ) : ?>

					<?php do_action( 'envira_whitelabel_tab_text_config' ); ?>

				<?php else : ?>

						<?php esc_html_e( 'Need some help?', 'envira-gallery' ); ?>
						<a href="http://enviragallery.com/docs/creating-first-envira-gallery/" class="envira-doc" target="_blank">
							<?php esc_html_e( 'Read the Documentation', 'envira-gallery' ); ?>
						</a>
						or
						<a href="https://www.youtube.com/embed/F9_wOefuBaw?autoplay=1&amp;rel=0" class="envira-video" target="_blank">
							<?php esc_html_e( 'Watch a Video', 'envira-gallery' ); ?>
						</a>

				<?php endif; ?>

				</small>

			</p>
			<table class="form-table" style="margin-bottom: 0;">
				<tbody>
					<tr id="envira-config-columns-box">
						<th scope="row">
							<label for="envira-config-columns"><?php esc_html_e( 'Number of Gallery Columns', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<select id="envira-config-columns" name="_envira_gallery[columns]">
								<?php foreach ( (array) envira_get_columns() as $i => $data ) : ?>
									<option value="<?php echo esc_html( $data['value'] ); ?>"<?php selected( $data['value'], envira_get_config( 'columns', $gallery_data ) ); ?>><?php echo esc_html( $data['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Determines the number of columns in the gallery. Automatic will attempt to fill each row as much as possible before moving on to the next row.', 'envira-gallery' ); ?></p>
						</td>
					</tr>

					<tr id="envira-config-lazy-loading-box">
						<th scope="row">
							<label for="envira-config-lazy-loading"><?php esc_html_e( 'Enable Lazy Loading?', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<input id="envira-config-lazy-loading" type="checkbox" name="_envira_gallery[lazy_loading]" value="<?php echo esc_html( envira_get_config( 'lazy_loading', $gallery_data ) ); ?>" <?php checked( envira_get_config( 'lazy_loading', $gallery_data, envira_get_config_default( 'lazy_loading' ) ), 1 ); ?> />
							<span class="description"><?php esc_html_e( 'Enables or disables lazy loading, which helps with performance by loading thumbnails only when they are visible. See our documentation for more information.', 'envira-gallery' ); ?></span>
						</td>
					</tr>

					<tr id="envira-config-lazy-loading-delay">
						<th scope="row">
							<label for="envira-config-lazy-loading-delay"><?php esc_html_e( 'Lazy Loading Delay', 'envira-gallery' ); ?></label>
						</th>
							<td>
								<input id="envira-config-lazy-loading-delay" type="number" name="_envira_gallery[lazy_loading_delay]" value="<?php echo esc_html( envira_get_config( 'lazy_loading_delay', $gallery_data, envira_get_config_default( 'lazy_loading_delay' ) ) ); ?>" /> <span class="envira-unit"><?php esc_html_e( 'milliseconds', 'envira-gallery' ); ?></span>
								<p class="description"><?php esc_html_e( 'Set a delay when new images are loaded', 'envira-gallery' ); ?></p>
							</td>
					</tr>
				</tbody>
			</table>
			<div id="envira-config-justified-settings-box">
				<table class="form-table" style="margin-bottom: 0;">
					<tbody>
						<tr id="envira-config-justified-row-height">
							<th scope="row">
								<label for="envira-config-justified-row-height"><?php esc_html_e( 'Automatic Layout: Row Height', 'envira-gallery' ); ?></label>
							</th>
							<td>
								<input id="envira-config-justified-row-height" type="number" name="_envira_gallery[justified_row_height]" value="<?php echo esc_html( envira_get_config( 'justified_row_height', $gallery_data, envira_get_config_default( 'justified_row_height' ) ) ); ?>" /> <span class="envira-unit"><?php esc_html_e( 'px', 'envira-gallery' ); ?></span>
								<p class="description"><?php esc_html_e( 'Determines how high (in pixels) each row will be. 150px is default. ', 'envira-gallery' ); ?></p>
							</td>
						</tr>
						<tr id="envira-config-justified-margins">
							<th scope="row">
								<label for="envira-config-justified-margins"><?php esc_html_e( 'Automatic Layout: Margins', 'envira-gallery' ); ?></label>
							</th>
							<td>
								<input id="envira-config-justified-margins" type="number" name="_envira_gallery[justified_margins]" value="<?php echo esc_html( envira_get_config( 'justified_margins', $gallery_data, envira_get_config_default( 'justified_margins' ) ) ); ?>" /> <span class="envira-unit"><?php esc_html_e( 'px', 'envira-gallery' ); ?></span>
								<p class="description"><?php esc_html_e( 'Sets the space between the images (defaults to 1)', 'envira-gallery' ); ?></p>
							</td>
						</tr>
						<tr id="envira-config-gallery-justified-last-row">
							<th scope="row">
								<label for="envira-config-gallery-last-row"><?php esc_html_e( 'Automatic Layout: Last Row', 'envira-gallery' ); ?></label>
							</th>
							<td>
								<select id="envira-config-gallery-last-row" name="_envira_gallery[justified_last_row]">
									<?php foreach ( (array) envira_get_justified_last_row() as $i => $data ) : ?>
										<option value="<?php echo esc_html( $data['value'] ); ?>"<?php selected( $data['value'], envira_get_config( 'justified_last_row', $gallery_data ) ); ?>><?php echo esc_html( $data['name'] ); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Sets how the last row is displayed.', 'envira-gallery' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div id="envira-config-description-settings-box">
				<table class="form-table" style="margin-bottom: 0;">
					<tbody>
						<!-- Display Description -->
						<tr id="envira-config-display-description-box">
								<th scope="row">
									<label for="envira-config-display-description"><?php esc_html_e( 'Display Gallery Description?', 'envira-gallery' ); ?></label>
								</th>
								<td>
									<select id="envira-config-display-description" name="_envira_gallery[description_position]">
										<?php
										foreach ( (array) envira_get_display_description_options() as $i => $data ) {
											?>
											<option value="<?php echo esc_html( $data['value'] ); ?>"<?php selected( $data['value'], envira_get_config( 'description_position', $gallery_data, envira_get_config_default( 'description_position' ) ) ); ?>><?php echo esc_html( $data['name'] ); ?></option>
											<?php
										}
										?>
									</select>
									<p class="description"><?php esc_html_e( 'Choose to display a description above or below this gallery\'s images.', 'envira-gallery' ); ?></p>
								</td>
							</tr>

						<!-- Description -->
						<tr id="envira-config-description-box">
								<th scope="row">
									<label for="envira-config-gallery-description"><?php esc_html_e( 'Gallery Description', 'envira-gallery' ); ?></label>
								</th>
								<td>
									<?php
									$description = envira_get_config( 'description', $gallery_data );

									wp_editor(
										$description,
										'envira-gallery-description',
										array(
											'media_buttons' => false,
											'wpautop' => true,
											'tinymce' => true,
											'textarea_name' => '_envira_gallery[description]',
										)
									);
									?>
									<p class="description"><?php esc_html_e( 'The description to display for this gallery.', 'envira-gallery' ); ?></p>
								</td>
							</tr>

						<tr id="envira-config-additional-copy-box-automatic">
							<th scope="row">
								<label for="envira-config-additional-copy-box"><?php esc_html_e( 'Enable Title/Caption?', 'envira-gallery' ); ?></label>
							</th>
							<td>
								<?php
								foreach ( envira_get_additional_copy_options() as $option_value => $option_name ) {

									if ( empty( $option_value ) || 'None' === $option_name ) { // no need to show 'None' for checkboxes.
										continue;
									}

									?>
									<label for="envira-config-social-<?php echo esc_html( $option_value ); ?>" class="label-for-checkbox">
										<input id="envira-config-social-<?php echo esc_html( $option_value ); ?>" type="checkbox" name="_envira_gallery[additional_copy_automatic_<?php echo esc_html( $option_value ); ?>]" value="1" <?php checked( envira_get_config( 'additional_copy_automatic_' . $option_value, $gallery_data, envira_get_config_default( 'additional_copy_automatic_' . $option_value ) ), 1 ); ?> />
										<?php echo esc_html( $option_name ); ?>
									</label>
									<?php
								}
								?>
								<!--<p class="description">
									<?php esc_html_e( 'Select the information that should be shared with each image.', 'envira-social' ); ?>
								</p>-->
							</td>
						</tr>

						<tr id="envira-config-additional-copy-box">
							<th scope="row">
								<label for="envira-config-additional-copy-box"><?php esc_html_e( 'Enable Title/Caption Below Image?', 'envira-gallery' ); ?></label>
							</th>
							<td>
								<?php
								foreach ( envira_get_additional_copy_options() as $option_value => $option_name ) {

									if ( empty( $option_value ) || 'None' === $option_name ) { // no need to show 'None' for checkboxes.
										continue;
									}

									?>
									<label for="envira-config-social-<?php echo esc_html( $option_value ); ?>" class="label-for-checkbox">
										<input id="envira-config-social-<?php echo esc_html( $option_value ); ?>" type="checkbox" name="_envira_gallery[additional_copy_<?php echo esc_html( $option_value ); ?>]" value="1" <?php checked( envira_get_config( 'additional_copy_' . $option_value, $gallery_data, envira_get_config_default( 'additional_copy_' . $option_value ) ), 1 ); ?> />
										<?php echo esc_html( $option_name ); ?>
									</label>

								<?php } ?>

							</td>
						</tr>

						<?php do_action( 'envira_gallery_include_justified_config_box', $post, $gallery_data ); ?>

						<!-- Dimensions -->
						<tr id="envira-config-image-size-box">
						<th scope="row">
							<label for="envira-config-image-size"><?php esc_html_e( 'Image Size', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<select id="envira-config-image-size" name="_envira_gallery[image_size]">
								<?php
								foreach ( (array) envira_get_image_sizes() as $i => $data ) {
									?>
									<option value="<?php echo esc_html( $data['value'] ); ?>"<?php selected( $data['value'], envira_get_config( 'image_size', $gallery_data, envira_get_config_default( 'image_size' ) ) ); ?>><?php echo esc_html( $data['name'] ); ?></option>
									<?php
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'Define the maximum image size for the Gallery view. Default will use the below Image Dimensions; Random will allow you to choose one or more WordPress image sizes, which will be used for the gallery output.', 'envira-gallery' ); ?></p>
						</td>
					</tr>
						<tr id="envira-config-crop-size-box">
						<th scope="row">
							<label for="envira-config-crop-width"><?php esc_html_e( 'Image Dimensions', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<input id="envira-config-crop-width" type="number" name="_envira_gallery[crop_width]" value="<?php echo esc_html( envira_get_config( 'crop_width', $gallery_data, envira_get_config_default( 'crop_width' ) ) ); ?>" /> <?php esc_html_e( 'width (px)', 'envira-gallery' ); ?> &#215; <input id="envira-config-crop-height" type="number" name="_envira_gallery[crop_height]" value="<?php echo esc_html( envira_get_config( 'crop_height', $gallery_data, envira_get_config_default( 'crop_height' ) ) ); ?>" /> <span class="envira-unit"><?php esc_html_e( 'height (px)', 'envira-gallery' ); ?></span>
							<p class="description"><?php esc_html_e( 'You should adjust these dimensions based on the number of columns in your gallery. This does not affect the full size lightbox images.', 'envira-gallery' ); ?></p>
						</td>
					</tr>
						<tr id="envira-config-crop-box">
						<th scope="row">
							<label for="envira-config-crop"><?php esc_html_e( 'Crop Images?', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<input id="envira-config-crop" type="checkbox" name="_envira_gallery[crop]" value="<?php echo esc_html( envira_get_config( 'crop', $gallery_data, $gallery_data, envira_get_config_default( 'crop' ) ) ); ?>" <?php checked( envira_get_config( 'crop', $gallery_data ), 1 ); ?> />
							<span class="description"><?php esc_html_e( 'If enabled, forces images to exactly match the sizes defined above for Image Dimensions and Mobile Dimensions.', 'envira-gallery' ); ?></span>
							<span class="description"><?php esc_html_e( 'If disabled, images will be resized to maintain their aspect ratio.', 'envira-gallery' ); ?></span>

						</td>
					</tr>
						<tr id="envira-config-crop-position-box">
						<th scope="row">
							<label for="envira-config--crop-position-"><?php esc_html_e( 'Crop Position', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<select id="envira-config-crop-position" name="_envira_gallery[crop_position]">
								<?php
								foreach ( (array) envira_crop_position() as $i => $data ) {
									?>
									<option value="<?php echo esc_html( $data['value'] ); ?>"<?php selected( $data['value'], envira_get_config( 'crop_position', $gallery_data, envira_get_config_default( 'crop_position' ) ) ); ?>><?php echo esc_html( $data['name'] ); ?></option>
									<?php
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'Select the position which images will be cropped from.', 'envira-gallery' ); ?></p>
						</td>
					</tr>
						<tr id="envira-config-image-sizes-random-box">
							<th scope="row">
								<label for="envira-config-image-sizes-random"><?php esc_html_e( 'Random Image Sizes', 'envira-gallery' ); ?></label>
							</th>
							<td>
								<?php
								// Get random image sizes that have been selected, if any.
								$image_sizes_random = (array) envira_get_config( 'image_sizes_random', $gallery_data );

								foreach ( (array) envira_get_image_sizes( true ) as $i => $data ) {
									?>
									<label for="envira-config-image-sizes-random-<?php echo esc_html( $data['value'] ); ?>">
										<input id="envira-config-image-sizes-random-<?php echo esc_html( $data['value'] ); ?>" type="checkbox" name="_envira_gallery[image_sizes_random][]" value="<?php echo esc_html( $data['value'] ); ?>"<?php echo ( in_array( $data['value'], $image_sizes_random, true ) ? ' checked' : '' ); ?> />
										<?php echo esc_html( $data['name'] ); ?>
									</label><br />
									<?php
								}
								?>
								<p class="description"><?php esc_html_e( 'Define the WordPress registered image sizes to include when randomly assigning an image size to each image in your Gallery.', 'envira-gallery' ); ?></p>
							</td>
						</tr>
					</tbody>

				</table>
			</div>

			<div id="envira-config-standard-settings-box">

				<table class="form-table">

					<tbody>

						<tr id="envira-config-gallery-theme-box">
							<th scope="row">
								<label for="envira-config-gallery-theme"><?php esc_html_e( 'Gallery Theme', 'envira-gallery' ); ?></label>
							</th>
							<td>
								<select id="envira-config-gallery-theme" name="_envira_gallery[gallery_theme]">
									<?php foreach ( (array) envira_get_gallery_themes() as $i => $data ) : ?>
										<option value="<?php echo esc_html( $data['value'] ); ?>"<?php selected( $data['value'], envira_get_config( 'gallery_theme', $gallery_data, envira_get_config_default( 'gallery_theme' ) ) ); ?>><?php echo esc_html( $data['name'] ); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Sets the theme for the gallery display.', 'envira-gallery' ); ?></p>
							</td>
						</tr>
						<tr id="envira-config-gutter-box">
							<th scope="row">
								<label for="envira-config-gutter"><?php esc_html_e( 'Column Gutter Width', 'envira-gallery' ); ?></label>
							</th>
							<td>
								<input id="envira-config-gutter" type="number" name="_envira_gallery[gutter]" value="<?php echo esc_html( envira_get_config( 'gutter', $gallery_data, envira_get_config_default( 'gutter' ) ) ); ?>" /> <span class="envira-unit"><?php esc_html_e( 'px', 'envira-gallery' ); ?></span>
								<p class="description"><?php esc_html_e( 'Sets the space between the columns (defaults to 10).', 'envira-gallery' ); ?></p>
							</td>
						</tr>
						<tr id="envira-config-margin-box">
							<th scope="row">
								<label for="envira-config-margin"><?php esc_html_e( 'Margin Below Each Image', 'envira-gallery' ); ?></label>
							</th>
							<td>
								<input id="envira-config-margin" type="number" name="_envira_gallery[margin]" value="<?php echo esc_html( envira_get_config( 'margin', $gallery_data, envira_get_config_default( 'margin' ) ) ); ?>" /> <span class="envira-unit"><?php esc_html_e( 'px', 'envira-gallery' ); ?></span>
								<p class="description"><?php esc_html_e( 'Sets the space below each item in the gallery.', 'envira-gallery' ); ?></p>
							</td>
						</tr>

						<!-- Dimensions -->
						<tr id="envira-config-isotope-box">
								<th scope="row">
									<label for="envira-config-isotope"><?php esc_html_e( 'Enable Isotope?', 'envira-gallery' ); ?></label>
								</th>
								<td>
									<input id="envira-config-isotope" type="checkbox" name="_envira_gallery[isotope]" value="<?php echo esc_html( envira_get_config( 'isotope', $gallery_data ) ); ?>" <?php checked( envira_get_config( 'isotope', $gallery_data, envira_get_config_default( 'isotope' ) ), 1 ); ?> />
									<span class="description"><?php esc_html_e( 'Enables or disables isotope/masonry layout support for the main gallery images.', 'envira-gallery' ); ?></span>
								</td>
							</tr>

						<?php do_action( 'envira_gallery_config_box', $post ); ?>

					</tbody>

				</table>

			</div>


			<div id="envira-config-sorting">

				<table class="form-table" style="margin-bottom: 0;">
					<tbody>

				<?php

				if ( class_exists( 'Envira_Defaults' ) && isset( $post->ID ) ) {

					$default_id_gallery = get_option( 'envira_default_gallery' );
					$default_id_album   = get_option( 'envira_default_album' );
					if ( $post->ID === $default_id_gallery ) {

						?>

				<!-- Sorting -->
				<tr id="envira-config-sorting-box">
					<th scope="row">
						<label for="envira-config-sorting"><?php esc_html_e( 'Sorting', 'envira-gallery' ); ?></label>
					</th>
					<td>
						<select id="envira-config-sorting-defaults" name="_envira_gallery[random]">
						<?php
						foreach ( (array) envira_get_sorting_options( false ) as $i => $data ) {
							?>
								<option value="<?php echo esc_html( $data['value'] ); ?>"<?php selected( $data['value'], envira_get_config( 'random', $gallery_data ) ); ?>><?php echo esc_html( $data['name'] ); ?></option>
									<?php
						}
						?>
						</select>
						<p class="description"><?php esc_html_e( 'Choose the sort order for your photos.', 'envira-gallery' ); ?></p>
					</td>
				</tr>
				<tr id="envira-config-sorting-direction-box">
					<th scope="row">
						<label for="envira-config-sorting-direction"><?php esc_html_e( 'Direction', 'envira-gallery' ); ?></label>
					</th>
					<td>
						<select id="envira-config-sorting-direction" name="_envira_gallery[sorting_direction]">
							<?php
							foreach ( (array) envira_get_sorting_directions() as $i => $data ) {
								?>
								<option value="<?php echo esc_html( $data['value'] ); ?>"<?php selected( $data['value'], envira_get_config( 'sorting_direction', $gallery_data ) ); ?>><?php echo esc_html( $data['name'] ); ?></option>
								<?php
							}
							?>
						</select>
					</td>
				</tr>

						<?php

					}
				}

				?>

					</tbody>

				</table>
			</div>

		</div>


		<?php
	}

	/**
	 * Callback for displaying the settings UI for the Lightbox tab.
	 *
	 * @since 1.7.0
	 *
	 * @param object $post The current post object.
	 */
	public function lightbox_tab( $post ) {
		$gallery_data = envira_get_gallery( $post->ID, true ); // flush transient as you grab settings.
		?>
		<div id="envira-lightbox">
			<p class="envira-intro">
				<?php esc_html_e( 'Lightbox Settings', 'envira-gallery' ); ?>

				<small>

					<?php esc_html_e( 'The settings below adjust the lightbox output.', 'envira-gallery' ); ?>
					<br />

					<?php if ( $this->whitelabel ) : ?>

						<?php do_action( 'envira_whitelabel_tab_text_lightbox' ); ?>

					<?php else : ?>

						<?php esc_html_e( 'Need some help?', 'envira-gallery' ); ?>
						<a href="http://enviragallery.com/docs/creating-first-envira-gallery/" class="envira-doc" target="_blank">
							<?php esc_html_e( 'Read the Documentation', 'envira-gallery' ); ?>
						</a>

							<?php esc_html_e( 'or', 'envira-gallery' ); ?>

					<a href="https://www.youtube.com/embed/4jHG3LOmV-c?autoplay=1&amp;rel=0" class="envira-video" target="_blank">
						<?php esc_html_e( 'Watch a Video', 'envira-gallery' ); ?>
					</a>
					<?php endif; ?>

				</small>


			</p>

			<table class="form-table no-margin">
				<tbody>
					<tr id="envira-config-lightbox-enabled-box">
						<th scope="row">
							<label for="envira-config-lightbox-enabled"><?php esc_html_e( 'Enable Lightbox?', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<input id="envira-config-lightbox-enabled" type="checkbox" name="_envira_gallery[lightbox_enabled]" value="<?php echo esc_html( envira_get_config( 'lightbox_enabled', $gallery_data ) ); ?>" <?php checked( envira_get_config( 'lightbox_enabled', $gallery_data ), 1 ); ?> />
							<span class="description"><?php esc_html_e( 'Enables or disables the gallery lightbox.', 'envira-gallery' ); ?></span>
						</td>
					</tr>
					<tr id="envira-config-lightbox-enabled-link">
						<th scope="row">
							<label for="envira-config-lightbox-enable-links"><?php esc_html_e( 'Enable Links?', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<input id="envira-config-lightbox-enable-links" type="checkbox" name="_envira_gallery[gallery_link_enabled]" value="<?php echo esc_html( envira_get_config( 'gallery_link_enabled', $gallery_data ) ); ?>" <?php checked( envira_get_config( 'gallery_link_enabled', $gallery_data ), 1 ); ?> />
							<span class="description"><?php esc_html_e( 'Enables or disables links only when the gallery lightbox is disabled.', 'envira-gallery' ); ?></span>
						</td>
					</tr>
				</tbody>
			</table>

			<div id="envira-lightbox-settings">
				<table class="form-table">
					<tbody>

						<tr id="envira-config-lightbox-theme-box">
							<th scope="row">
								<label for="envira-config-lightbox-theme"><?php esc_html_e( 'Gallery Lightbox Theme', 'envira-gallery' ); ?></label>
							</th>
							<td>
								<select id="envira-config-lightbox-theme" name="_envira_gallery[lightbox_theme]">
									<?php foreach ( (array) envira_get_lightbox_themes() as $i => $data ) : ?>
										<option value="<?php echo esc_html( $data['value'] ); ?>"<?php selected( $data['value'], envira_get_config( 'lightbox_theme', $gallery_data ) ); ?>><?php echo esc_html( $data['name'] ); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Sets the theme for the gallery lightbox display.', 'envira-gallery' ); ?></p>
							</td>
						</tr>
						<tr id="envira-config-lightbox-additional-title-caption">
							<th scope="row">
								<label for="envira-config-title-caption"><?php esc_html_e( 'Show Title Or Caption?', 'envira-gallery' ); ?></label>
							</th>
							<td>
								<select id="envira-config-lightbox-title-caption" name="_envira_gallery[lightbox_title_caption]">
									<?php foreach ( (array) envira_get_additional_copy_options() as $option_value => $option_name ) : ?>
										<option value="<?php echo esc_html( $option_value ); ?>" <?php selected( $option_value, envira_get_config( 'lightbox_title_caption', $gallery_data ) ); ?>><?php echo esc_html( $option_name ); ?></option>
									<?php endforeach; ?>
								</select><br>
								<p class="description"><?php esc_html_e( 'Caption is the default text, but you can choose title instead or nothing at all (for maximum size images with supersize).', 'envira-gallery' ); ?></p>
							</td>
						</tr>
						<tr id="envira-config-lightbox-image-size-box">
							<th scope="row">
								<label for="envira-config-lightbox-image-size"><?php esc_html_e( 'Image Size', 'envira-gallery' ); ?></label>
							</th>
							<td>
								<select id="envira-config-lightbox-image-size" name="_envira_gallery[lightbox_image_size]">
									<?php foreach ( (array) envira_get_image_sizes() as $i => $data ) : ?>
										<option value="<?php echo esc_html( $data['value'] ); ?>" <?php selected( $data['value'], envira_get_config( 'lightbox_image_size', $gallery_data ) ); ?>><?php echo esc_html( $data['name'] ); ?></option>
									<?php endforeach; ?>
								</select><br>
								<p class="description"><?php esc_html_e( 'Define the maximum image size for the Lightbox view. Default will display the original, full size image.', 'envira-gallery' ); ?></p>
							</td>
						</tr>
						<tr id="envira-config-lightbox-title-display-box">
							<th scope="row">
								<label for="envira-config-lightbox-title-display"><?php esc_html_e( 'Caption Position', 'envira-gallery' ); ?></label>
							</th>
							<td>
								<select id="envira-config-lightbox-title-display" name="_envira_gallery[title_display]">
									<?php foreach ( (array) envira_get_title_displays() as $i => $data ) : ?>
										<option value="<?php echo esc_html( $data['value'] ); ?>"<?php selected( $data['value'], envira_get_config( 'title_display', $gallery_data ) ); ?>><?php echo esc_html( $data['name'] ); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Sets the display of the lightbox image\'s caption.', 'envira-gallery' ); ?></p>
							</td>
						</tr>
						<tr id="envira-config-lightbox-arrows-box">
								<th scope="row">
									<label for="envira-config-lightbox-arrows"><?php esc_html_e( 'Enable Gallery Arrows?', 'envira-gallery' ); ?></label>
								</th>
								<td>
									<input id="envira-config-lightbox-arrows" type="checkbox" name="_envira_gallery[arrows]" value="<?php echo esc_html( envira_get_config( 'arrows', $gallery_data ) ); ?>" <?php checked( envira_get_config( 'arrows', $gallery_data ), 1 ); ?> />
									<span class="description"><?php esc_html_e( 'Enables or disables the gallery lightbox navigation arrows.', 'envira-gallery' ); ?></span>
								</td>
							</tr>
						<tr id="envira-config-lightbox-arrows-position-box">
								<th scope="row">
									<label for="envira-config-lightbox-arrows-position"><?php esc_html_e( 'Gallery Arrow Position', 'envira-gallery' ); ?></label>
								</th>
								<td>
									<select id="envira-config-lightbox-arrows-position" name="_envira_gallery[arrows_position]">
										<?php foreach ( (array) envira_get_arrows_positions() as $i => $data ) : ?>
											<option value="<?php echo esc_html( $data['value'] ); ?>"<?php selected( $data['value'], envira_get_config( 'arrows_position', $gallery_data ) ); ?>><?php echo esc_html( $data['name'] ); ?></option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php esc_html_e( 'Sets the position of the gallery lightbox navigation arrows.', 'envira-gallery' ); ?></p>
								</td>
							</tr>
						<tr id="envira-config-lightbox-toolbar-box">
								<th scope="row">
									<label for="envira-config-lightbox-toolbar"><?php esc_html_e( 'Enable Gallery Toolbar?', 'envira-gallery' ); ?></label>
								</th>
								<td>
									<input id="envira-config-lightbox-toolbar" type="checkbox" name="_envira_gallery[toolbar]" value="<?php echo esc_html( envira_get_config( 'toolbar', $gallery_data ) ); ?>" <?php checked( envira_get_config( 'toolbar', $gallery_data ), 1 ); ?> />
									<span class="description"><?php esc_html_e( 'Enables or disables the gallery lightbox toolbar. For legacy lightbox themes, the toolbar is required for some addon features like', 'envira-gallery' ); ?> <a target="_new" href="https://enviragallery.com/addons/fullscreen-addon/"><?php esc_html_e( 'fullscreen view', 'envira-gallery' ); ?></a>.</span>
								</td>
							</tr>
						<tr id="envira-config-lightbox-toolbar-title-box">
								<th scope="row">
									<label for="envira-config-lightbox-toolbar-title"><?php esc_html_e( 'Display Gallery Title in Toolbar?', 'envira-gallery' ); ?></label>
								</th>
								<td>
									<input id="envira-config-lightbox-toolbar-title" type="checkbox" name="_envira_gallery[toolbar_title]" value="<?php echo esc_html( envira_get_config( 'toolbar_title', $gallery_data ) ); ?>" <?php checked( envira_get_config( 'toolbar_title', $gallery_data ), 1 ); ?> />
									<span class="description"><?php esc_html_e( 'Display the gallery title in the lightbox toolbar.', 'envira-gallery' ); ?></span>
								</td>
							</tr>
						<tr id="envira-config-lightbox-toolbar-position-box">
								<th scope="row">
									<label for="envira-config-lightbox-toolbar-position"><?php esc_html_e( 'Gallery Toolbar Position', 'envira-gallery' ); ?></label>
								</th>
								<td>
									<select id="envira-config-lightbox-toolbar-position" name="_envira_gallery[toolbar_position]">
										<?php foreach ( (array) envira_get_toolbar_positions() as $i => $data ) : ?>
											<option value="<?php echo esc_html( $data['value'] ); ?>"<?php selected( $data['value'], envira_get_config( 'toolbar_position', $gallery_data ) ); ?>><?php echo esc_html( $data['name'] ); ?></option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php esc_html_e( 'Sets the position of the lightbox toolbar.', 'envira-gallery' ); ?></p>
								</td>
							</tr>
						<tr id="envira-config-lightbox-loop-box">
								<th scope="row">
									<label for="envira-config-lightbox-loop"><?php esc_html_e( 'Loop Gallery Navigation?', 'envira-gallery' ); ?></label>
								</th>
								<td>
									<input id="envira-config-lightbox-loop" type="checkbox" name="_envira_gallery[loop]" value="<?php echo esc_html( envira_get_config( 'loop', $gallery_data ) ); ?>" <?php checked( envira_get_config( 'loop', $gallery_data ), 1 ); ?> />
									<span class="description"><?php esc_html_e( 'Enables or disables infinite navigation cycling of the lightbox gallery.', 'envira-gallery' ); ?></span>
								</td>
							</tr>
						<tr id="envira-config-lightbox-open-close-effect-box">
								<th scope="row">
									<label for="envira-config-lightbox-open-close-effect"><?php esc_html_e( 'Lightbox Open/Close Effect', 'envira-gallery' ); ?></label>
								</th>
								<td>
									<select id="envira-config-lightbox-open-close-effect" name="_envira_gallery[lightbox_open_close_effect]">
										<?php
										// Account for FB3 Update.
										$effect = envira_get_config( 'lightbox_open_close_effect', $gallery_data ) === 'zomm-in-out' ? 'zoom-in-out' : envira_get_config( 'lightbox_open_close_effect', $gallery_data );
										// Standard Effects.
										foreach ( (array) envira_get_envirabox_open_effects() as $i => $data ) {
											?>
											<option value="<?php echo esc_html( $data['value'] ); ?>"<?php selected( $data['value'], $effect ); ?>><?php echo esc_html( $data['name'] ); ?></option>
											<?php
										}

										?>
									</select>
									<p class="description"><?php esc_html_e( 'Type of transition when opening and closing the lightbox.', 'envira-gallery' ); ?></p>
								</td>
							</tr>
						<tr id="envira-config-lightbox-effect-box">
								<th scope="row">
									<label for="envira-config-lightbox-effect"><?php esc_html_e( 'Lightbox Transition Effect', 'envira-gallery' ); ?></label>
								</th>
								<td>
									<select id="envira-config-lightbox-effect" name="_envira_gallery[effect]">
										<?php
										// Account for FB3 Update.
										$effect = envira_get_config( 'effect', $gallery_data ) === 'zomm-in-out' ? 'zoom-in-out' : envira_get_config( 'effect', $gallery_data );
										// Standard Effects.
										foreach ( (array) envira_get_transition_effects() as $i => $data ) {
											?>
											<option value="<?php echo esc_html( $data['value'] ); ?>"<?php selected( $data['value'], $effect ); ?>><?php echo esc_html( $data['name'] ); ?></option>
											<?php
										}

										?>
									</select>
									<p class="description"><?php esc_html_e( 'Type of transition between images in the lightbox view.', 'envira-gallery' ); ?></p>
								</td>
							</tr>
						<tr id="envira-config-supersize-box">
								<th scope="row">
									<label for="envira-config-supersize"><?php esc_html_e( 'Enable Lightbox Supersize?', 'envira-gallery' ); ?></label>
								</th>
								<td>
									<input id="envira-config-supersize" type="checkbox" name="_envira_gallery[supersize]" value="<?php echo esc_html( envira_get_config( 'supersize', $gallery_data ) ); ?>" <?php checked( envira_get_config( 'supersize', $gallery_data ), 1 ); ?> />
									<span class="description"><?php esc_html_e( 'Enables or disables supersize mode for gallery lightbox images.', 'envira-gallery' ); ?></span>
								</td>
							</tr>

						<?php do_action( 'envira_gallery_lightbox_box', $post ); ?>
						<tr id="envira-config-image-counter">
							<th scope="row">
								<label for="envira-config-margin"><?php esc_html_e( 'Enable Image Counter?', 'envira-gallery' ); ?></label>
							</th>
								<td>
									<input id="envira-config-lightbox-image-counter" type="checkbox" name="_envira_gallery[image_counter]" value="<?php echo esc_html( envira_get_config( 'image_counter', $gallery_data ) ); ?>" <?php checked( envira_get_config( 'image_counter', $gallery_data ), 1 ); ?> />
									<span class="description"><?php esc_html_e( 'Adds \'Image X of X\' after your caption.', 'envira-gallery' ); ?></span>
								</td>

						</tr>
					</tbody>
				</table>

					<p class="envira-intro"><?php esc_html_e( 'The settings below adjust the thumbnail views for the gallery lightbox display.', 'envira-gallery' ); ?></p>
					<table class="form-table">
						<tbody>

							<tr id="envira-config-thumbnails-box">
								<th scope="row">
									<label for="envira-config-thumbnails"><?php esc_html_e( 'Enable Gallery Thumbnails?', 'envira-gallery' ); ?></label>
								</th>
								<td>
									<input id="envira-config-thumbnails" type="checkbox" name="_envira_gallery[thumbnails]" value="<?php echo esc_html( envira_get_config( 'thumbnails', $gallery_data ) ); ?>" <?php checked( envira_get_config( 'thumbnails', $gallery_data ), 1 ); ?> />
									<span class="description"><?php esc_html_e( 'Enables or disables the gallery lightbox thumbnails.', 'envira-gallery' ); ?></span>
								</td>
							</tr>
							<?php
								$custom = envira_get_config( 'thumbnails_custom_size', $gallery_data );
								$width  = envira_get_config( 'thumbnails_width', $gallery_data );
								$height = envira_get_config( 'thumbnails_height', $gallery_data );

								/*
								If the user has a pre-existing gallery with width/height that were NOT once before the defaults
								- which are likely 75px width and 50px height - then auto check the custom width/height box
								*/
							if ( empty( $custom ) &&
									! empty( $width ) &&
									( envira_get_config( 'thumbnails_width', $gallery_data ) !== envira_get_config_default( 'thumbnails_width' ) ) &&
									! empty( $height ) &&
									( envira_get_config( 'thumbnails_height', $gallery_data ) !== envira_get_config_default( 'thumbnails_height' ) ) ) {
								$checked = 'checked="true"';
							} else {
								$checked = checked( envira_get_config( 'thumbnails_custom_size', $gallery_data ), 1, false );
							}

							?>
							<tr id="envira-config-thumbnails-custom-size">
								<th scope="row">
									<label for="envira-config-thumbnails-custom-size"><?php esc_html_e( 'Use Custom Width/Height?', 'envira-gallery' ); ?></label>
								</th>
								<td>
									<input id="envira-config-thumbnails-custom-size" type="checkbox" name="_envira_gallery[thumbnails_custom_size]" value="<?php echo esc_html( envira_get_config( 'thumbnails_custom_size', $gallery_data ) ); ?>" <?php echo esc_html( $checked ); ?> />
									<span class="description"><?php esc_html_e( 'This enables you to enter a custom width and height, overriding Envira\'s automatic settings.', 'envira-gallery' ); ?></span>
								</td>
							</tr>
							<tr id="envira-config-thumbnails-width-box">
								<th scope="row">
									<label for="envira-config-thumbnails-width"><?php esc_html_e( 'Gallery Thumbnails Width', 'envira-gallery' ); ?></label>
								</th>
								<td>
									<input id="envira-config-thumbnails-width" type="number" name="_envira_gallery[thumbnails_width]" value="<?php echo esc_html( envira_get_config( 'thumbnails_width', $gallery_data ) ); ?>" /> <span class="envira-unit"><?php esc_html_e( 'px', 'envira-gallery' ); ?></span>
									<p class="description"><?php esc_html_e( 'Sets the width of each lightbox thumbnail.', 'envira-gallery' ); ?></p>
								</td>
							</tr>
							<tr id="envira-config-thumbnails-height-box">
								<th scope="row">
									<label for="envira-config-thumbnails-height"><?php esc_html_e( 'Gallery Thumbnails Height', 'envira-gallery' ); ?></label>
								</th>
								<td>
									<input id="envira-config-thumbnails-height" type="number" name="_envira_gallery[thumbnails_height]" value="<?php echo esc_html( envira_get_config( 'thumbnails_height', $gallery_data ) ); ?>" /> <span class="envira-unit"><?php esc_html_e( 'px', 'envira-gallery' ); ?></span>
									<p class="description"><?php esc_html_e( 'Sets the height of each lightbox thumbnail.', 'envira-gallery' ); ?></p>
								</td>
							</tr>
							<tr id="envira-config-thumbnails-position-box">
								<th scope="row">
									<label for="envira-config-thumbnails-position"><?php esc_html_e( 'Gallery Thumbnails Position', 'envira-gallery' ); ?></label>
								</th>
								<td>
									<select id="envira-config-thumbnails-position" name="_envira_gallery[thumbnails_position]">
										<?php foreach ( (array) envira_get_thumbnail_positions() as $i => $data ) : ?>
											<option value="<?php echo esc_html( $data['value'] ); ?>"<?php selected( $data['value'], envira_get_config( 'thumbnails_position', $gallery_data ) ); ?>><?php echo esc_html( $data['name'] ); ?></option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php esc_html_e( 'Sets the position of the lightbox thumbnails.', 'envira-gallery' ); ?></p>
								</td>
							</tr>

							<tr id="envira-config-thumbnail-button">
								<th scope="row">
									<label for="envira-config-thumbnail-button"><?php esc_html_e( 'Enable Thumbnail Button?', 'envira-gallery' ); ?></label>
								</th>
								<td>
									<input id="envira-config-thumbnail-button" type="checkbox" name="_envira_gallery[thumbnails_toggle]" value="<?php echo esc_html( envira_get_config( 'thumbnails_toggle', $gallery_data ) ); ?>" <?php checked( envira_get_config( 'thumbnails_toggle', $gallery_data ), 1 ); ?> />
									<span class="description"><?php esc_html_e( 'Enables display of thumbnail toggle button in lightbox.', 'envira-gallery' ); ?></span>
								</td>
							</tr>

							<tr id="envira-config-thumbnail-hide">
								<th scope="row">
									<label for="envira-config-thumbnail-hide"><?php esc_html_e( 'Hide Thumbnails On Opening Lightbox?', 'envira-gallery' ); ?></label>
								</th>
								<td>
									<input id="envira-config-thumbnail-hide" type="checkbox" name="_envira_gallery[thumbnails_hide]" value="<?php echo esc_html( envira_get_config( 'thumbnails_hide', $gallery_data ) ); ?>" <?php checked( envira_get_config( 'thumbnails_hide', $gallery_data ), 1 ); ?> />
									<span class="description"><?php esc_html_e( 'Enables thumbnails to stay initially hidden when the lightbox opens. User must click the thumbnail button to make them visible.', 'envira-gallery' ); ?></span>
								</td>
							</tr>

							<?php do_action( 'envira_gallery_thumbnails_box', $post ); ?>

						</tbody>
					</table>

			</div>
		</div>
		<?php

	}

	/**
	 * Callback for displaying the settings UI for the Mobile tab.
	 *
	 * @since 1.3.2
	 *
	 * @param object $post The current post object.
	 */
	public function mobile_tab( $post ) {

		$gallery_data = envira_get_gallery( $post->ID, true ); // flush transient as you grab settings.

		?>

		<div id="envira-mobile">
			<p class="envira-intro">
				<?php esc_html_e( 'Mobile Gallery Settings', 'envira-gallery' ); ?>
				<small>

					<?php esc_html_e( 'The settings below adjust configuration options for the Gallery when viewed on a mobile device.', 'envira-gallery' ); ?><br />

					<?php if ( $this->whitelabel ) : ?>
						<?php do_action( 'envira_whitelabel_tab_text_mobile' ); ?>
					<?php else : ?>
						<?php esc_html_e( 'Need some help?', 'envira-gallery' ); ?>
						<a href="http://enviragallery.com/docs/creating-first-envira-gallery/" class="envira-doc" target="_blank">
							<?php esc_html_e( 'Read the Documentation', 'envira-gallery' ); ?>
						</a>
						or
						<a href="https://www.youtube.com/embed/4jHG3LOmV-c?autoplay=1&amp;rel=0" class="envira-video" target="_blank">
							<?php esc_html_e( 'Watch a Video', 'envira-gallery' ); ?>
						</a>
					<?php endif; ?>

				</small>
			</p>
			<table class="form-table">
				<tbody>

					<tr id="envira-config-mobile-box">
						<th scope="row">
							<label for="envira-config-mobile"><?php esc_html_e( 'Create Mobile Gallery Images?', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<input id="envira-config-mobile" type="checkbox" name="_envira_gallery[mobile]" value="<?php echo esc_html( envira_get_config( 'mobile', $gallery_data, envira_get_config_default( 'mobile' ) ) ); ?>" <?php checked( envira_get_config( 'mobile', $gallery_data ), 1 ); ?> />
							<span class="description"><?php esc_html_e( 'Enables or disables creating specific images for mobile devices.', 'envira-gallery' ); ?></span>
						</td>
					</tr>

					<tr id="envira-config-mobile-size-box">
						<th scope="row">
							<label for="envira-config-mobile-width"><?php esc_html_e( 'Mobile Dimensions', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<input id="envira-config-mobile-width" type="number" name="_envira_gallery[mobile_width]" value="<?php echo esc_html( envira_get_config( 'mobile_width', $gallery_data, envira_get_config_default( 'mobile_width' ) ) ); ?>" /> &#215; <input id="envira-config-mobile-height" type="number" name="_envira_gallery[mobile_height]" value="<?php echo esc_html( envira_get_config( 'mobile_height', $gallery_data, envira_get_config_default( 'mobile_height' ) ) ); ?>" /> <span class="envira-unit"><?php esc_html_e( 'px', 'envira-gallery' ); ?></span>
							<p class="description"><?php esc_html_e( 'These will be the sizes used for images displayed on mobile devices.', 'envira-gallery' ); ?></p>
						</td>
					</tr>

					<tr id="envira-config-mobile-justified-row-height">
							<th scope="row">
								<label for="envira-config-justified-row-height-mobile"><?php esc_html_e( 'Automatic Layout: Row Height', 'envira-gallery' ); ?></label>
							</th>
							<td>
								<input id="envira-config-justified-row-height-mobile" type="number" name="_envira_gallery[mobile_justified_row_height]" value="<?php echo esc_html( envira_get_config( 'mobile_justified_row_height', $gallery_data, envira_get_config_default( 'mobile_justified_row_height' ) ) ); ?>" /> <span class="envira-unit"><?php esc_html_e( 'px', 'envira-gallery' ); ?></span>
								<p class="description"><?php esc_html_e( 'Determines how high (in pixels) each row will be. 80px is default. ', 'envira-gallery' ); ?></p>
							</td>
					</tr>
					<tr id="envira-config-title-caption-automatic-mobile">
							<th scope="row">
								<label for="envira-config-additional-copy-box"><?php esc_html_e( 'Enable Title/Caption?', 'envira-gallery' ); ?></label>
							</th>
							<td>
								<?php
								foreach ( envira_get_additional_copy_options() as $option_value => $option_name ) {

									if ( empty( $option_value ) || 'None' === $option_name ) { // no need to show 'None' for checkboxes.
										continue;
									}

									?>
									<label for="envira-config-<?php echo esc_html( $option_value ); ?>-automatic-mobile" class="label-for-checkbox">
										<input id="envira-config-<?php echo esc_html( $option_value ); ?>-automatic-mobile" type="checkbox" name="_envira_gallery[additional_copy_automatic_<?php echo esc_html( $option_value ); ?>_mobile]" value="1" <?php checked( envira_get_config( 'additional_copy_automatic_' . $option_value . '_mobile', $gallery_data, envira_get_config_default( 'additional_copy_automatic_' . $option_value . '_mobile' ) ), 1 ); ?> />
										<?php echo esc_html( $option_name ); ?>
									</label>
									<?php
								}
								?>
								<!--<p class="description">
									<?php esc_html_e( 'Select the information that should be shared with each image.', 'envira-social' ); ?>
								</p>-->
							</td>
						</tr>

						<tr id="envira-config-title-caption-column-mobile">
							<th scope="row">
								<label for="envira-config-additional-copy-box"><?php esc_html_e( 'Enable Title/Caption Below Image?', 'envira-gallery' ); ?></label>
							</th>
							<td>
								<?php
								foreach ( envira_get_additional_copy_options() as $option_value => $option_name ) {

									if ( empty( $option_value ) || 'None' === $option_name ) { // no need to show 'None' for checkboxes.
										continue;
									}

									?>
									<label for="envira-config-<?php echo esc_html( $option_value ); ?>-mobile" class="label-for-checkbox">
										<input id="envira-config-<?php echo esc_html( $option_value ); ?>-mobile" type="checkbox" name="_envira_gallery[additional_copy_<?php echo esc_html( $option_value ); ?>_mobile]" value="1" <?php checked( envira_get_config( 'additional_copy_' . $option_value . '_mobile', $gallery_data, envira_get_config_default( 'additional_copy_' . $option_value . '_mobile' ) ), 1 ); ?> />
										<?php echo esc_html( $option_name ); ?>
									</label>

								<?php } ?>

							</td>
						</tr>

					<?php do_action( 'envira_gallery_mobile_box', $post ); ?>
				</tbody>
			</table>

			<!-- Lightbox -->
			<p class="envira-intro">
				<?php esc_html_e( 'Mobile Lightbox Settings', 'envira-gallery' ); ?>
				<small>
					<?php esc_html_e( 'The settings below adjust configuration options for the Lightbox when viewed on a mobile device.', 'envira-gallery' ); ?><br />
				</small>
			</p>
			<table class="form-table">
				<tbody>
					<tr id="envira-config-mobile-lightbox-box">
						<th scope="row">
							<label for="envira-config-mobile-lightbox"><?php esc_html_e( 'Enable Lightbox?', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<input id="envira-config-mobile-lightbox" type="checkbox" name="_envira_gallery[mobile_lightbox]" value="<?php echo esc_html( envira_get_config( 'mobile_lightbox', $gallery_data ) ); ?>" <?php checked( envira_get_config( 'mobile_lightbox', $gallery_data, envira_get_config_default( 'mobile_lightbox' ) ), 1 ); ?> />
							<span class="description"><?php esc_html_e( 'Enables or disables the gallery lightbox on mobile devices. Disabling also removes ANY links from gallery images.', 'envira-gallery' ); ?></span>
						</td>
					</tr>
					<tr id="envira-config-lightbox-mobile-enable-links">
						<th scope="row">
							<label for="envira-config-lightbox-enable-links"><?php esc_html_e( 'Enable Links?', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<input id="envira-config-lightbox-mobile-enable-links" type="checkbox" name="_envira_gallery[mobile_gallery_link_enabled]" value="<?php echo esc_html( envira_get_config( 'mobile_gallery_link_enabled', $gallery_data ) ); ?>" <?php checked( envira_get_config( 'mobile_gallery_link_enabled', $gallery_data ), 1 ); ?> />
							<span class="description"><?php esc_html_e( 'Enables or disables links only when the gallery lightbox on mobile is disabled.', 'envira-gallery' ); ?></span>
						</td>
					</tr>
					<tr id="envira-config-mobile-touchwipe-close-box">
						<th scope="row">
							<label for="envira-config-mobile-touchwipe-close"><?php esc_html_e( 'Close Lightbox on Swipe Up?', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<input id="envira-config-mobile-touchwipe-close" type="checkbox" name="_envira_gallery[mobile_touchwipe_close]" value="<?php echo esc_html( envira_get_config( 'mobile_touchwipe_close', $gallery_data ) ); ?>" <?php checked( envira_get_config( 'mobile_touchwipe_close', $gallery_data, envira_get_config_default( 'mobile_touchwipe_close' ) ), 1 ); ?> />
							<span class="description"><?php esc_html_e( 'Enables or disables closing the Lightbox when the user swipes up on mobile devices.', 'envira-gallery' ); ?></span>
						</td>
					</tr>
					<tr id="envira-config-mobile-arrows-box">
						<th scope="row">
							<label for="envira-config-mobile-arrows"><?php esc_html_e( 'Enable Gallery Arrows?', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<input id="envira-config-mobile-arrows" type="checkbox" name="_envira_gallery[mobile_arrows]" value="<?php echo esc_html( envira_get_config( 'mobile_arrows', $gallery_data ) ); ?>" <?php checked( envira_get_config( 'mobile_arrows', $gallery_data ), 1 ); ?> />
							<span class="description"><?php esc_html_e( 'Enables or disables the gallery lightbox navigation arrows on mobile devices.', 'envira-gallery' ); ?></span>
						</td>
					</tr>
					<tr id="envira-config-mobile-toolbar-box">
						<th scope="row">
							<label for="envira-config-mobile-toolbar"><?php esc_html_e( 'Enable Gallery Toolbar?', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<input id="envira-config-mobile-toolbar" type="checkbox" name="_envira_gallery[mobile_toolbar]" value="<?php echo esc_html( envira_get_config( 'mobile_toolbar', $gallery_data, envira_get_config_default( 'mobile_toolbar' ) ) ); ?>" <?php checked( envira_get_config( 'mobile_toolbar', $gallery_data ), 1 ); ?> />
							<span class="description"><?php esc_html_e( 'Enables or disables the gallery lightbox toolbar on mobile devices.', 'envira-gallery' ); ?></span>
						</td>
					</tr>
					<tr id="envira-config-mobile-thumbnails-box">
						<th scope="row">
							<label for="envira-config-mobile-thumbnails"><?php esc_html_e( 'Enable Gallery Thumbnails?', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<input id="envira-config-mobile-thumbnails" type="checkbox" name="_envira_gallery[mobile_thumbnails]" value="<?php echo esc_html( envira_get_config( 'mobile_thumbnails', $gallery_data ) ); ?>" <?php checked( envira_get_config( 'mobile_thumbnails', $gallery_data, envira_get_config_default( 'mobile_thumbnails' ) ), 1 ); ?> />
							<span class="description"><?php esc_html_e( 'Enables or disables the gallery lightbox thumbnails on mobile devices.', 'envira-gallery' ); ?></span>
						</td>
					</tr>
					<tr id="envira-config-mobile-thumbnails-width-box">
						<th scope="row">
							<label for="envira-config-mobile-thumbnails-width"><?php esc_html_e( 'Gallery Thumbnails Width', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<input id="envira-config-mobile-thumbnails-width" type="number" name="_envira_gallery[mobile_thumbnails_width]" value="<?php echo esc_html( envira_get_config( 'mobile_thumbnails_width', $gallery_data, envira_get_config_default( 'mobile_thumbnails_width' ) ) ); ?>" /> <span class="envira-unit"><?php esc_html_e( 'px', 'envira-gallery' ); ?></span>
							<p class="description"><?php esc_html_e( 'Sets the width of each lightbox thumbnail when on mobile devices.', 'envira-gallery' ); ?></p>
						</td>
					</tr>
					<tr id="envira-config-mobile-thumbnails-height-box">
						<th scope="row">
							<label for="envira-config-mobile-thumbnails-height"><?php esc_html_e( 'Gallery Thumbnails Height', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<input id="envira-config-mobile-thumbnails-height" type="number" name="_envira_gallery[mobile_thumbnails_height]" value="<?php echo esc_html( envira_get_config( 'mobile_thumbnails_height', $gallery_data, envira_get_config_default( 'mobile_height' ) ) ); ?>" /> <span class="envira-unit"><?php esc_html_e( 'px', 'envira-gallery' ); ?></span>
							<p class="description"><?php esc_html_e( 'Sets the height of each lightbox thumbnail when on mobile devices.', 'envira-gallery' ); ?></p>
						</td>
					</tr>
					<?php do_action( 'envira_gallery_mobile_lightbox_box', $post ); ?>
				</tbody>
			</table>
		</div>
		<?php

	}

	/**
	 * Callback for displaying the settings UI for the Standalone tab.
	 *
	 * @since 1.3.2
	 *
	 * @param object $post The current post object.
	 */
	public function standalone_tab( $post ) {
		$gallery_data = envira_get_gallery( $post->ID );
		// Get post type so we load the correct metabox instance and define the input field names
		// Input field names vary depending on whether we are editing a Gallery or Album.
		$post_type = get_post_type( $post );
		switch ( $post_type ) {
			/**
			* Gallery
			*/
			case 'envira':
				$key = '_envira_gallery';
				break;

		}

		// Gallery options only apply to Galleries, not Albums.
		if ( 'envira' === $post_type ) {

			/* Get list of templates */

			$templates = get_page_templates();

			?>
			<?php if ( $this->whitelabel ) : ?>
				<p class="envira-intro">

					<?php esc_html_e( 'Standalone Settings.', 'envira-standalone' ); ?>

					<small>

						<?php esc_html_e( 'The settings below adjust the Standalone settings.', 'envira-standalone' ); ?>

						<?php if ( $this->whitelabel ) : ?>

							<?php do_action( 'envira_standalone_whitelabel_tab_helptext' ); ?>

						<?php else : ?>

							<?php esc_html_e( 'Need some help?', 'envira-standalone' ); ?>
							<a href="http://enviragallery.com/docs/standalone/" class="envira-doc" target="_blank">
								<?php esc_html_e( 'Read the Documentation', 'envira-standalone' ); ?>
							</a>
							or
							<a href="https://www.youtube.com/embed/dJ2t7uplFkw?autoplay=1&rel=0" class="envira-video" target="_blank">
								<?php esc_html_e( 'Watch a Video', 'envira-standalone' ); ?>
							</a>

						<?php endif; ?>
					</small>
				</p>
			<?php endif; ?>

			<p class="envira-intro">
				<?php esc_html_e( 'Standalone Options', 'envira-standalone' ); ?>

				<small>
					<?php esc_html_e( 'The settings below adjust the Standalone settings.', 'envira-standalone' ); ?>
					<br/>
					<?php esc_html_e( 'Need some help?', 'envira-standalone' ); ?>
					<a href="http://enviragallery.com/docs/standalone/" class="envira-doc" target="_blank">
						<?php esc_html_e( 'Read the Documentation', 'envira-standalone' ); ?>
					</a>
					or
					<a href="https://www.youtube.com/embed/dJ2t7uplFkw?autoplay=1&rel=0" class="envira-video" target="_blank">
						<?php esc_html_e( 'Watch a Video', 'envira-standalone' ); ?>
					</a>
				</small>
			</p>
			<table class="form-table">
				<tbody>
					<tr id="envira-config-standalone-box">
							<th scope="row">
								<label for="envira-config-standalone-template"><?php esc_html_e( 'Template', 'envira-standalone' ); ?></label>
							</th>
							<td>
								<?php if ( ! empty( $templates ) ) : ?>
								<select id="envira-config-standalone-template" name="<?php echo esc_html( $key ); ?>[standalone_template]">
									<option value="">(Default)</option>
									<?php foreach ( (array) $templates as $name => $filename ) : ?>

									<option value="<?php echo esc_html( $filename ); ?>"<?php selected( $filename, envira_get_config( 'standalone_template', $gallery_data ) ); ?>><?php echo esc_html( $name ); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'By default we use single.php, which is the default template of the single blog post in your theme.', 'envira-zoom' ); ?></p>

								<?php else : ?>

								<p class="description"><?php esc_html_e( 'Your current theme does not have any custom templates. If you want to use a template besides the default, you need to add a custom template to your theme.', 'envira-gallery' ); ?></p>

								<?php endif; ?>

							</td>
					</tr>
				</tbody>
			</table>
			<?php
		}
	}

	/**
	 * Callback for displaying the settings UI for the Misc tab.
	 *
	 * @since 1.7.0
	 *
	 * @param object $post The current post object.
	 */
	public function misc_tab( $post ) {
		$gallery_data = envira_get_gallery( $post->ID );

		?>
		<div id="envira-misc">
			<p class="envira-intro">
				<?php esc_html_e( 'Miscellaneous Settings', 'envira-gallery' ); ?>

					<small>

						<?php esc_html_e( 'The settings below adjust miscellaneous options for the Gallery.', 'envira-gallery' ); ?>

						<?php if ( $this->whitelabel ) : ?>
							<?php do_action( 'envira_whitelabel_tab_text_misc' ); ?>
						<?php else : ?>

							<br />
							<?php esc_html_e( 'Need some help?', 'envira-gallery' ); ?>
							<a href="http://enviragallery.com/docs/creating-first-envira-gallery/" class="envira-doc" target="_blank">
								<?php esc_html_e( 'Read the Documentation', 'envira-gallery' ); ?>
							</a>
							or
							<a href="https://www.youtube.com/embed/4jHG3LOmV-c?autoplay=1&amp;rel=0" class="envira-video" target="_blank">
								<?php esc_html_e( 'Watch a Video', 'envira-gallery' ); ?>
							</a>

						<?php endif; ?>

					</small>


			</p>
			<table class="form-table">
				<tbody>
					<tr id="envira-config-slug-box">
						<th scope="row">
							<label for="envira-config-slug"><?php esc_html_e( 'Gallery Slug', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<input id="envira-config-slug" type="text" name="_envira_gallery[slug]" value="<?php echo esc_html( envira_get_config( 'slug', $gallery_data ) ); ?>" />
							<p class="description"><strong><?php esc_html_e( 'Unique', 'envira-gallery' ); ?></strong> <?php esc_html_e( 'internal gallery slug for identification and advanced gallery queries.', 'envira-gallery' ); ?></p>
						</td>
					</tr>
					<tr id="envira-config-classes-box">
						<th scope="row">
							<label for="envira-config-classes"><?php esc_html_e( 'Custom Gallery Classes', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<?php

							$classes        = envira_get_config( 'classes', $gallery_data );
							$classes_output = ( is_array( $classes ) ) ? ( implode( "\n", $classes ) ) : $classes;

							?>
							<textarea id="envira-config-classes" rows="5" cols="75" name="_envira_gallery[classes]" placeholder="<?php esc_html_e( 'Enter custom gallery CSS classes here, one per line.', 'envira-gallery' ); ?>"><?php echo esc_html( $classes_output ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Adds custom CSS classes to this gallery. Enter one class per line.', 'envira-gallery' ); ?></p>
						</td>
					</tr>

					<tr id="envira-config-import-box">
						<th scope="row">
							<label for="envira-config-import-gallery"><?php esc_html_e( 'Import Gallery', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<form></form>
							<?php
							$import_url = 'auto-draft' === $post->post_status ? add_query_arg(
								array(
									'post'   => $post->ID,
									'action' => 'edit',
									'envira-gallery-imported' => true,
								),
								admin_url( 'post.php' )
							) : add_query_arg( 'envira-gallery-imported', true );

							$import_url = esc_url( $import_url );
							?>
							<form action="<?php echo esc_url( $import_url ); ?>" id="envira-config-import-gallery-form" class="envira-gallery-import-form" method="post" enctype="multipart/form-data">

								<input type="file" name="envira_import_gallery" id="envira-config-import-gallery" class="importfile" data-multiple-caption="{count} files selected" />
								<label for="envira-config-import-gallery"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="17" viewBox="0 0 612 612"><path d="M494.7,255C476.85,168.3,400.35,102,306,102c-73.95,0-137.7,40.8-168.3,102C58.65,214.2,0,277.95,0,357c0,84.15,68.85,153,153,153h331.5c71.4,0,127.5-56.1,127.5-127.5C612,316.2,558.45,260.1,494.7,255z M357,331.5v102H255v-102h-76.5L306,204l127.5,127.5H357z"/></svg><span>Choose a file&hellip;</span></label>
								<input type="submit" name="envira-gallery-verify-submit" id="envira-gallery-verify-submit" class="button button-primary" value="Import">
								<input type="hidden" name="envira_import" value="1" />
								<input type="hidden" name="envira_post_id" value="<?php echo esc_html( $post->ID ); ?>" />
								<?php wp_nonce_field( 'envira-gallery-import', 'envira-gallery-import' ); ?>
							</form>

						</td>
					</tr>

					<tr id="envira-config-export-box">
						<th scope="row">
							<label for="envira-config-import-gallery"><?php esc_html_e( 'Export Gallery', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<form id="envira-config-export-gallery-form" method="post">
								<input type="hidden" name="envira_export" value="1" />
								<input type="hidden" name="envira_post_id" value="<?php echo esc_html( $post->ID ); ?>" />
								<?php wp_nonce_field( 'envira-gallery-export', 'envira-gallery-export' ); ?>
								<?php submit_button( __( 'Export', 'envira-gallery' ), 'button button-primary', 'envira-gallery-export-submit', false ); ?>
							</form>
						</td>
					</tr>

					<tr id="envira-config-rtl-box">
						<th scope="row">
							<label for="envira-config-rtl"><?php esc_html_e( 'Enable RTL Support?', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<input id="envira-config-rtl" type="checkbox" name="_envira_gallery[rtl]" value="<?php echo esc_html( envira_get_config( 'rtl', $gallery_data ) ); ?>" <?php checked( envira_get_config( 'rtl', $gallery_data ), 1 ); ?> />
							<span class="description">
								<?php if ( $this->whitelabel ) : ?>
									<?php
									// Translators: %1$s.
									echo esc_html( sprintf( __( 'Enables or disables RTL support in %1$s for right-to-left languages.', 'envira-gallery' ), apply_filters( 'envira_whitelabel_name', false ) ) );
									?>
								<?php else : ?>
									<?php esc_html_e( 'Enables or disables RTL support in Envira for right-to-left languages.', 'envira-gallery' ); ?>
								<?php endif; ?>

							</span>
						</td>
					</tr>
					<?php do_action( 'envira_gallery_misc_box', $post ); ?>
				</tbody>
			</table>
		</div>

		<?php
	}

	/**
	 * Callback for saving values from Envira metaboxes.
	 *
	 * @since 1.7.0
	 *
	 * @param int    $post_id The current post ID.
	 * @param object $post The current post object.
	 */
	public function save_meta_boxes( $post_id, $post ) {

		global $wpdb;

		// Bail out if we fail a security check.
		if ( ! isset( $_POST['envira-gallery'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['envira-gallery'] ) ), 'envira-gallery' ) || ! isset( $_POST['_envira_gallery'] ) ) {
			return;
		}

		// Bail out if running an autosave, ajax, cron or revision.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

			// Check if this is a Quick Edit request.
			if ( isset( $_POST['_inline_edit'] ) ) {

				// Just update specific fields in the Quick Edit screen
				// Get settings.
				$settings = get_post_meta( $post_id, '_eg_gallery_data', true );
				if ( empty( $settings ) ) {
					return;
				}

				// Update Settings.
				$settings['config']['columns']                 = isset( $_POST['_envira_gallery']['columns'] ) ? preg_replace( '#[^a-z0-9-_]#', '', sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['columns'] ) ) ) : false;
				$settings['config']['gallery_theme']           = isset( $_POST['_envira_gallery']['gallery_theme'] ) ? preg_replace( '#[^a-z0-9-_]#', '', sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['gallery_theme'] ) ) ) : false;
				$settings['config']['justified_gallery_theme'] = isset( $_POST['_envira_gallery']['justified_gallery_theme'] ) ? preg_replace( '#[^a-z0-9-_]#', '', sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['justified_gallery_theme'] ) ) ) : false;
				$settings['config']['gutter']                  = isset( $_POST['_envira_gallery']['gutter'] ) ? absint( $_POST['_envira_gallery']['gutter'] ) : false;
				$settings['config']['margin']                  = isset( $_POST['_envira_gallery']['margin'] ) ? absint( $_POST['_envira_gallery']['margin'] ) : false;
				$settings['config']['crop_width']              = isset( $_POST['_envira_gallery']['crop_width'] ) ? absint( $_POST['_envira_gallery']['crop_width'] ) > 0 ? absint( $_POST['_envira_gallery']['crop_width'] ) : envira_get_config_default( 'crop_width' ) : false;
				$settings['config']['crop_height']             = isset( $_POST['_envira_gallery']['crop_height'] ) ? absint( $_POST['_envira_gallery']['crop_height'] ) > 0 ? absint( $_POST['_envira_gallery']['crop_height'] ) : envira_get_config_default( 'crop_height' ) : false;

				// Provide a filter to override settings.
				$settings = apply_filters( 'envira_gallery_quick_edit_save_settings', $settings, $post_id, $post );

				// Update the post meta.
				update_post_meta( $post_id, '_eg_gallery_data', $settings );

				// Finally, flush all gallery caches to ensure everything is up to date.
				envira_flush_gallery_caches( $post_id, $settings['config']['slug'] );

			}

			return;
		}

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Bail out if the user doesn't have the correct permissions to update the slider.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// If the post has just been published for the first time, set meta field for the gallery meta overlay helper.
		if ( isset( $post->post_date ) && isset( $post->post_modified ) && $post->post_date === $post->post_modified ) {

			update_post_meta( $post_id, '_eg_just_published', true );

		}

		// Sanitize all user inputs.
		$settings = get_post_meta( $post_id, '_eg_gallery_data', true );

		if ( empty( $settings ) ) {

			$settings = array();

		}

		// Check if the lightbox theme has changed.
		$new_lb_theme = isset( $_POST['_envira_gallery']['lightbox_theme'] ) ? preg_replace( '#[^a-z0-9-_]#', '', sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['lightbox_theme'] ) ) ) : false;
		$old_lb_theme = ( isset( $settings['config']['lightbox_theme'] ) ) ? $settings['config']['lightbox_theme'] : false;

		// Check if the lightbox thumbnails state changed.
		$new_thumbnail_setting = isset( $_POST['_envira_gallery']['thumbnails'] ) ? 1 : 0;
		$old_thumbnail_setting = isset( $settings['config']['thumbnails'] ) ? $settings['config']['thumbnails'] : false;

		// Force slider ID to match Post ID. This is deliberate; if a gallery is duplicated (either using a duplication)
		// plugin or WPML, the ID remains as the original gallery ID, which breaks things for translations etc.
		$settings['id'] = $post_id;

		// Config.
		$settings['config']['type']               = isset( $_POST['_envira_gallery']['type'] ) ? sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['type'] ) ) : envira_get_config_default( 'type' );
		$settings['config']['columns']            = isset( $_POST['_envira_gallery']['columns'] ) ? preg_replace( '#[^a-z0-9-_]#', '', sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['columns'] ) ) ) : envira_get_config_default( 'columns' );
		$settings['config']['gallery_theme']      = isset( $_POST['_envira_gallery']['gallery_theme'] ) ? preg_replace( '#[^a-z0-9-_]#', '', sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['gallery_theme'] ) ) ) : envira_get_config_default( 'gallery_theme' );
		$settings['config']['justified_margins']  = isset( $_POST['_envira_gallery']['justified_margins'] ) ? absint( $_POST['_envira_gallery']['justified_margins'] ) : envira_get_config_default( 'justified_margins' );
		$settings['config']['justified_last_row'] = isset( $_POST['_envira_gallery']['justified_last_row'] ) ? sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['justified_last_row'] ) ) : envira_get_config_default( 'justified_last_row' );
		$settings['config']['lazy_loading']       = isset( $_POST['_envira_gallery']['lazy_loading'] ) ? 1 : 0;
		$settings['config']['lazy_loading_delay'] = isset( $_POST['_envira_gallery']['lazy_loading_delay'] ) ? absint( $_POST['_envira_gallery']['lazy_loading_delay'] ) : envira_get_config_default( 'lazy_loading_delay' );
		$settings['config']['gutter']             = isset( $_POST['_envira_gallery']['gutter'] ) ? absint( $_POST['_envira_gallery']['gutter'] ) : envira_get_config_default( 'gutter' );
		$settings['config']['margin']             = isset( $_POST['_envira_gallery']['margin'] ) ? absint( $_POST['_envira_gallery']['margin'] ) : envira_get_config_default( 'margin' );
		$settings['config']['image_size']         = isset( $_POST['_envira_gallery']['image_size'] ) ? sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['image_size'] ) ) : envira_get_config_default( 'image_size' );
		$settings['config']['crop_width']         = isset( $_POST['_envira_gallery']['crop_width'] ) ? absint( $_POST['_envira_gallery']['crop_width'] ) > 0 ? absint( $_POST['_envira_gallery']['crop_width'] ) : envira_get_config_default( 'crop_width' ) : envira_get_config_default( 'crop_width' );
		$settings['config']['crop_height']        = isset( $_POST['_envira_gallery']['crop_height'] ) ? absint( $_POST['_envira_gallery']['crop_height'] ) > 0 ? absint( $_POST['_envira_gallery']['crop_height'] ) : envira_get_config_default( 'crop_height' ) : envira_get_config_default( 'crop_height' );
		$settings['config']['crop']               = isset( $_POST['_envira_gallery']['crop'] ) ? 1 : 0;
		$settings['config']['crop_position']      = isset( $_POST['_envira_gallery']['crop_position'] ) ? preg_replace( '#[^a-z0-9-_]#', '', sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['crop_position'] ) ) ) : envira_get_config_default( 'crop_position' );

		// this is for isotope.
		foreach ( envira_get_additional_copy_options() as $value => $option ) {
			$settings['config'][ 'additional_copy_' . $value ] = ( isset( $_POST['_envira_gallery'][ 'additional_copy_' . $value ] ) ? 1 : 0 );
		}
		foreach ( envira_get_additional_copy_options() as $value => $option ) {
			$settings['config'][ 'additional_copy_' . $value . '_mobile' ] = ( isset( $_POST['_envira_gallery'][ 'additional_copy_' . $value . '_mobile' ] ) ? 1 : 0 );
		}

		// this is for automatic.
		foreach ( envira_get_additional_copy_options() as $value => $option ) {
			$settings['config'][ 'additional_copy_automatic_' . $value ] = ( isset( $_POST['_envira_gallery'][ 'additional_copy_automatic_' . $value ] ) ? 1 : 0 );
		}
		foreach ( envira_get_additional_copy_options() as $value => $option ) {
			$settings['config'][ 'additional_copy_automatic_' . $value . '_mobile' ] = ( isset( $_POST['_envira_gallery'][ 'additional_copy_automatic_' . $value . '_mobile' ] ) ? 1 : 0 );
		}

		// Automatic/Justified.
		$settings['config']['justified_row_height'] = isset( $_POST['_envira_gallery']['justified_row_height'] ) && 0 !== absint( $_POST['_envira_gallery']['justified_row_height'] ) ? absint( $_POST['_envira_gallery']['justified_row_height'] ) : 150;
		$settings['config']['description_position'] = isset( $_POST['_envira_gallery']['description_position'] ) ? preg_replace( '#[^a-z0-9-_]#', '', sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['description_position'] ) ) ) : envira_get_config_default( 'description_position' );
		$settings['config']['description']          = isset( $_POST['_envira_gallery']['description'] ) ? trim( wp_kses( wp_unslash( $_POST['_envira_gallery']['description'] ), apply_filters( 'envira_gallery_allowed_html', $this->wp_kses_allowed_html, $post ) ) ) : false;
		$settings['config']['random']               = ( isset( $_POST['_envira_gallery']['random'] ) ) ? preg_replace( '#[^a-z0-9-_]#', '', sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['random'] ) ) ) : false;
		$settings['config']['sort_order']           = ( isset( $_POST['_envira_gallery']['random'] ) ) ? preg_replace( '#[^a-z0-9-_]#', '', sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['random'] ) ) ) : false;
		$settings['config']['sorting_direction']    = isset( $_POST['_envira_gallery']['sorting_direction'] ) ? sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['sorting_direction'] ) ) : false;
		$settings['config']['image_sizes_random']   = isset( $_POST['_envira_gallery']['image_sizes_random'] ) ? $_POST['_envira_gallery']['image_sizes_random'] : array(); // @codingStandardsIgnoreLine
		$settings['config']['isotope']              = isset( $_POST['_envira_gallery']['isotope'] ) ? 1 : 0;

		// Lightbox.
		$settings['config']['lightbox_enabled']       = isset( $_POST['_envira_gallery']['lightbox_enabled'] ) ? 1 : 0;
		$settings['config']['gallery_link_enabled']   = isset( $_POST['_envira_gallery']['gallery_link_enabled'] ) ? 1 : 0;
		$settings['config']['lightbox_theme']         = isset( $_POST['_envira_gallery']['lightbox_theme'] ) ? preg_replace( '#[^a-z0-9-_]#', '', sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['lightbox_theme'] ) ) ) : envira_get_config_default( 'lightbox_theme' );
		$settings['config']['lightbox_image_size']    = isset( $_POST['_envira_gallery']['lightbox_image_size'] ) ? preg_replace( '#[^a-z0-9-_]#', '', sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['lightbox_image_size'] ) ) ) : envira_get_config_default( 'lightbox_image_size' );
		$settings['config']['title_display']          = isset( $_POST['_envira_gallery']['title_display'] ) ? preg_replace( '#[^a-z0-9-_]#', '', sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['title_display'] ) ) ) : envira_get_config_default( 'title_display' );
		$settings['config']['lightbox_title_caption'] = isset( $_POST['_envira_gallery']['lightbox_title_caption'] ) ? preg_replace( '#[^a-z0-9-_]#', '', sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['lightbox_title_caption'] ) ) ) : envira_get_config_default( 'lightbox_title_caption' );

		$settings['config']['arrows']                     = isset( $_POST['_envira_gallery']['arrows'] ) ? 1 : 0;
		$settings['config']['arrows_position']            = isset( $_POST['_envira_gallery']['arrows_position'] ) ? preg_replace( '#[^a-z0-9-_]#', '', sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['arrows_position'] ) ) ) : envira_get_config_default( 'arrows_position' );
		$settings['config']['toolbar']                    = isset( $_POST['_envira_gallery']['toolbar'] ) ? 1 : 0;
		$settings['config']['toolbar_title']              = isset( $_POST['_envira_gallery']['toolbar_title'] ) ? 1 : 0;
		$settings['config']['toolbar_position']           = isset( $_POST['_envira_gallery']['toolbar_position'] ) ? preg_replace( '#[^a-z0-9-_]#', '', sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['toolbar_position'] ) ) ) : envira_get_config_default( 'toolbar_position' );
		$settings['config']['loop']                       = isset( $_POST['_envira_gallery']['loop'] ) ? 1 : 0;
		$settings['config']['lightbox_open_close_effect'] = isset( $_POST['_envira_gallery']['lightbox_open_close_effect'] ) ? preg_replace( '#[^A-Za-z0-9-_]#', '', sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['lightbox_open_close_effect'] ) ) ) : envira_get_config_default( 'lightbox_open_close_effect' );
		$settings['config']['effect']                     = isset( $_POST['_envira_gallery']['effect'] ) ? preg_replace( '#[^A-Za-z0-9-_]#', '', sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['effect'] ) ) ) : false;
		$settings['config']['supersize']                  = isset( $_POST['_envira_gallery']['supersize'] ) ? 1 : 0;
		$settings['config']['thumbnails_toggle']          = isset( $_POST['_envira_gallery']['thumbnails_toggle'] ) ? 1 : 0;
		$settings['config']['thumbnails_hide']            = isset( $_POST['_envira_gallery']['thumbnails_hide'] ) ? 1 : 0;
		$settings['config']['image_counter']              = isset( $_POST['_envira_gallery']['image_counter'] ) ? 1 : 0;

		// Lightbox Thumbnails.
		$settings['config']['thumbnails']             = isset( $_POST['_envira_gallery']['thumbnails'] ) ? 1 : 0;
		$settings['config']['thumbnails_width']       = isset( $_POST['_envira_gallery']['thumbnails_width'] ) ? absint( $_POST['_envira_gallery']['thumbnails_width'] ) : envira_get_config_default( 'thumbnails_width' );
		$settings['config']['thumbnails_height']      = isset( $_POST['_envira_gallery']['thumbnails_height'] ) ? absint( $_POST['_envira_gallery']['thumbnails_height'] ) : envira_get_config_default( 'thumbnails_height' );
		$settings['config']['thumbnails_position']    = isset( $_POST['_envira_gallery']['thumbnails_position'] ) ? preg_replace( '#[^a-z0-9-_]#', '', sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['thumbnails_position'] ) ) ) : envira_get_config_default( 'thumbnails_position' );
		$settings['config']['thumbnails_custom_size'] = isset( $_POST['_envira_gallery']['thumbnails_custom_size'] ) ? 1 : 0;

		// Mobile.
		$settings['config']['mobile']                      = isset( $_POST['_envira_gallery']['mobile'] ) ? 1 : 0;
		$settings['config']['mobile_width']                = isset( $_POST['_envira_gallery']['mobile_width'] ) ? absint( $_POST['_envira_gallery']['mobile_width'] ) : envira_get_config_default( 'mobile_width' );
		$settings['config']['mobile_height']               = isset( $_POST['_envira_gallery']['mobile_height'] ) ? absint( $_POST['_envira_gallery']['mobile_height'] ) : envira_get_config_default( 'mobile_height' );
		$settings['config']['mobile_lightbox']             = isset( $_POST['_envira_gallery']['mobile_lightbox'] ) ? 1 : 0;
		$settings['config']['mobile_gallery_link_enabled'] = isset( $_POST['_envira_gallery']['mobile_gallery_link_enabled'] ) ? 1 : 0;
		$settings['config']['mobile_arrows']               = isset( $_POST['_envira_gallery']['mobile_arrows'] ) ? 1 : 0;
		$settings['config']['mobile_toolbar']              = isset( $_POST['_envira_gallery']['mobile_toolbar'] ) ? 1 : 0;
		$settings['config']['mobile_thumbnails']           = isset( $_POST['_envira_gallery']['mobile_thumbnails'] ) ? 1 : 0;
		$settings['config']['mobile_touchwipe_close']      = isset( $_POST['_envira_gallery']['mobile_touchwipe_close'] ) ? 1 : 0;
		$settings['config']['mobile_thumbnails_width']     = isset( $_POST['_envira_gallery']['mobile_thumbnails_width'] ) && 0 !== $_POST['_envira_gallery']['mobile_thumbnails_width'] ? absint( $_POST['_envira_gallery']['mobile_thumbnails_width'] ) : 75;
		$settings['config']['mobile_thumbnails_height']    = isset( $_POST['_envira_gallery']['mobile_thumbnails_height'] ) && 0 !== $_POST['_envira_gallery']['mobile_thumbnails_height'] ? absint( $_POST['_envira_gallery']['mobile_thumbnails_height'] ) : 50;
		$settings['config']['mobile_justified_row_height'] = isset( $_POST['_envira_gallery']['mobile_justified_row_height'] ) && 0 !== absint( $_POST['_envira_gallery']['mobile_justified_row_height'] ) ? absint( $_POST['_envira_gallery']['mobile_justified_row_height'] ) : 80;

		// Depricated values.
		$settings['config']['keyboard'] = true;

		// Standalone.
		if ( envira_get_setting( 'standalone_enabled' ) ) {
			$settings['config']['standalone_template'] = ( isset( $_POST['_envira_gallery']['standalone_template'] ) ? str_replace( '-php', '.php', sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['standalone_template'] ) ) ) : '' );
		}

		$settings['config']['classes'] = isset( $_POST['_envira_gallery']['classes'] ) ? explode( "\n", sanitize_text_field( wp_unslash( $_POST['_envira_gallery']['classes'] ) ) ) : array();
		$settings['config']['rtl']     = isset( $_POST['_envira_gallery']['rtl'] ) ? 1 : 0;

		/* slug */

		$slug_to_save               = ( ! empty( $_POST['_envira_gallery']['slug'] ) ) ? sanitize_title( wp_unslash( $_POST['_envira_gallery']['slug'] ) ) : sanitize_title( $post->post_name );
		$settings['config']['slug'] = $slug_to_save;

		// We need to add metadata if the config slug doesn't match.
		if ( ! empty( $slug_to_save ) && $slug_to_save !== $post->post_name ) {

			$existing_page = get_page_by_path( $slug_to_save, ARRAY_A, array( 'envira', 'envira_album', 'post', 'page' ) );

			// Does this slug exist for any other post?
			if ( $existing_page ) {

				$this->duplicate_post_id = $existing_page['ID'];

				// Generate a unique slug, like WP does, and place it in settings.
				$slug_to_save = wp_unique_post_slug( $slug_to_save, $post_id, $post->post_status, 'envira', false );

				add_filter( 'redirect_post_location', array( $this, 'add_notice_slug_exists' ), 99 );

			}

			if ( ! wp_is_post_revision( $post_id ) ) {

				// unhook this function so it doesn't loop infinitely.
				remove_action( 'save_post', array( $this, 'save_meta_boxes' ) );

				// update the post, which calls save_post again.
				wp_update_post(
					array(
						'ID'        => $post_id,
						'post_name' => $slug_to_save,
					)
				);

				// re-hook this function.
				add_action( 'save_post', array( $this, 'save_meta_boxes' ) );

			}

			// finally update the envira gallery slug meta data.
			update_post_meta( $post_id, 'envira_gallery_slug', $slug_to_save );

		} else {

			// this metadata SHOULD no longer be needed, so let's delete it if it exists and the slug is what it should be anyway.
			delete_post_meta( $post_id, 'envira_gallery_slug' );

		}

		$settings['config']['slug'] = $slug_to_save;

		// Provide a filter to override settings.
		$settings = apply_filters( 'envira_gallery_save_settings', $settings, $post_id, $post );

		// Fire a hook for addons.
		do_action( 'envira_gallery_pre_save_settings', $settings, $post_id, $post );

		// Update the post meta.
		update_post_meta( $post_id, '_eg_gallery_data', $settings );

		// Update the Gallery Version.
		update_post_meta( $post_id, '_eg_version', ENVIRA_VERSION );

		// Fire a hook for addons that need to utilize the cropping feature.
		do_action( 'envira_gallery_saved_settings', $settings, $post_id, $post );

		envira_crop_images( $post_id );

		// Finally, flush all gallery caches to ensure everything is up to date.
		envira_flush_gallery_caches( $post_id, $settings['config']['slug'] );

	}

	/**
	 * Add notice for duplicate slugs
	 *
	 * @since 1.8.4
	 * @param    array $location    Location.
	 */
	public function add_notice_slug_exists( $location ) {

		remove_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );

		return add_query_arg( array( 'envira_slug_exists' => $this->duplicate_post_id ), $location );

	}

	/**
	 * Quick check to see if the slug exists ANYWHERE, not just in Envira custom post types
	 *
	 * @since 1.8.4
	 * @param    array $post_name    Post Name.
	 */
	public function the_slug_exists( $post_name ) {

		global $wpdb;

		if ( $wpdb->get_row( $wpdb->prepare( 'SELECT post_name FROM wp_posts WHERE post_name = %s', '"' . $post_name . '"' ), 'ARRAY_A' ) ) { // @codingStandardsIgnoreLine
			return true;
		} else {
			return false;
		}

	}











}
