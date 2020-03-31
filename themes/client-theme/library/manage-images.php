<?php

// Remove default image sizes.
function sd_remove_default_image_sizes( $sizes ) {
    // unset( $sizes['thumbnail'] );
    unset( $sizes['medium'] );
    unset( $sizes['medium_large'] );
    unset( $sizes['large'] );

    return $sizes;
}
add_filter('intermediate_image_sizes_advanced', 'sd_remove_default_image_sizes');

// Add new image sizes here
function sd_add_image_sizes() {

	/*
		To make this easy we'll store all images in one array and loop through it.

		Excerpt from link below.
		=====================================================================================

		Cropping behavior for the image size is dependent on the value of $crop:

			1. If false (default), images will be scaled, not cropped.
			2. If an array in the form of array( x_crop_position, y_crop_position ):
				- x_crop_position accepts ‘left’ ‘center’, or ‘right’.
				- y_crop_position accepts ‘top’, ‘center’, or ‘bottom’. Images will be cropped to the specified dimensions within the defined crop area.
			3. If true, images will be cropped to the specified dimensions using center positions.

		=====================================================================================

		@see https://developer.wordpress.org/reference/functions/add_image_size/

	*/
	$image_sizes = array(
		array(
			'name' => 'rectangle-medium',
    		'width' => 325,
    		'height' => 225,
    		'crop' => array( 'center', 'center' ),
		),
		array(
			'name' => 'rectangle-small',
			'width' => 200,
			'height' => 120,
			'crop' => false,
		),
	);

	foreach ($image_sizes as $image_size) {
		add_image_size( $image_size['name'], $image_size['width'], $image_size['height'] , $image_size['crop'] );
	}

}
// add_filter( 'after_setup_theme', 'sd_add_image_sizes' );
