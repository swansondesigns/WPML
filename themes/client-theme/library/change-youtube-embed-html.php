<?php
/**
 *	Modify Default YouTube Embed HMTL
 *
 *	@author Graham Swanson
 *
 * @see WP_Embed::shortcode()
 *
 * @param mixed  $cache   The cached HTML result, stored in post meta.
 * @param string $url     The attempted embed URL.
 * @param array  $attr    An array of shortcode attributes.
 * @param int    $post_ID Post ID.
 */

function sd_modify_default_youtube_embed_html( $cache, $url ) {

	if ( $youtube_id = sd_get_youtube_video_id_from_url($url)  ) {

		$cache = '<div class="youtube-iframe-wrapper-outer"><div class="youtube-iframe-wrapper-inner">
		<iframe src="https://www.youtube.com/embed/' . $youtube_id . '?feature=oembed" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen=""></iframe>
			</div></div>';
	}

	return $cache;
}
add_filter( 'embed_oembed_html', 'sd_modify_default_youtube_embed_html', 2, 99 );
