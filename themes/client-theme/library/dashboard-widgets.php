<?php

/*
	Disable Default Dashboard Widgets
	@ https://digwp.com/2014/02/disable-default-dashboard-widgets/

	- On the dashboard, each widget is in a div with a class of "postbox-container"
	- Inside that container, there is a div with an ID of "*-sortables" where * is either "normal" (left column) or "side" (right of that)
	- The "normal" or "left" in this ID must match the array below.
	- Inside the "*-sortables" div are the divs for each widget.  Each has an ID.  This ID is the last value in the array.

	NOTE: All widgets are either "normal" or "side".  The other columns are for user placed widgets only.

*/
function sd_disable_default_dashboard_widgets() {
	global $wp_meta_boxes;
	// wp..
	// Activity
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity']);

	// At a Glance
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);

	// Wordpress Events and News
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);

	// Quick Draft
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);

	// Yoast
	// unset($wp_meta_boxes['dashboard']['normal']['core']['wpseo-dashboard-overview']);

	// Wordfence
	// unset($wp_meta_boxes['dashboard']['normal']['core']['wordfence_activity_report_widget']);

	// unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);
	// unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links']);
	// unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);

	// unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);
	// unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_recent_drafts']);
	// // bbpress
	// unset($wp_meta_boxes['dashboard']['normal']['core']['bbp-dashboard-right-now']);
	// // gravity forms
	// unset($wp_meta_boxes['dashboard']['normal']['core']['rg_forms_dashboard']);
}
add_action('wp_dashboard_setup', 'sd_disable_default_dashboard_widgets', 999);



function my_custom_dashboard_widgets() {
	global $wp_meta_boxes;
	wp_add_dashboard_widget('sd_quick_links', 'Quick Links', 'sd_quick_links_content');
}
// add_action('wp_dashboard_setup', 'my_custom_dashboard_widgets');

function sd_quick_links_content() {
	$quick_links = [
		// array (
		// 	'name' => 'Label',
		// 	'link' => 'page-id'
		// ),
	];

	if ( is_array( $quick_links ) && !empty( $quick_links ) ) {

		echo '<ul id="sd-quick-links">';
		foreach ($quick_links as $quick_link) {
			echo '<li><a href="/wp-admin/admin.php?page=' . $quick_link['link'] . '">' . $quick_link['name'] . '</a></li>';
		}
		echo '</ul>';
		echo wpautop('These links can only be accessed from the dashboard as a way to keep the left menu neat and clean.');
		echo '<style>
		ul#sd-quick-links a {
			padding: 20px;
			display: block;
			border: 1px solid;
			font-size: 20px;
		}
		</style>';

	}
}
