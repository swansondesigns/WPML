<?php
/**
 * Outputs the green Envira Gallery Header
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
<div id="envira-header-temp"></div>
<div id="envira-header" class="envira-header">
	<?php if ( apply_filters( 'envira_whitelabel', false ) ) : ?>
		<?php do_action( 'envira_whitelabel_header_logo' ); ?>
	<?php else : ?>
	<h1 class="envira-logo" id="envira-logo">
		<img src="<?php echo esc_url( $data['logo'] ); ?>" alt="<?php esc_html_e( 'Envira Gallery', 'envira-gallery' ); ?>" width="339" height="26" />
	</h1>
	<?php endif; ?>
</div>
