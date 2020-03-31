<?php

function sd_register_my_cpts_carousel() {

	/**
	 * Post Type: Carousels.
	 */

	$labels = array(
		"name" => __( "Carousels", "sd" ),
		"singular_name" => __( "Carousel", "sd" ),
		"menu_name" => __( "Carousels", "sd" ),
		"all_items" => __( "All Carousels", "sd" ),
		"add_new" => __( "Add Carousel", "sd" ),
		"add_new_item" => __( "Add New Carousel", "sd" ),
		"edit_item" => __( "Edit Carousel", "sd" ),
		"new_item" => __( "New Carousel", "sd" ),
		"view_item" => __( "View Carousel", "sd" ),
		"view_items" => __( "View Carousels", "sd" ),
		"search_items" => __( "Search Carousel", "sd" ),
		"not_found" => __( "No Carousels Found", "sd" ),
		"not_found_in_trash" => __( "No Carousels Found in Trash", "sd" ),
		"parent_item_colon" => __( "Parent Carousel", "sd" ),
		"featured_image" => __( "Featured Image for This Carousel", "sd" ),
		"set_featured_image" => __( "Set featured image for this Carousel", "sd" ),
		"remove_featured_image" => __( "Remove featured image for this Carousel", "sd" ),
		"use_featured_image" => __( "Use as Featured Image for this Carousel", "sd" ),
		"archives" => __( "Carousel Archives", "sd" ),
		"insert_into_item" => __( "Insert into Carousel", "sd" ),
		"uploaded_to_this_item" => __( "Uploaded to this Carousel", "sd" ),
		"filter_items_list" => __( "Filter Carousels List", "sd" ),
		"items_list_navigation" => __( "Carousels list navigation", "sd" ),
		"items_list" => __( "Carousels list", "sd" ),
		"attributes" => __( "Carousels Attributes", "sd" ),
		"parent_item_colon" => __( "Parent Carousel", "sd" ),
	);

	$args = array(
		"label" => __( "Carousels", "sd" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"delete_with_user" => false,
		"show_in_rest" => false,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		"has_archive" => false,
		"show_in_menu" => true,
		"show_in_nav_menus" => false,
		"exclude_from_search" => true,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"rewrite" => array( "slug" => "carousel", "with_front" => true ),
		"query_var" => true,
		"menu_position" => 30,
		"menu_icon" => "dashicons-controls-repeat",
		"supports" => array( "title" ),
	);

	register_post_type( "carousel", $args );
}

// add_action( 'init', 'sd_register_my_cpts_carousel' );
