<?php
/**
 * Envira Image Functions.
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
 * Is this an image function.
 *
 * @access public
 * @param mixed $url // a given url.
 * @return boolean
 */
function envira_is_image( $url ) {

	$parse     = wp_parse_url( $url );
	$filetypes = envira_get_supported_filetypes();

	// bail if its not an array.
	if ( ! is_array( $parse ) ) {

		return false;

	}

	if ( ! isset( $parse['path'] ) ) {
		return false;
	}

	$extension = pathinfo( $parse['path'], PATHINFO_EXTENSION );

	$img_extensions = explode( ',', $filetypes[0]['extensions'] );

	if ( in_array( strtolower( $extension ), $img_extensions, true ) ) {

		return true;

	}

	return false;
}

/**
 * Get_image_sizes function.
 *
 * @access public
 * @param boolean $core_only Only bother with WordPress sizes.
 * @return array
 */
function envira_get_image_sizes( $core_only = false ) {

	if ( ! $core_only ) {
		$sizes = array(
			array(
				'value' => 'default',
				'name'  => __( 'Default', 'envira-gallery' ),
			),
		);
	}

	global $_wp_additional_image_sizes;
	$wp_sizes = get_intermediate_image_sizes();
	foreach ( (array) $wp_sizes as $size ) {
		if ( isset( $_wp_additional_image_sizes[ $size ] ) ) {
			$width  = absint( $_wp_additional_image_sizes[ $size ]['width'] );
			$height = absint( $_wp_additional_image_sizes[ $size ]['height'] );
		} else {
			$width  = absint( get_option( $size . '_size_w' ) );
			$height = absint( get_option( $size . '_size_h' ) );
		}

		if ( ! $width && ! $height ) {
			$sizes[] = array(
				'value' => $size,
				'name'  => ucwords( str_replace( array( '-', '_' ), ' ', $size ) ),
			);
		} else {
			$sizes[] = array(
				'value'  => $size,
				'name'   => ucwords( str_replace( array( '-', '_' ), ' ', $size ) ) . ' (' . $width . ' x ' . $height . ')',
				'width'  => $width,
				'height' => $height,
			);
		}
	}
	// Add Option for full image.
	$sizes[] = array(
		'value' => 'full',
		'name'  => __( 'Original Image', 'envira-gallery' ),
	);

	return apply_filters( 'envira_gallery_image_sizes', $sizes );
}

/**
 * Get shortcode image sizes
 *
 * @access public
 * @return array
 */
function envira_get_shortcode_image_sizes() {
	global $_wp_additional_image_sizes;
	$sizes = array();

	foreach ( get_intermediate_image_sizes() as $_size ) {
		if ( in_array( $_size, array( 'thumbnail', 'medium', 'medium_large', 'large' ), true ) ) {
			if ( (bool) get_option( "{$_size}_crop" ) === true ) {
				continue;
			}
			$sizes[ $_size ]['name']   = $_size;
			$sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
			$sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
			$sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
		} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
			if ( true === $_wp_additional_image_sizes[ $_size ]['crop'] ) {
				continue;
			}
			$sizes[ $_size ] = array(
				'name'   => $_size,
				'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
				'height' => $_wp_additional_image_sizes[ $_size ]['height'],
				'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
			);
		}
	}

	return $sizes;
}

/**
 * Helper method to return common information about an image.
 *
 * @since 1.7.0
 *
 * @param array $args // List of resizing args to expand for gathering info.
 * @return WP_Error|string Return WP_Error on error, array of data on success.
 */
function envira_get_image_info( $args ) {

	// Unpack arguments.
	list( $url, $width, $height, $crop, $align, $quality, $retina, $data ) = $args;

	// Return an error if no URL is present.
	if ( empty( $url ) ) {
		return new WP_Error( 'envira-gallery-error-no-url', __( 'No image URL specified for cropping.', 'envira-gallery' ) );
	}

	// Get the image file path.
	$urlinfo       = wp_parse_url( $url );
	$wp_upload_dir = wp_upload_dir();

	// Interpret the file path of the image.
	if ( preg_match( '/\/[0-9]{4}\/[0-9]{2}\/.+$/', $urlinfo['path'], $matches ) ) {

		$file_path = $wp_upload_dir['basedir'] . $matches[0];

	} else {

		$content_dir = defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : '/wp-content/';
		$pathinfo    = wp_parse_url( $url );
		$uploads_dir = is_multisite() ? '/files/' : $content_dir;
		$file_path   = trailingslashit( $wp_upload_dir['basedir'] ) . basename( $urlinfo['path'] );
		$file_path   = preg_replace( '/(\/\/)/', '/', $file_path );

	}

	// Attempt to stream and import the image if it does not exist based on URL provided.
	if ( ! file_exists( $file_path ) ) {
		return new WP_Error( 'envira-gallery-error-no-file', __( 'No file could be found for the image URL specified.', 'envira-gallery' ) );
	}

	// Get original image size.
	$size = getimagesize( $file_path );

	// If no size data obtained, return an error.
	if ( ! is_array( $size ) ) {

		return new WP_Error( 'envira-gallery-error-no-size', __( 'The dimensions of the original image could not be retrieved for cropping.', 'envira-gallery' ) );

	}

	// Set original width and height.
	list( $orig_width, $orig_height, $orig_type ) = $size;

	// Generate width or height if not provided.
	if ( $width && ! $height ) {
		$height = floor( $orig_height * ( $width / $orig_width ) );
	} elseif ( $height && ! $width ) {
		$width = floor( $orig_width * ( $height / $orig_height ) );
	} elseif ( ! $width && ! $height ) {
		return new WP_Error( 'envira-gallery-error-no-size', __( 'The dimensions of the original image could not be retrieved for cropping.', 'envira-gallery' ) );
	}

	// Allow for different retina image sizes.
	$retina = $retina ? 2 : 1;

	// Destination width and height variables.
	$dest_width  = $width * $retina;
	$dest_height = $height * $retina;

	// Some additional info about the image.
	$info = pathinfo( $file_path );
	$dir  = $info['dirname'];
	$ext  = $info['extension'];
	$name = wp_basename( $file_path, ".$ext" );

	// Suffix applied to filename.
	$suffix = "${dest_width}x${dest_height}";

	// Set alignment information on the file.
	if ( $crop ) {
		$suffix .= ( $align ) ? "_${align}" : '_c';
	}

	// Get the destination file name.
	$dest_file_name = "${dir}/${name}-${suffix}.${ext}";

	// Return the info.
	$info = array(
		'dir'            => $dir,
		'name'           => $name,
		'ext'            => $ext,
		'suffix'         => $suffix,
		'orig_width'     => $orig_width,
		'orig_height'    => $orig_height,
		'orig_type'      => $orig_type,
		'dest_width'     => $dest_width,
		'dest_height'    => $dest_height,
		'file_path'      => $file_path,
		'dest_file_name' => $dest_file_name,
	);

	return $info;

}

/**
 * Helper method to get image width
 *
 * @since 1.7.0
 *
 * @param mixed $id // image ID.
 * @param mixed $item // image item data.
 * @param mixed $data // gallery data.
 * @param mixed $i // counter.
 * @param mixed $output_src // output src.
 * @return WP_Error|string Return WP_Error on error, array of data on success.
 */
function envira_get_image_width( $id, $item, $data, $i, $output_src ) {

	if ( envira_get_config( 'crop', $data ) && envira_get_config( 'crop_width', $data ) && envira_get_config( 'image_size', $data ) !== 'full' ) {

		$output_width = envira_get_config( 'crop_width', $data );

	} elseif ( envira_get_config( 'columns', $data ) !== 0 && envira_get_config( 'image_size', $data ) && envira_get_config( 'image_size', $data ) !== 'full' && envira_get_config( 'crop_width', $data ) && envira_get_config( 'crop_height', $data ) ) {

		$output_width = envira_get_config( 'crop_width', $data );

	} elseif ( isset( $data['config']['type'] ) && 'instagram' === $data['config']['type'] && strpos( $imagesrc, 'cdninstagram' ) !== false ) {

		// if this is an instagram image, @getimagesize might not work.
		// therefore we should try to extract the size from the url itself.
		if ( strpos( $imagesrc, '150x150' ) ) { // @codingStandardsIgnoreLine - not defined? why?

			$output_width = '150';

		} elseif ( strpos( $imagesrc, '640x640' ) ) { // @codingStandardsIgnoreLine - not defined? why?

			$output_width = '640';

		} else {

			$output_width = '150';

		}
	} elseif ( ! empty( $item['width'] ) ) {

		$output_width = $item['width'];

	} elseif ( ! empty( $placeholder[1] ) ) { // @codingStandardsIgnoreLine - not defined? why?

		$output_width = $placeholder[1]; // @codingStandardsIgnoreLine - not defined? why?

	} else {

		$output_width = false;

	}
	return apply_filters( 'envira_gallery_output_width', $output_width, $id, $item, $data, $i, $output_src );

}

/**
 * Helper method to get image height
 *
 * @since 1.7.0
 *
 * @param mixed $id // image ID.
 * @param mixed $item // image item data.
 * @param mixed $data // gallery data.
 * @param mixed $i // counter.
 * @param mixed $output_src // output src.
 * @return WP_Error|string Return WP_Error on error, array of data on success.
 */
function envira_get_item_height( $id, $item, $data, $i, $output_src ) {

	if ( envira_get_config( 'crop', $data ) && envira_get_config( 'crop_width', $data ) && envira_get_config( 'image_size', $data ) !== 'full' ) {

		$output_height = envira_get_config( 'crop_height', $data );

	} elseif ( envira_get_config( 'columns', $data ) !== 0 && envira_get_config( 'image_size', $data ) && envira_get_config( 'image_size', $data ) !== 'full' && envira_get_config( 'crop_width', $data ) && envira_get_config( 'crop_height', $data ) ) {

		$output_height = envira_get_config( 'crop_height', $data );

	} elseif ( isset( $data['config']['type'] ) && 'instagram' === $data['config']['type'] && strpos( $imagesrc, 'cdninstagram' ) !== false ) {

		// if this is an instagram image, @getimagesize might not work
		// therefore we should try to extract the size from the url itself.
		if ( strpos( $imagesrc, '150x150' ) ) { // @codingStandardsIgnoreLine - not defined? why?

			$output_height = '150';

		} elseif ( strpos( $imagesrc, '640x640' ) ) { // @codingStandardsIgnoreLine - not defined? why?

			$output_height = '640';

		} else {
			$output_height = '150';

		}
	} elseif ( ! empty( $item['height'] ) ) {

		$output_height = $item['height'];

	} elseif ( ! empty( $placeholder[2] ) ) { // @codingStandardsIgnoreLine - not defined? why?

		$output_height = $placeholder[2]; // @codingStandardsIgnoreLine - not defined? why?

	} else {

		$output_height = false;

	}

	return apply_filters( 'envira_gallery_output_height', $output_height, $id, $item, $data, $i, $output_src );

}

/**
 * Find Clostest Size function.
 *
 * @access public
 * @param mixed $data Gallery Data.
 * @return bool|intenger
 */
function envira_find_clostest_size( $data ) {
	$image_sizes = envira_get_shortcode_image_sizes();
	$dimensions  = envira_get_config( 'dimensions', $data );
	$width       = envira_get_config( 'crop_width', $data );
	$height      = envira_get_config( 'crop_height', $data );
	$match       = false;
	usort( $image_sizes, 'envira_usort_callback' );
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
 * Fall back for Usort Callback
 *
 * @param array $a $data to sort.
 * @param array $b $data to sort.
 * @return intenger
 */
function envira_usort_callback( $a, $b ) {

	return intval( $a['width'] ) - intval( $b['width'] );

}

/**
 * Helper Method to get image src.
 *
 * @param intenger $id Image ID.
 * @param array    $item Item Data.
 * @param array    $data Gallery Data.
 * @param boolean  $mobile Is Mobile.
 * @param boolean  $retina Is Retina.
 * @return bool|string
 */
function envira_get_image_src( $id, $item, $data, $mobile = false, $retina = false ) {

	// Define variable.
	$src = false;

	$mobile = envira_is_mobile();

	// Check for mobile and mobile setting.
	$type = $mobile && envira_get_config( 'mobile', $data ) ? 'mobile' : 'crop'; // 'crop' is misleading here - it's the key that stores the thumbnail width + height.
	// If this image is an instagram, we grab the src and don't use the $id.
	// otherwise using the $id refers to a postID in the database and has been known.
	// at times to pull up the wrong thumbnail instead of the instagram one.
	$instagram = false;
	if ( ! empty( $item['src'] ) && strpos( $item['src'], 'cdninstagram' ) !== false ) :
		// using 'cdninstagram' because it seems all urls contain it - but be watchful in the future.
		$instagram = true;
		$src       = $item['src'];
		$image     = $item['src'];
	endif;
	$image_size = envira_get_config( 'image_size', $data );
	if ( ! $src && is_int( $id ) ) : // wp_get_attachment_image_src only accepts $id as integer.
		if ( ( envira_get_config( 'crop', $data ) && 'default' === $image_size ) || 'full' === $image_size ) {
			$src = apply_filters( 'envira_gallery_retina_image_src', wp_get_attachment_image_src( $id, 'full' ), $id, $item, $data, $mobile );
		} elseif ( 'full' !== $image_size && ! $retina ) {
			// Check if this Gallery uses a WordPress defined image size.
			if ( 'default' !== $image_size ) {
				// If image size is envira_gallery_random, get a random image size.
				if ( 'envira_gallery_random' === $image_size ) {
					// Get random image sizes that have been chosen for this Gallery.
					$image_sizes_random = (array) envira_get_config( 'image_sizes_random', $data );
					if ( count( $image_sizes_random ) === 0 ) {
						// The user didn't choose any image sizes - use them all.
						$core_image_sizes           = envira_get_image_sizes( true );
						$core_image_size_random_key = array_rand( $core_image_sizes, 1 );
						$image_size                 = $core_image_sizes[ $core_image_size_random_key ]['value'];
					} else {
						$core_image_size_random_key = array_rand( $image_sizes_random, 1 );
						$image_size                 = $image_sizes_random[ $core_image_size_random_key ];
					}
					// Get the random WordPress defined image size.
					$src = wp_get_attachment_image_src( $id, $image_size );
				} else {
					// Get the requested WordPress defined image size.
					$src = wp_get_attachment_image_src( $id, $image_size );
				}
			} else {
				$isize = envira_find_clostest_size( $data ) !== '' ? envira_find_clostest_size( $data ) : 'full';
				$src   = apply_filters( 'envira_gallery_default_image_src', wp_get_attachment_image_src( $id, $isize ), $id, $item, $data, $mobile );
			}
		} else {
			$src = apply_filters( 'envira_gallery_retina_image_src', wp_get_attachment_image_src( $id, 'full' ), $id, $item, $data, $mobile );
		}
	endif;
	// Check if this returned an image.
	if ( ! $src ) {
		// Fallback to the $item's image source.
		$image = isset( $item['src'] ) ? $item['src'] : false;
	} elseif ( ! $instagram ) {
		$image = $src[0];
	}
	// If we still don't have an image at this point, something went wrong.
	if ( ! isset( $image ) ) {
		$item_link = isset( $item['link'] ) ? $item['link'] : false;
		return apply_filters( 'envira_gallery_no_image_src', $item_link, $id, $item, $data );
	}
	// If the current layout is justified/automatic
	// if the image size is a WordPress size and we're not requesting a retina image we don't need to resize or crop anything.
	if ( 'default' !== $image_size && ! $retina && 'mobile' !== $type ) {
		// Return the image.
		return apply_filters( 'envira_gallery_image_src', $image, $id, $item, $data );
	}
	$crop = envira_get_config( 'crop', $data );
	if ( $crop || 'mobile' === $type ) {
		// If the image size is default (i.e. the user has input their own custom dimensions in the Gallery),
		// we may need to resize the image now
		// This is safe to call every time, as resize_image() will check if the image already exists, preventing thumbnails
		// from being generated every single time.
		$args = array(
			'position' => envira_get_config( 'crop_position', $data ),
			'width'    => envira_get_config( $type . '_width', $data ),
			'height'   => envira_get_config( $type . '_height', $data ),
			'quality'  => 100,
			'retina'   => $retina,
		);
		// If we're requesting a retina image, and the gallery uses a registered WordPress image size,
		// we need use the width and height of that registered WordPress image size - not the gallery's
		// image width and height, which are hidden settings.
		// if this is mobile, go with the mobile image settings, otherwise proceed?
		if ( 'default' !== $image_size && $retina && 'mobile' !== $type ) {
			// Find WordPress registered image size.
			$core_image_sizes = envira_get_image_sizes( true ); // true = WordPress only image sizes (excludes random).
			foreach ( $core_image_sizes as $size ) {
				if ( $size['value'] !== $image_size ) {
					continue;
				}
				// We found the image size. Use its dimensions.
				if ( ! empty( $size['width'] ) ) {
					$args['width'] = $size['width'];
				}
				if ( ! empty( $size['height'] ) ) {
					$args['height'] = $size['height'];
				}
				break;
			}
		}
		// Filter.
		$args = apply_filters( 'envira_gallery_crop_image_args', $args );
		// Make sure we're grabbing the full image to crop.
		$image_to_crop = apply_filters( 'envira_gallery_crop_image_src', wp_get_attachment_image_src( $id, 'full' ), $id, $item, $data, $mobile );
		// Check if this returned an image.
		if ( ! $image_to_crop ) {
			// Fallback to the $item's image source.
			$image_to_crop = ! empty( $item['src'] ) ? $item['src'] : false;
		} elseif ( ! $instagram ) {
			$image_to_crop = ! empty( $src[0] ) ? $src[0] : false;
		}
		$resized_image = envira_resize_image( $image_to_crop, $args['width'], $args['height'], true, envira_get_config( 'crop_position', $data ), $args['quality'], $args['retina'], $data );
		// If there is an error, possibly output error message and return the default image src.
		if ( ! is_wp_error( $resized_image ) ) {
			return apply_filters( 'envira_gallery_image_src', $resized_image, $id, $item, $data );
		}
	}
	// return full image.
	return apply_filters( 'envira_gallery_image_src', $image, $id, $item, $data );

}
