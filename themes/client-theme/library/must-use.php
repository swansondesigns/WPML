<?php

function sd_remove_block_css(){
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-css' );
}
add_action( 'wp_enqueue_scripts', 'sd_remove_block_css', 100 );
