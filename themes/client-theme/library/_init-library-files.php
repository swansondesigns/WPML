<?php

/*
  Common Wordpress Needs
*/
// $library_files_to_include = array(
//   'advanced-custom-fields',
//   'change-youtube-embed-html',
//   'custom-post-types',
//   'dashboard-widgets',
//   'handy-functions',
//   'manage-images',
//   'manage-widgets',
//   'shortcode-feature',
//   'shortcode-posts',
//   'underscores-post-thumbnail',
//   'white-label',
//   'widget-areas',
// );

if ( is_array( $library_files_to_include ) ) {

	foreach ($library_files_to_include as $library_file) {

		include_once( __DIR__ . '/'. $library_file . '.php' );
		// require_once( __DIR__ . '/'. $library_file . '.php' );

	}

}
