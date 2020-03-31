<?php

if ( ! function_exists('sd_truncate_string') ) {

	function sd_truncate_string($phrase, $max_words) {
		$phrase_array = explode(' ',$phrase);
		if(count($phrase_array) > $max_words && $max_words > 0)
		$phrase = implode(' ',array_slice($phrase_array, 0, $max_words));
		return $phrase;
	}

}

if ( ! function_exists('sd_get_youtube_video_id_from_url') ) {

	function sd_get_youtube_video_id_from_url($video_url) {

		// We need to extract just the ID from the video URL
		preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $video_url, $match);

		// ID will be the first result in the array
		if ( is_array( $match ) ) {

			return $match[1];

		} else {

			return false;

		}

	}

}


if ( ! function_exists('sd_get_page_excerpt') ) {

	// Pages, by default, do not have excerpts so we need a function that replicates the WP core for pages.
	function sd_get_page_excerpt( $page_id, $max_words ) {

		// We will need the page object for a couple things, so may as well grab it now.
		$page_object = get_page( $page_id );

		// Check to see if the page has an excerpt set.  Excerpt support must be added to pages:  add_post_type_support( 'page', 'excerpt' );
		if ( $page_object->post_excerpt ) {

			// If we have an excerpt, we are good to go.
			$excerpt = $page_object->post_excerpt;

		} else {

			// If there is no excerpt set, we check to see if there is a <!-- more --> tag using get_extended().
			$result = get_extended( $page_object->post_content );

			// The get_extended() function returns an array with ['main'] and ['extended'] as before and after the <!-- more --> tag.
			// Content without a <!-- more --> tag will only return ['main'].

			// So, if we have ['extended'], then we must have a <!-- more --> tag.
			if ( $result['extended'] ) {

				// That means we can use the part before the <!-- more --> tag as the excerpt.
				$excerpt = $result['main'];

			} else {

				// If we don't have a <!-- more --> tag, we need to grab the first part of the entry content.

				// We'll use the truncate_string() function to set the length of the excerpt.
				$excerpt = truncate_string( $page_object->post_content, $max_words ) . '<span class="end-hellip">&hellip;</span>';

				//isset($max_words) || $max_words = 40;

			}

		}

		// Once we have our excerpt string, we clean it up and return it.
		return $excerpt;

	}

}
