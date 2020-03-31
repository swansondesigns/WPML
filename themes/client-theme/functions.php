<?php


function sd_theme_enqueuer() {

	$prefix = 'claro';

    $parent_style = 'underscores-style'; // This is 'twentyfifteen-style' for the Twenty Fifteen theme.

    wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style',
        get_stylesheet_directory_uri() . '/css/style.css',
        // get_stylesheet_directory_uri() . '/css/style.min.css',
        array( $parent_style ),
        wp_get_theme()->get('Version')
    );


    // Animate.css
    wp_enqueue_style( $prefix . '-animate', get_stylesheet_directory_uri() . '/css/animate.css'  );

    // Google Fonts
    wp_enqueue_style( $prefix . '-google-fonts', 'https://fonts.googleapis.com/css?family=Boogaloo|Open+Sans:400,700' );

    // Font Awesome
    // We either do this one...
    wp_enqueue_style( $prefix . '-fontawesome-main', get_stylesheet_directory_uri() . '/fonts/fontawesome/css/all.min.css' );

    // ...or a selection of these.  Need the first one and then others as required.
    // wp_enqueue_style( $prefix . '-fontawesome-main',    get_stylesheet_directory_uri() . '/fonts/css/fontawesome.min.css' );
    // wp_enqueue_style( $prefix . '-fontawesome-brands',  get_stylesheet_directory_uri() . '/fonts/css/brands.min.css' );
    // wp_enqueue_style( $prefix . '-fontawesome-light',   get_stylesheet_directory_uri() . '/fonts/css/light.min.css' );
    // wp_enqueue_style( $prefix . '-fontawesome-regular', get_stylesheet_directory_uri() . '/fonts/css/regular.min.css' );
    // wp_enqueue_style( $prefix . '-fontawesome-solid',   get_stylesheet_directory_uri() . '/fonts/css/solid.min.css' );



}
add_action( 'wp_enqueue_scripts', 'sd_theme_enqueuer' );

// Update CSS within in Admin
function admin_style() {

  	wp_enqueue_style('style-admin', get_stylesheet_directory_uri() . '/css/style.admin.css');

}
add_action('admin_enqueue_scripts', 'admin_style');

/*
  Common Wordpress Needs
*/
function sd_add_library_includes() {

	$library_files_to_include = [
		'dashboard-widgets',
		'white-label',
	];

	foreach ($library_files_to_include as $library_file) {

		include_once( __DIR__ . '\/library/' . $library_file . '.php' );

	}
}
add_action( 'init', 'sd_add_library_includes' );
