<?php
/**
 * Status class.
 *
 * @since 1.7.0
 *
 * @package Envira_Gallery
 * @author  Envira Gallery Team <support@enviragallery.com>
 */

namespace Envira\Admin\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Status class.
 *
 * @since 1.7.0
 *
 * @package Envira_Gallery
 * @author  Envira Gallery Team <support@enviragallery.com>
 */
class Status {
	/**
	 * Register the Addons submenu item for Envira.
	 *
	 * @since 1.7.0
	 */
	public function admin_menu() {

		// Check and see if whitelabeling is active... by default this screen shouldn't be accessible when whitelabeling is on.
		if ( apply_filters( 'envira_whitelabel', false ) ) {
			if ( ! apply_filters( 'envira_whitelabel_addon_screen', false ) ) {
				return;
			}
		}
		$label = apply_filters( 'envira_whitelabel', false ) ? '' : __( 'Envira Gallery ', 'envira-gallery' );
		// Register the submenu.
		$this->hook = add_submenu_page(
			'edit.php?post_type=envira',
			$label . __( 'Status', 'envira-gallery' ),
			'<span style="color:#7cc048"> ' . __( 'Status', 'envira-gallery' ) . '</span>',
			apply_filters( 'envira_gallery_menu_cap', 'manage_options' ),
			ENVIRA_SLUG . '-status',
			array( $this, 'status_page' )
		);

	}

	/**
	 * Status pageholder function.
	 *
	 * @since 1.7.0
	 */
	public function status_page() {

	}

}
