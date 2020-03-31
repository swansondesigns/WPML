<?php
/**
 * Handles all admin ajax interactions for the Envira Gallery plugin.
 *
 * @since 1.5.0
 *
 * @package Envira Gallery
 * @author  Envira Gallery Team <support@enviragallery.com>
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

?>

<!-- Add from Media Library -->
<a href="#" class="envira-media-library button" title="<?php esc_html_e( 'Click Here to Insert from Other Image Sources', 'envira-gallery' ); ?>" style="vertical-align: baseline;">
	<?php esc_html_e( 'Select Files from Other Sources', 'envira-gallery' ); ?>
</a>

<!-- Progress Bar -->
<div class="envira-progress-bar">
	<div class="envira-progress-bar-inner"></div>
	<div class="envira-progress-bar-status">
		<span class="uploading">
			<?php esc_html_e( 'Uploading Image', 'envira-gallery' ); ?>
			<span class="current">1</span>
			<?php esc_html_e( 'of', 'envira-gallery' ); ?>
			<span class="total">3</span>
		</span>

		<span class="done"><?php esc_html_e( 'All images uploaded.', 'envira-gallery' ); ?></span>
		<span class="uploading_zip"><?php esc_html_e( 'Zip file uploaded.', 'envira-gallery' ); ?></span>
		<span class="opening_zip"><span class="spinner"></span> <?php esc_html_e( 'Adding images from Zip file.', 'envira-gallery' ); ?></span>
		<span class="done_zip"><?php esc_html_e( 'Zip import complete.', 'envira-gallery' ); ?></span>
	</div>
</div>

<div class="envira-progress-adding-images">
	<div class="envira-progress-status">
		<span class="spinner"></span><span class="adding_images"><?php esc_html_e( 'Adding items to gallery.', 'envira-gallery' ); ?></span>
	</div>
</div>
