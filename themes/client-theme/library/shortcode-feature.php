<?php
function sd_register_shortcode_feature( $atts ) {

	$atts = shortcode_atts( array(
		'slug' => null
	), $atts );


	$args = array(
	  'name'        => 'golf',
	  'post_type'   => 'features',
	  'numberposts' => 1
	);

	if ( $posts = get_posts( $args ) ) {

		foreach ($posts as $post) {
			// code...
			// Your loop code
			// $selected_post = get_sub_field('feature_repeater');
			// $post_id = $posts->ID;
			/*
			echo '<pre>';
			var_dump($post);
			echo '</pre>';
			//*/

			// include( get_stylesheet_directory_uri() . '/flexible-content-layouts/features/promo-box.php' );

			set_query_var( 'feature_id', $post->ID );
			get_template_part( 'flexible-content/features/feature', 'promo-box'  );
		}



	} else {
		echo 'No posts';
	}


}
add_shortcode( 'feature' , 'sd_register_shortcode_feature' );
