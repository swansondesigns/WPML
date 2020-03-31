<?php
/**
 * Outputs the Gallery Code Metabox Content.
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

<p><?php esc_html_e( 'You can place this gallery anywhere into your posts, pages, custom post types or widgets by using the shortcode below:', 'envira-gallery' ); ?></p>
<div class="envira-code">
	<input readonly type="text" class="code-textfield" id="envira_shortcode_id_<?php echo esc_attr( $data['post']->ID ); ?>" value="[envira-gallery id=&quot;<?php echo esc_attr( $data['post']->ID ); ?>&quot;]">
	<a href="#" title="<?php esc_html_e( 'Copy Shortcode to Clipboard', 'envira-gallery' ); ?>" data-clipboard-target="#envira_shortcode_id_<?php echo esc_attr( $data['post']->ID ); ?>" class="dashicons dashicons-clipboard envira-clipboard">
		<span><?php esc_html_e( 'Copy to Clipboard', 'envira-gallery' ); ?></span>
	</a>
</div>

<?php
if ( ! empty( $data['gallery_data']['config']['slug'] ) ) {
	?>
	<div class="envira-code">
	<textarea readonly class="code-textfield" id="envira_shortcode_slug_<?php echo esc_attr( $data['post']->ID ); ?>">[envira-gallery slug=&quot;<?php echo esc_attr( $data['gallery_data']['config']['slug'] ); ?>&quot;]</textarea>
		<a href="#" title="<?php esc_html_e( 'Copy Shortcode to Clipboard', 'envira-gallery' ); ?>" data-clipboard-target="#envira_shortcode_slug_<?php echo esc_attr( $data['post']->ID ); ?>" class="dashicons dashicons-clipboard envira-clipboard">
			<span><?php esc_html_e( 'Copy to Clipboard', 'envira-gallery' ); ?></span>
		</a>
	</div>
	<?php
}
?>

<p><?php esc_html_e( 'You are able to use a special shortcode to open a gallery with a link:', 'envira-gallery' ); ?></p>
<div class="envira-code">
	<textarea readonly class="code-textfield" id="envira_template_tag_link_<?php echo esc_attr( $data['post']->ID ); ?>">[envira-link id=&quot;<?php echo esc_attr( $data['post']->ID ); ?>&quot;]Click here[/envira-link]</textarea>
	<a href="#" title="<?php esc_attr_e( 'Copy Template Tag to Clipboard', 'envira-gallery' ); ?>" data-clipboard-target="#envira_template_tag_link_<?php echo esc_attr( $data['post']->ID ); ?>" class="dashicons dashicons-clipboard envira-clipboard">
		<span><?php esc_html_e( 'Copy to Clipboard', 'envira-gallery' ); ?></span>
	</a>
</div>

<p><?php esc_html_e( 'You can place this gallery into your template files by using the template tag below:', 'envira-gallery' ); ?></p>
<div class="envira-code">
	<textarea readonly rows="2" class="code-textfield" id="envira_template_tag_id_<?php echo esc_attr( $data['post']->ID ); ?>"><?php echo 'if ( function_exists( \'envira_gallery\' ) ) { envira_gallery( \'' . esc_attr( $data['post']->ID ) . '\' ); }'; ?></textarea>
	<a href="#" title="<?php esc_attr_e( 'Copy Template Tag to Clipboard', 'envira-gallery' ); ?>" data-clipboard-target="#envira_template_tag_id_<?php echo esc_attr( $data['post']->ID ); ?>" class="dashicons dashicons-clipboard envira-clipboard">
		<span><?php esc_html_e( 'Copy to Clipboard', 'envira-gallery' ); ?></span>
	</a>
</div>

<?php
if ( ! empty( $data['gallery_data']['config']['slug'] ) ) {
	?>
	<div class="envira-code">
		<textarea readonly rows="3" class="code-textfield" id="envira_template_tag_slug_<?php echo esc_attr( $data['post']->ID ); ?>"><?php echo 'if ( function_exists( \'envira_gallery\' ) ) { envira_gallery( \'' . esc_attr( $data['gallery_data']['config']['slug'] ) . '\', \'slug\' ); }'; ?></textarea>
		<a href="#" title="<?php esc_html_e( 'Copy Template Tag to Clipboard', 'envira-gallery' ); ?>" data-clipboard-target="#envira_template_tag_slug_<?php echo esc_attr( $data['post']->ID ); ?>" class="dashicons dashicons-clipboard envira-clipboard">
			<span><?php esc_html_e( 'Copy to Clipboard', 'envira-gallery' ); ?></span>
		</a>
	</div>
	<?php
}
?>
