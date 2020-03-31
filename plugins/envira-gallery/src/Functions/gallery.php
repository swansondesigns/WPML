<?php
/**
 * Envira Gallery Functions.
 *
 * @since 1.7.0
 *
 * @package Envira_Gallery
 * @author  Envira Gallery Team <support@enviragallery.com>
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the Gallery Object.
 *
 * @since 1.7.0
 *
 * @access public
 * @param mixed   $gallery_id Gallery ID.
 * @param boolean $flush_cache Flush Cache.
 * @return array
 */
function envira_get_gallery( $gallery_id, $flush_cache = false ) {

	$gallery = get_transient( '_eg_cache_' . $gallery_id );

	// Attempt to return the transient first, otherwise generate the new query to retrieve the data.
	if ( true === $flush_cache || false === $gallery ) {
		$gallery = _envira_get_gallery( $gallery_id );
		if ( $gallery ) {
			$expiration = envira_get_transient_expiration_time();
			set_transient( '_eg_cache_' . $gallery_id, $gallery, $expiration );
		}
	}

	// Return the gallery data.
	return $gallery;

}

/**
 * Internal method that returns a gallery based on ID.
 *
 * @since 1.7.0
 *
 * @param int $gallery_id     The gallery ID used to retrieve a gallery.
 * @return array|bool Array of gallery data or false if none found.
 */
function _envira_get_gallery( $gallery_id ) {

	$meta = get_post_meta( $gallery_id, '_eg_gallery_data', true );

	/**
	* Version 1.2.1+: Check if $meta has a value - if not, we may be using a Post ID but the gallery
	* has moved into the Envira CPT
	*/
	if ( empty( $meta ) ) {
		$gallery_id = get_post_meta( $gallery_id, '_eg_gallery_id', true );
		$meta       = get_post_meta( $gallery_id, '_eg_gallery_data', true );
	}

	return $meta;

}

/**
 * Envira_get_gallery_by_slug function.
 *
 * @since 1.7.0
 *
 * @access public
 * @param string $slug Gallery Slug.
 * @return array
 */
function envira_get_gallery_by_slug( $slug ) {

	// Attempt to return the transient first, otherwise generate the new query to retrieve the data.
	$gallery = get_transient( '_eg_cache_' . $slug );

	if ( false === $gallery ) {

		$gallery = _envira_get_gallery_by_slug( $slug );

		if ( $gallery ) {
			$expiration = envira_get_transient_expiration_time();
			set_transient( '_eg_cache_' . $slug, $gallery, $expiration );
		}
	}

	// Return the gallery data.
	return $gallery;

}

/**
 * _envira_get_gallery_by_slug function.
 *
 * @since 1.7.0
 *
 * @access private
 * @param string $slug Gallery Slug.
 * @return boolean
 */
function _envira_get_gallery_by_slug( $slug ) {

	// Get Envira CPT by slug.
	$galleries = new WP_Query(
		array(
			'post_type'      => 'envira',
			'name'           => $slug,
			'fields'         => 'ids',
			'posts_per_page' => 1,
		)
	);

	if ( $galleries->posts ) {
		return get_post_meta( $galleries->posts[0], '_eg_gallery_data', true );
	}

	// Get Envira CPT by meta-data field (yeah this is an edge case dealing with slugs in shortcode and modified slug in the misc tab of the gallery).
	$galleries = new WP_Query(
		array(
			'post_type'      => 'envira',
			'meta_key'       => 'envira_gallery_slug', // @codingStandardsIgnoreLine
			'meta_value'     => $slug, // @codingStandardsIgnoreLine
			'fields'         => 'ids',
			'posts_per_page' => 1,
		)
	);

	if ( $galleries->posts ) {
		return get_post_meta( $galleries->posts[0], '_eg_gallery_data', true );
	}

	// If nothing found, get Envira CPT by _eg_gallery_old_slug.
	// This covers Galleries migrated from Pages/Posts --> Envira CPTs.
	$galleries = new WP_Query(
		array(
			'post_type'      => 'envira',
			'no_found_rows'  => true,
			'cache_results'  => false,
			'fields'         => 'ids',
			'meta_query'     => array( // @codingStandardsIgnoreLine
				array(
					'key'   => '_eg_gallery_old_slug',
					'value' => $slug,
				),
			),
			'posts_per_page' => 1,
		)
	);

	if ( $galleries->posts ) {
		return get_post_meta( $galleries->posts[0], '_eg_gallery_data', true );
	}

	// No galleries found.
	return false;

}

/**
 * Envira Get Galleries function.
 *
 * @since 1.7.0
 *
 * @access public
 * @param bool   $skip_empty (default: true).
 * @param bool   $ignore_cache (default: false).
 * @param string $search_terms (default: '').
 * @return array
 */
function envira_get_galleries( $skip_empty = true, $ignore_cache = false, $search_terms = '' ) {

	$galleries = get_transient( '_eg_cache_all' );

	// Attempt to return the transient first, otherwise generate the new query to retrieve the data.
	if ( $ignore_cache || ! empty( $search_terms ) || false === $galleries ) {
		$galleries = _envira_get_galleries( $skip_empty, $search_terms );

		// Cache the results if we're not performing a search and we have some results.
		if ( $galleries && empty( $search_terms ) ) {
			$expiration = envira_get_transient_expiration_time();
			set_transient( '_eg_cache_all', $galleries, $expiration );
		}
	}

	// Return the gallery data.
	return $galleries;

}

/**
 * Envira Get Galleries function.
 *
 * @since 1.7.0
 *
 * @access public
 * @param bool   $skip_empty (default: true).
 * @param string $search_terms (default: '').
 * @param int    $posts_per_page (default: 99).
 * @param string $orderby (default: post_date).
 * @param string $order (default: DESC).
 * @return $ret array Gallery data.
 */
function _envira_get_galleries( $skip_empty = true, $search_terms = '', $posts_per_page = 99, $orderby = 'post_date', $order = 'DESC' ) {

	// Build WP_Query arguments.
	$args = array(
		'post_type'      => 'envira',
		'post_status'    => 'publish',
		'posts_per_page' => $posts_per_page,
		'no_found_rows'  => true,
		'fields'         => 'ids',
		'orderby'        => $orderby,
		'order'          => $order,
		'meta_query'     => array( // @codingStandardsIgnoreLine
			array(
				'key'     => '_eg_gallery_data',
				'compare' => 'EXISTS',
			),
		),
	);

	// If search terms exist, add a search parameter to the arguments.
	if ( ! empty( $search_terms ) ) {
		$args['s'] = $search_terms;
	}

	// Run WP_Query.
	$galleries = new WP_Query( $args );

	if ( ! isset( $galleries->posts ) || empty( $galleries->posts ) ) {
		return false;
	}

	// Now loop through all the galleries found and only use galleries that have images in them.
	$ret = array();
	foreach ( $galleries->posts as $id ) {

		$data = get_post_meta( $id, '_eg_gallery_data', true );

		// Skip empty galleries.
		if ( $skip_empty && empty( $data['gallery'] ) ) {
			continue;
		}

		// Skip default/dynamic gallery types.
		$type = envira_get_config( 'type', $data );
		if ( 'defaults' === envira_get_config( 'type', $data ) || 'dynamic' === envira_get_config( 'type', $data ) ) {
			continue;
		}

		// Add title.
		$data['config']['title'] = get_the_title( $id );

		// Add gallery to array of galleries.
		$ret[] = $data;
	}

	// Return the gallery data.
	return $ret;

}

/**
 * Gallery image count function.
 *
 * @since 1.7.0
 *
 * @access public
 * @param mixed $gallery_id Gallery Id.
 * @return int
 */
function envira_get_gallery_image_count( $gallery_id ) {

	$data = get_post_meta( $gallery_id, '_eg_gallery_data', true );

	$gallery = apply_filters( 'envira_images_pre_data', $data, $gallery_id );

	return ( isset( $gallery['gallery'] ) ? count( $gallery['gallery'] ) : 0 );

}

/**
 * Returns full Gallery Config defaults to json object.
 *
 * @since 1.7.1
 *
 * @access public
 * @param mixed   $gallery_id Gallery Id.
 * @param boolean $raw Raw.
 * @param array   $data Gallery Data.
 * @return string
 */
function envira_get_gallery_config( $gallery_id, $raw = false, $data = null ) {

	if ( ! isset( $gallery_id ) ) {

		return false;

	}

	$images = array();

	if ( ! empty( $data ) && ( 'dynamic' === $data['config']['type'] || 'default' === $data['config']['type'] ) ) {

		$data          = $data;
		$original_data = $data;

	} else {

		$data          = envira_get_gallery( $gallery_id );
		$original_data = $data;

		// temp hack: preserve keyboard and mousewheel settings (see 1980).
		$keyboard   = isset( $data['config']['keyboard'] ) ? $data['config']['keyboard'] : 1;
		$mousewheel = isset( $data['config']['mousewheel'] ) ? $data['config']['mousewheel'] : 1;

		// below filter makes keyboard 0 and makes mousewheel reappear as 0.
		$data['config']['keyboard']   = $keyboard;
		$data['config']['mousewheel'] = $mousewheel;

		$data = apply_filters( 'envira_gallery_pre_data', $data, $gallery_id );

	}

	if ( ! isset( $data['config']['gallery_id'] ) && isset( $data['id'] ) ) {
		$data['config']['gallery_id'] = $data['id'];
	}

	if ( $raw ) {

		return $data['config'];

	}

	// Santitize Description.
	if ( ! empty( $data['config']['description'] ) ) {
		$data['config']['description'] = envira_santitize_description( $data['config']['description'] );
	}

	// Santitize Options
	// Todo - create black/white list of options to santitize?
	foreach ( $data['config'] as $key => $value ) {
		$data['config'][ $key ] = envira_santitize_config_setting( $value, $key );
	}

	// Add filter here for custom (or blocking) santitizing from addons.
	$data = apply_filters( 'envira_get_gallery_config', $data, $original_data );

	// Disable/Remove FullScreen if Fullscreen addon is not present.
	if ( ! class_exists( 'Envira_Fullscreen' ) ) {
		if ( isset( $data['config']['open_fullscreen'] ) ) {
			unset( $data['config']['open_fullscreen'] );
		}
	}

	// Disable/Remove Proofing if proofing addon is not present OR proofing isn't even activated.
	if ( ! class_exists( 'Envira_Proofing' ) || empty( $data['config']['proofing'] ) || 0 === $data['config']['proofing'] ) {
		foreach ( $data['config'] as $key => $value ) {
			if ( strtolower( substr( $key, 0, 8 ) ) === 'proofing' ) {
				unset( $data['config'][ $key ] );
			}
		}
		if ( isset( $data['config']['proofing_submitted_message'] ) ) {
			unset( $data['config']['proofing_submitted_message'] );
		}
		if ( isset( $data['config']['proofing_email_message'] ) ) {
			unset( $data['config']['proofing_email_message'] );
		}
		if ( isset( $data['config']['proofing_email_subject'] ) ) {
			unset( $data['config']['proofing_email_subject'] );
		}
	}

	// Disable/Remove FullScreen if CSS addon is not present.
	if ( ! function_exists( 'envira_custom_css_plugins_loaded' ) && isset( $data['config']['custom_css'] ) ) {
		unset( $data['config']['custom_css'] );
	}

	// Auto Thumbnail Size Check.
	$data = envira_maybe_set_thumbnail_size_auto( $data );

	return wp_json_encode( $data['config'] );

}

/**
 * General santitization of configuration settings
 *
 * @since 1.8.3
 *
 * @access public
 * @param string $value The value.
 * @param string $key The key.
 * @return array
 */
function envira_santitize_config_setting( $value, $key ) {

	/* at the moment we are only processing strings, either on their own or in arrays */

	if ( 'custom_css' === $key ) {
		/* Remove comments */
		$regex     = array(
			"`^([\t\s]+)`ism"                       => '',
			'`^\/\*(.+?)\*\/`ism'                   => '',
			"`([\n\A;]+)\/\*(.+?)\*\/`ism"          => '$1',
			"`([\n\A;\s]+)//(.+?)[\n\r]`ism"        => "$1\n",
			"`(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+`ism" => "\n",
		);
			$value = preg_replace( array_keys( $regex ), $regex, $value );
	}

	if ( gettype( $value ) === 'array' && ! empty( $value ) ) {
		foreach ( $value as $array_key => $array_value ) {
			if ( gettype( $array_value ) === 'string' ) {
				$value[ $array_key ] = htmlentities( $array_value, ENT_QUOTES );
			}
		}
		return $value;
	}

	if ( gettype( $value ) !== 'string' ) {
		return $value;
	}

	return htmlentities( $value, ENT_QUOTES );

}

/**
 * Main santitization function
 *
 * @since 1.8.3
 *
 * @access public
 * @param string $value String value.
 * @return array
 */
function envira_santitize_value( $value ) {

	return htmlentities( $value, ENT_QUOTES );

}

/**
 * Determine if lightbox width and height settings should be set to auto
 *
 * @since 1.8.3
 *
 * @access public
 * @param array $data Gallery data.
 * @return array
 */
function envira_maybe_set_thumbnail_size_auto( $data ) {

	if ( isset( $data['config']['thumbnails_custom_size'] ) && 0 === $data['config']['thumbnails_custom_size'] ) {
		$data['config']['thumbnails_width']  = 'auto';
		$data['config']['thumbnails_height'] = 'auto';
	}

	// if this value 'thumbnails_custom_size' isn't set/exists, then this is a gallery created/updated before 1.8.3 so the width/height values should be honored.
	return $data;

}

/**
 * Santitize Gallery Captions As They Are Requested
 *
 * @since 1.7.1
 *
 * @access public
 * @param  string $caption The caption.
 * @return string
 */
function envira_santitize_caption( $caption ) {

	if ( empty( $caption ) ) {
		return '';
	}

	// until we built a better santitizer, put this in place for resolving smart quotes in some scenarios when htmlentities doesn't
	// $caption = str_replace(array("'", "'", '"', '"'), array(chr(145), chr(146), chr(147), chr(148) ), $caption );.
	$encoding = ( mb_detect_encoding( $caption ) !== 'ASCII' ) ? mb_detect_encoding( $caption ) : 'utf-8';
	$caption  = function_exists( 'mb_detect_encoding' ) ? htmlentities( $caption, ENT_QUOTES, $encoding ) : htmlentities( $caption, ENT_QUOTES, $encoding );
	$caption  = str_replace( '&quot;', '"', $caption );
	$caption  = nl2br( $caption ); /* convert line breaks into <br/> tags */

	return $caption;

}

/**
 * Replacement for mb_detect_encoding if it doesn't exist
 *
 * @since 1.8.4.2
 *
 * @access public
 * @param string $string Incoming string.
 * @param string $enc Encoding.
 * @param string $ret Return.
 * @return result
 */
function envira_detect_encoding( $string, $enc = null, $ret = null ) {

	static $enclist = array(
		'UTF-8',
		'ASCII',
		'ISO-8859-1',
		'ISO-8859-2',
		'ISO-8859-3',
		'ISO-8859-4',
		'ISO-8859-5',
		'ISO-8859-6',
		'ISO-8859-7',
		'ISO-8859-8',
		'ISO-8859-9',
		'ISO-8859-10',
		'ISO-8859-13',
		'ISO-8859-14',
		'ISO-8859-15',
		'ISO-8859-16',
		'Windows-1251',
		'Windows-1252',
		'Windows-1254',
	);

	$result = false;

	foreach ( $enclist as $item ) {
		$sample = iconv( $item, $item, $string );
		if ( md5( $sample ) === md5( $string ) ) {
			if ( null === $ret ) {
				$result = $item;
			} else {
				$result = true; }
			break;
		}
	}

	return $result;
}

/**
 * Santitize Lightbox Captions As They Are Requested
 *
 * @since 1.7.1
 *
 * @access public
 * @param string $lightbox_caption Caption.
 * @return string
 */
function envira_santitize_lightbox_caption( $lightbox_caption ) {

	if ( empty( $lightbox_caption ) ) {
		return false;
	}

	$lightbox_caption = htmlentities( $lightbox_caption, ENT_COMPAT, 'UTF-8' );
	$lightbox_caption = str_replace( array( '&amp;lt;br /&amp;gt;', '&amp;lt;br &amp;gt;', '&amp;lt;br&amp;gt' ), '<br />', $lightbox_caption );
	$lightbox_caption = str_replace( array( '&amp;lt;strong&amp;gt;', '&amp;lt;/strong&amp;gt;' ), array( '<strong>', '</strong>' ), $lightbox_caption );
	$lightbox_caption = str_replace( array( '&amp;lt;em&amp;gt;', '&amp;lt;/em&amp;gt;' ), array( '<em>', '</em>' ), $lightbox_caption );

	return $lightbox_caption;

}


/**
 * Santitize Gallery Titles As They Are Requested
 *
 * @since 1.7.1
 *
 * @access public
 * @param array $meta_data Meta Data.
 * @return void
 */
function envira_santitize_metadata( $meta_data ) {

	if ( ! $meta_data ) {
		return;
	}

	if ( is_array( $meta_data ) && ! empty( $meta_data ) ) {
		foreach ( $meta_data as $key => $data ) {
			$meta_data[ $key ] = envira_santitize_title( $data );
		}
	} else {
		$meta_data = envira_santitize_title( $meta_data );
	}

	return $meta_data;

}


/**
 * Santitize Gallery Titles As They Are Requested
 *
 * @since 1.7.1
 *
 * @access public
 * @param string $title Gallery title.
 * @return string
 */
function envira_santitize_title( $title ) {

	if ( empty( $title ) || is_array( $title ) ) {
		return;
	}

	$encoding       = ( mb_detect_encoding( $title ) !== 'ASCII' ) ? mb_detect_encoding( $title ) : 'utf-8';
	$filtered_title = function_exists( 'mb_detect_encoding' ) ? htmlentities( $title, ENT_QUOTES, $encoding ) : htmlentities( $title, ENT_QUOTES, $encoding );
	$filtered_title = htmlentities( str_replace( array( '"' ), array( '&quot;' ), $title ), ENT_QUOTES );
	return $filtered_title;

}

/**
 * Santitize Gallery Fields As They Are Requested
 *
 * @since 1.7.1
 *
 * @access public
 * @param string $description Gallery description.
 * @return string
 */
function envira_santitize_description( $description ) {

	// until we built a better santitizer, put this in place for resolving smart quotes in some scenarios when htmlentities doesn't.
	$description = str_replace( array( "'", "'", '"', '"' ), array( chr( 145 ), chr( 146 ), chr( 147 ), chr( 148 ) ), $description );

	return htmlentities( $description, ENT_QUOTES );

}

/**
 * Returns All Gallery Images defaults to json object.
 *
 * @since 1.7.1
 *
 * @access public
 * @param mixed   $gallery_id Gallery id.
 * @param bool    $raw (default: false).
 * @param array   $data (default: null).
 * @param array   $return_sort_ids (default: false).
 * @param boolean $for_albums (default: false).
 * @param string  $gallery_type Type.
 * @param boolean $cache Enable cache.
 * @return string
 */
function envira_get_gallery_images( $gallery_id, $raw = false, $data = null, $return_sort_ids = false, $for_albums = false, $gallery_type = false, $cache = true ) {

	if ( ! empty( $data ) && isset( $data['config']['sort_order'] ) && '1' === $data['config']['sort_order'] ) {

		$data = envira_insure_random_gallery( $data, $gallery_id );

	} else {

		/* if this isn't random sorting, then let's use transient caching */

		$cache = ( ( ! defined( 'ENVIRA_DEBUG' ) || ! ENVIRA_DEBUG ) && true === $cache ) ? get_transient( '_eg_fragment_json_' . $gallery_id ) : false;

		if ( 'gutenberg' === $gallery_type ) {
			$data = envira_sort_gallery( $data, $data['config']['sort_order'], $data['config']['sorting_direction'] );
		}

		if ( $cache ) {

			if ( $raw ) {

				return json_decode( $cache['gallery_images'] );

			} else {

				if ( false === $return_sort_ids ) {

					return $cache['gallery_images'];

				} else {

					return $cache;

				}
			}
		}
	}

	if ( ! isset( $gallery_id ) ) {
		return false;
	}

	$images  = array();
	$sizes   = get_intermediate_image_sizes();
	$sizes[] = 'full';
	// make sure to get the album data because we need to check settings for title/caption override.
	$album_data = ( $for_albums && ! empty( $data ) ) ? $data : false;

	if ( ! empty( $data ) && 'dynamic' === $data['config']['type'] ) {

		$data = $data;

	} else {

		$data = envira_get_gallery( $gallery_id );

	}

	// Make sure it gets filtered.
	$data = apply_filters( 'envira_images_pre_data', $data, $gallery_id );

	$i        = 0;
	$id_array = array();

	if ( isset( $data['gallery'] ) && is_array( $data['gallery'] ) ) {

		foreach ( (array) $data['gallery'] as $id => $item ) {

			// If the item isn't an array, bail. GH 2779.
			if ( ! is_array( $item ) ) {
				continue;
			}

			// Skip over images that are pending (ignore if in Preview mode).
			if ( isset( $item['status'] ) && 'pending' === $item['status'] && ! is_preview() ) {
				continue;
			}

			if ( isset( $data['config']['type'] ) && 'instagram' !== $data['config']['type'] && 'fc' !== $data['config']['type'] ) {

				$image_size = envira_get_config( 'lightbox_image_size', $data );
				$image_data = wp_get_attachment_metadata( $id );
				$src        = wp_get_attachment_image_src( $id, $image_size );

				// check and see if this gallery as image_meta.
				if ( isset( $image_data['image_meta'] ) ) {
					// santitize image_meta.
					$image_data['image_meta']['caption'] = isset( $image_data['image_meta']['caption'] ) && ! empty( $image_data['image_meta']['caption'] ) && 'null' !== $image_data['image_meta']['caption'] ? envira_santitize_title( $image_data['image_meta']['caption'] ) : '';
					$image_data['image_meta']['title']   = isset( $image_data['image_meta']['title'] ) && ! empty( $image_data['image_meta']['title'] ) && 'null' !== $image_data['image_meta']['title'] ? envira_santitize_title( $image_data['image_meta']['title'] ) : '';
					if ( ! empty( $image_data['image_meta']['keywords'] ) ) {
						foreach ( $image_data['image_meta']['keywords'] as $index => $keyword ) {
							$image_data['image_meta']['keywords'][ $index ] = envira_santitize_title( $keyword );
						}
					}
					foreach ( $image_data['image_meta'] as $image_meta_id => $image_meta_data ) {
						if ( 'caption' === $image_meta_id || 'title' === $image_meta_id ) {
							continue;
						}
						$image_data['image_meta'][ $image_meta_id ] = envira_santitize_metadata( $image_data['image_meta'][ $image_meta_id ] );
					};

					$item['meta'] = $image_data['image_meta'];

				}

				$item['src'] = $src[0];

				foreach ( $sizes as $size ) {
					$size_url      = wp_get_attachment_image_src( $id, $size );
					$item[ $size ] = $size_url[0];
				}
			}
			$thumb_args = array(
				'position' => envira_get_config( 'crop_position', $data ),
				'width'    => false === envira_get_config( 'thumbnails_custom_size', $data ) ? envira_get_config_default( 'thumbnails_width' ) : envira_get_config( 'thumbnails_width', $data ),
				'height'   => false === envira_get_config( 'thumbnails_custom_size', $data ) ? envira_get_config_default( 'thumbnails_height' ) : envira_get_config( 'thumbnails_height', $data ),
				'quality'  => 100,
				'retina'   => false,
			);
			$src        = ( ! empty( $item['src'] ) ) ? ( $item['src'] ) : false;
			$thumb      = envira_resize_image( $src, $thumb_args['width'], $thumb_args['height'], true, envira_get_config( 'crop_position', $data ), $thumb_args['quality'], $thumb_args['retina'], $data );

			$item['title'] = ( ! empty( $item['title'] ) ) ? envira_santitize_title( $item['title'] ) : '';
			$item['index'] = $i;
			$item['id']    = $id;
			$item['thumb'] = $thumb;
			$item['video'] = isset( $item['video_in_gallery'] ) ? true : false;

			/* caption - set default then override if title was selected. */
			switch ( envira_get_config( 'lightbox_title_caption', $album_data ) ) {
				case 'caption':
					$item['caption'] = isset( $item['caption'] ) ? envira_santitize_caption( $item['caption'] ) : false;
					break;
				case 'title':
					$item['caption'] = isset( $item['title'] ) ? envira_santitize_caption( $item['title'] ) : false;
					break;
				default:
					$item['caption'] = false;
					break;
			}

			$item['opts']       = array(
				'caption' => envira_get_config( 'lightbox_title_caption', $data ) === 'title' ? $item['title'] : $item['caption'],
				'thumb'   => $thumb,
				'title'   => $item['title'],
			);
			$item['alt']        = ( ! empty( $item['alt'] ) ) ? envira_santitize_title( $item['alt'] ) : '';
			$item['gallery_id'] = $gallery_id;

			/* album specific info */
			if ( $for_albums ) {
				$item['gallery_title'] = envira_get_config( 'title', $data ) ? envira_santitize_title( envira_get_config( 'title', $data ) ) : envira_santitize_title( get_the_title( $gallery_id ) );
			}

			$item = apply_filters( 'envira_gallery_output_item_data', $item, $id, $data, $i );

			$images[ $id ] = $item;

			$id_array[] = $id;

			$i++;

		}
	}

	// this holds all data, which we will store in transient - so that we can pull out what we need from the cache (see above).
	$full_data = array(
		'gallery_images' => wp_json_encode( $images, JSON_UNESCAPED_UNICODE ),
		'sorted_ids'     => wp_json_encode( $id_array, JSON_UNESCAPED_UNICODE ),
	);

	// set the transient.
	$transient = set_transient( '_eg_fragment_json_' . $gallery_id, $full_data, WEEK_IN_SECONDS );

	if ( $raw ) {

		return $images;

	}

	if ( false === $return_sort_ids ) {

		return wp_json_encode( $images, JSON_UNESCAPED_UNICODE );

	} else {

		return ( $full_data );

	}

}

/**
 * Helper method for setting default config values.
 *
 * @since 1.7.0
 *
 * @global int $id      The current post ID.
 * @global object $post The current post object.
 * @param string $key   The default config key to retrieve.
 * @return string       Key value on success, false on failure.
 */
function envira_get_config_default( $key ) {

	global $id, $post;

	// Get the current post ID. If ajax, grab it from the $_POST variable.
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_POST['post_id'] ) ) { // @codingStandardsIgnoreLine !!! TODO - Move to ajax function
		$post_id = absint( $_POST['post_id'] ); // @codingStandardsIgnoreLine
	} else {
		$post_id = isset( $post->ID ) ? $post->ID : (int) $id;
	}

	// Prepare default values.
	$defaults = envira_get_config_defaults( $post_id );

	// Return the key specified.
	return isset( $defaults[ $key ] ) ? $defaults[ $key ] : false;

}

/**
 * Helper method for retrieving config values.
 *
 * @since 1.0.0
 *
 * @global int $id        The current post ID.
 * @global object $post   The current post object.
 * @param string $key       The config key to retrieve.
 * @param string $data       Gallery data.
 * @param string $default A default value to use.
 * @return mixed            Key value on success, empty string on failure.
 */
function envira_get_config( $key, $data, $default = null ) {

	if ( ! is_array( $data ) ) {

		return envira_get_config_default( $key );

	}

	$is_mobile_keys = array();

	// If we are on a mobile device, some config keys have mobile equivalents, which we need to check instead.
	if ( envira_mobile_detect()->isMobile() ) {
		$is_mobile_keys = array(
			'lightbox_enabled'     => 'mobile_lightbox',
			'gallery_link_enabled' => 'mobile_gallery_link_enabled',
			'arrows'               => 'mobile_arrows',
			'toolbar'              => 'mobile_toolbar',
			'thumbnails'           => 'mobile_thumbnails',
			'thumbnails_width'     => 'mobile_thumbnails_width',
			'thumbnails_height'    => 'mobile_thumbnails_height',
		);

		if ( isset( $data['config']['mobile'] ) && false !== $data['config']['mobile'] ) {
			$is_mobile_keys['crop_width']  = 'mobile_width';
			$is_mobile_keys['crop_height'] = 'mobile_height';

		}

		$is_mobile_keys = apply_filters( 'envira_gallery_get_config_mobile_keys', $is_mobile_keys );

		if ( array_key_exists( $key, $is_mobile_keys ) ) {
			// Use the mobile array key to get the config value.
			$key = $is_mobile_keys[ $key ];
		}
	} else { // if we are not on a mobile device, check for custom thumbnail sizes.

		// If the user hasn't overrided lightbox thumbnails with custom sizes, make sure these are set to auto.
		if ( ( 'thumbnails_height' === $key || 'thumbnails_width' === $key ) && ( ! isset( $data['config']['thumbnails_custom_size'] ) || false === $data['config']['thumbnails_custom_size'] ) ) {
			$value = 'auto';
		}
	}

	// The toolbar is not needed for base dark so lets disable it.
	if ( 'toolbar' === $key && 'base_dark' === $data['config']['lightbox_theme'] ) {
		$data['config'][ $key ] = 0;
	}

	// Disable/Remove FullScreen if Fullscreen addon is not present.
	if ( ! class_exists( 'Envira_Fullscreen' ) ) {
		if ( isset( $data['config']['open_fullscreen'] ) ) {
			unset( $data['config']['open_fullscreen'] );
		}
	}

	if ( isset( $data['config'] ) ) {
		$data['config'] = apply_filters( 'envira_gallery_get_config', $data['config'], $key );
	} else {
		$data['config'][ $key ] = false;
	}

	$default = null !== $default ? $default : envira_get_config_default( $key );
	$value   = isset( $data['config'][ $key ] ) ? $data['config'][ $key ] : $default;

	return $value;

}


/**
 * Envira Get Gallery Data function.
 *
 * @access public
 * @param mixed $gallery_id The gallery id.
 * @return $data
 */
function envira_get_gallery_data( $gallery_id ) {

	// If no ID is set create a new gallery.
	if ( ! isset( $gallery_id ) ) {

		return false;
	}

	$data = get_post_meta( $gallery_id, '_eg_gallery_data', true );

	return $data;

}

/**
 * Helper function to prepare the metadata for an image in a gallery.
 *
 * @since 1.7.0
 *
 * @param array $gallery_data   Array of data for the gallery.
 * @param int   $id             The attachment ID to prepare data for.
 * @param array $image          Attachment image. Populated if inserting from the Media Library.
 * @return array $gallery_data Amended gallery data with updated image metadata.
 */
function envira_prepare_gallery_data( $gallery_data, $id, $image = false ) {

	// Get attachment.
	$attachment = get_post( $id );

	// Add this image to the start or end of the gallery, depending on the setting.
	$media_position = envira_get_setting( 'media_position' );

	// Depending on whether we're inserting from the Media Library or not, prepare the image array.
	if ( ! $image ) {
		$url       = wp_get_attachment_image_src( $id, 'full' );
		$alt_text  = get_post_meta( $id, '_wp_attachment_image_alt', true );
		$new_image = array(
			'status'  => 'active',
			'src'     => isset( $url[0] ) ? esc_url( $url[0] ) : '',
			'title'   => get_the_title( $id ),
			'link'    => ( isset( $url[0] ) ? esc_url( $url[0] ) : '' ),
			'alt'     => ! empty( $alt_text ) ? $alt_text : '',
			'caption' => ! empty( $attachment->post_excerpt ) ? $attachment->post_excerpt : '',
			'thumb'   => '',
		);
	} else {
		$new_image = array(
			'status'  => 'active',
			'src'     => ( isset( $image['src'] ) ? $image['src'] : $image['url'] ),
			'title'   => $image['title'],
			'link'    => $image['link'],
			'alt'     => $image['alt'],
			'caption' => $image['caption'],
			'thumb'   => '',
		);
	}

	// Allow Addons to possibly add metadata now.
	$image = apply_filters( 'envira_gallery_ajax_prepare_gallery_data_item', $new_image, $image, $id, $gallery_data );

	// If gallery data is not an array (i.e. we have no images), just add the image to the array.
	if ( ! isset( $gallery_data['gallery'] ) || ! is_array( $gallery_data['gallery'] ) ) {
		$gallery_data['gallery']        = array();
		$gallery_data['gallery'][ $id ] = $image;
	} else {

		switch ( $media_position ) {
			case 'before':
				// Add image to start of images array
				// Store copy of images, reset gallery array and rebuild.
				$images                         = $gallery_data['gallery'];
				$gallery_data['gallery']        = array();
				$gallery_data['gallery'][ $id ] = $image;
				foreach ( $images as $old_image_id => $old_image ) {
					$gallery_data['gallery'][ $old_image_id ] = $old_image;
				}
				break;
			case 'after':
			default:
				// Add image, this will default to the end of the array.
				$gallery_data['gallery'][ $id ] = $image;
				break;
		}
	}

	// Filter and return.
	$gallery_data = apply_filters( 'envira_gallery_ajax_item_data', $gallery_data, $attachment, $id, $image );

	return $gallery_data;

}

add_filter( 'envira_gallery_pre_data', 'envira_insure_random_gallery', 10, 3 );


/**
 * Helper function to ensure random galleries bypass cache and are displayed randomly on the front end
 *
 * @since 1.7.0
 *
 * @param array   $data           Array of data for the gallery.
 * @param int     $gallery_id     The attachment ID to prepare data for.
 * @param boolean $flush_cache    Flush the transient or not.
 * @return array $data          Updated gallery data
 */
function envira_insure_random_gallery( $data, $gallery_id, $flush_cache = false ) {

	if ( ! $data || ! isset( $data['config']['sort_order'] ) || '1' !== $data['config']['sort_order'] ) {
		return $data;
	}

	// Store transient, but check and see if there's one already.
	$gallery_data = get_transient( '_eg_fragment_gallery_random_sort_' . $data['id'] );
	// Attempt to return the transient first, otherwise generate the new query to retrieve the data.
	if ( true === $flush_cache || false === $gallery_data ) {
		$expiration   = DAY_IN_SECONDS; // envira_get_transient_expiration_time could also be used here.
		$gallery_data = envira_sort_gallery( $data, '1', 'DESC' ); // '1' = random
		set_transient( '_eg_fragment_gallery_random_sort_' . $data['id'], $gallery_data, $expiration );
	}

	return $gallery_data;

}

add_filter( 'envira_gallery_get_transient_markup', 'envira_maybe_clear_cache_random', 10, 2 );

/**
 * Helper function to ensure random galleries bypass cache and are displayed randomly on the front end
 *
 * @since 1.7.0
 *
 * @param array $transient  Transient.
 * @param int   $data       Array of data for the gallery.
 * @return boolean          Allow cache or not.
 */
function envira_maybe_clear_cache_random( $transient, $data ) {
	if ( ! $data || ! isset( $data['config']['sort_order'] ) || 1 !== $data['config']['sort_order'] ) {
		return $transient;
	} else {
		return false;
	}
}

/**
 * Helper method to get the version the gallery was updated or created.
 *
 * @since 1.7.1
 *
 * @access public
 * @param mixed $gallery_id Gallery ID.
 * @return bool|intenger
 */
function envira_get_gallery_version( $gallery_id ) {

	if ( empty( $gallery_id ) ) {

		return false;

	}

	$version = get_post_meta( $gallery_id, '_eg_version', true );

	if ( ! empty( $version ) ) {

		return $version;

	}

	return false;

}

/**
 * Maybe update the gallery, check the version.
 *
 * @since 1.7.1
 *
 * @access public
 * @param mixed $gallery_id Gallery id.
 * @return boolean
 */
function envira_maybe_update_gallery( $gallery_id ) {

	$version = envira_get_gallery_version( $gallery_id );

	if ( ! isset( $version ) || version_compare( $version, '1.8.0', '<' ) ) {

		return true;
	}

	return false;

}

// Conditionally load the template tag.
if ( ! function_exists( 'envira_gallery' ) ) {

	/**
	 * Primary template tag for outputting Envira galleries in templates.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $id       The ID of the gallery to load.
	 * @param string $type      The type of field to query.
	 * @param array  $args          Associative array of args to be passed.
	 * @param bool   $return    Flag to echo or return the gallery HTML.
	 */
	function envira_gallery( $id, $type = 'id', $args = array(), $return = false ) {

		// If we have args, build them into a shortcode format.
		$args_string = ! empty( $args ) ? ' ' . str_replace( '=', '="', http_build_query( $args, null, '" ', PHP_QUERY_RFC3986 ) ) . '" ' : false;

		// Build the shortcode.
		$shortcode = ! empty( $args_string ) ? '[envira-gallery ' . $type . '="' . $id . '"' . $args_string . ']' : '[envira-gallery ' . $type . '="' . $id . '"]';

		// Return or echo the shortcode output.
		if ( $return ) {

			return do_shortcode( $shortcode );

		} else {

			echo do_shortcode( $shortcode );

		}

	}
}
