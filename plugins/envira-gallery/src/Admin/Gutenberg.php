<?php
/**
 * Gutenberg class.
 *
 * @since 1.8.5
 *
 * @package Envira_Gallery
 * @author  Envira Gallery Team <support@enviragallery.com>
 */

namespace Envira\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gutenberg class.
 *
 * @since 1.8.5
 */
class Gutenberg {

	/**
	 * Flag to determine if media modal is loaded.
	 *
	 * @since 1.8.5
	 *
	 * @var object
	 */
	public $loaded = false;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.8.5
	 */
	public function __construct() {

		add_action( 'enqueue_block_assets', array( $this, 'block_assets' ), 10 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'editor_assets' ), 10 );

	}

	/**
	 * Enqueue Gutenberg block assets for both frontend + backend.
	 *
	 * `wp-blocks`: includes block type registration and related functions.
	 *
	 * @since 1.0.0
	 */
	public function block_assets() {

		wp_enqueue_style(
			'envira_gutenberg-style-css', // Handle.
			plugins_url( 'assets/css/blocks.style.build.css', ENVIRA_FILE ), // Block style CSS.
			array( 'wp-blocks' ), // Dependency to include the CSS after it.
			ENVIRA_VERSION
		);

	}


	/**
	 * Enqueue Gutenberg block assets for backend editor.
	 *
	 * `wp-blocks`: includes block type registration and related functions.
	 * `wp-element`: includes the WordPress Element abstraction for describing the structure of your blocks.
	 * `wp-i18n`: To internationalize the block's text.
	 *
	 * @since 1.0.0
	 */
	public function editor_assets() {

		wp_enqueue_script(
			'envira_gutenberg-block-js',
			plugins_url( 'assets/js/envira-gutenberg.js', ENVIRA_FILE ), // Block.build.js: we register the block here and built with Webpack.
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-editor' ), // dependencies, defined above.
			ENVIRA_VERSION,
			true // Enqueue the script in the footer.
		);

		$columns = envira_get_columns();

		$new_columns = array();
		foreach ( $columns as $options ) {
			$new_columns[] = array(
				'label' => $options['name'],
				'value' => $options['value'],
			);
		}
		$lightbox_options = envira_get_lightbox_themes();

		$new_lightbox = array();
		foreach ( $lightbox_options as $options ) {
			$new_lightbox[] = array(
				'label' => $options['name'],
				'value' => $options['value'],
			);
		}

		$image_option = envira_get_image_sizes();

		$new_sizes = array();
		foreach ( $image_option as $options ) {
			$new_sizes[] = array(
				'label' => $options['name'],
				'value' => $options['value'],
			);
		}
		$options    = array(
			'columns'         => $new_columns,
			'lightbox_themes' => $new_lightbox,
			'image_sizes'     => $new_sizes,
			'sorting_options' => envira_get_sorting_options(),
		);
		$args_array = array(
			'options' => $options,
			'isLite'  => false,
		);
		wp_localize_script(
			'envira_gutenberg-block-js',
			'envira_args',
			$args_array
		);

		// Styles.
		wp_enqueue_style(
			'envira_gutenberg-block-editor-css', // Handle.
			plugins_url( 'assets/css/blocks.editor.build.css', ENVIRA_FILE ), // Block editor CSS.
			array( 'wp-edit-blocks' ), // Dependency to include the CSS after it.
			ENVIRA_VERSION
		);
	}

}
