<?php
function sd_register_shortcode_posts( $atts ) {

	$atts = shortcode_atts( array(
		'category' => null,
		'maxposts' => 3,
	), $atts );

	$args = array(
		'post_type'   => 'post',
		'posts_per_page'   => $atts['maxposts'],
		'category_name' => $atts['category'],
	);


	// if ( empty($atts['category_name'] ) )
	// 	$args['category_name'] = $atts['category_name'];

	// This is required to use standard WP functions without passing the post ID.
	global $post;

	if ( $posts = get_posts( $args ) ) {

		ob_start();
		echo '<section class="posts-section">';


		foreach($posts as $post) : setup_postdata($post);

			echo '<div class="single-post">';

				$href = get_permalink();

				the_post_thumbnail( 'rectangle-small' );

				the_title( '<h3><a href="' . $href . '">', '</a></h3>' );

				echo sd_get_page_excerpt( get_the_ID(), '40' );

				echo '<a href=' . $href . ' class="button">Learn More</a>';

			echo '</div>';

		endforeach;
		wp_reset_postdata();

		echo '</section>';

		$output_buffer = ob_get_contents();
		ob_end_clean();

		return $output_buffer;

	} else {
		echo wpautop('Did not get posts');
	}


}
add_shortcode( 'posts' , 'sd_register_shortcode_posts' );
