<?php

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function sd_widgets_init() {

	register_sidebar( array(
        'name'          => esc_html__( 'Page sidebar', 'sd' ),
        'id'            => 'sidebar-blog',
        'description'   => esc_html__( 'Widgets in this sidebar will appear on all blog pages.', 'sd' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );

	register_sidebar( array(
        'name'          => esc_html__( 'Blog sidebar', 'sd' ),
        'id'            => 'sidebar-blog',
        'description'   => esc_html__( 'Widgets in this sidebar will appear on all blog pages.', 'sd' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );

	register_sidebar( array(
		'name' => esc_html__( 'Footer widget area' ),
		'id' => 'footer-widget-area',
		'description' => esc_html__( 'Widgets from this area will appear in footer.' ),
		'before_widget' => '<div id="%1$s" class="footer-widget widget %2$s">',
		'after_widget' => '</div>',
		'before_title' => '<h3 class="title"><span>',
		'after_title' => '</span></h3>',
	) );


}
add_action( 'widgets_init', 'sd_widgets_init' );
