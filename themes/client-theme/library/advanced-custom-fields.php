<?php

if( function_exists('acf_add_local_field_group') ):

	acf_add_local_field_group(array(
		'key' => 'group_5b4e2fa1361ff',
		'title' => 'In Page Navigation Override',
		'fields' => array(
			array(
				'key' => 'field_5b4e2fa9e2779',
				'label' => 'Override IPN?',
				'name' => 'ipn_override_toggle',
				'type' => 'true_false',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'message' => '',
				'default_value' => 0,
				'ui' => 1,
				'ui_on_text' => '',
				'ui_off_text' => '',
			),
			array(
				'key' => 'field_5b4e2fc0e277a',
				'label' => 'URL',
				'name' => 'ipn_override_url',
				'type' => 'url',
				'instructions' => 'Include the "http://" or "https://" portion of the URL.',
				'required' => 0,
				'conditional_logic' => array(
					array(
						array(
							'field' => 'field_5b4e2fa9e2779',
							'operator' => '==',
							'value' => '1',
						),
					),
				),
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'default_value' => '',
				'placeholder' => '',
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'page',
				),
				array(
					'param' => 'page_template',
					'operator' => '!=',
					'value' => 'template-front-page.php',
				),
			),
		),
		'menu_order' => 0,
		'position' => 'side',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => '',
		'active' => 1,
		'description' => '',
	));

	acf_add_local_field_group(array(
		'key' => 'group_5b1fef5256c1a',
		'title' => 'In-Page Navigation',
		'fields' => array(
			array(
				'key' => 'field_5b1fefb9ae196',
				'label' => 'Instructions',
				'name' => '',
				'type' => 'message',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'message' => 'To control which pages appear in the "In-Page Navigation" choose the pages/posts that you would like to display using the selector below.

	To display the navigation, place this in the editor above:

	[in_page_navigation]

	Include the brackets.',
				'new_lines' => 'wpautop',
				'esc_html' => 0,
			),
			array(
				'key' => 'field_5b1fef67ae194',
				'label' => 'Pages',
				'name' => 'pages',
				'type' => 'repeater',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'collapsed' => 'field_5b1fef75ae195',
				'min' => 0,
				'max' => 0,
				'layout' => 'table',
				'button_label' => 'Add Page',
				'sub_fields' => array(
					array(
						'key' => 'field_5b1fef75ae195',
						'label' => 'Page',
						'name' => 'page',
						'type' => 'post_object',
						'instructions' => '',
						'required' => 1,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'post_type' => array(
							0 => 'page',
							1 => 'post',
						),
						'taxonomy' => array(
						),
						'allow_null' => 0,
						'multiple' => 0,
						'return_format' => 'object',
						'ui' => 1,
					),
				),
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'page',
				),
				array(
					'param' => 'page_template',
					'operator' => '!=',
					'value' => 'template-front-page.php',
				),
			),
		),
		'menu_order' => 0,
		'position' => 'normal',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => '',
		'active' => 1,
		'description' => '',
	));

	acf_add_local_field_group(array(
		'key' => 'group_5c12947ef2022',
		'title' => 'Sponsor Sidebar Settings',
		'fields' => array(
			array(
				'key' => 'field_5c12948d51dcc',
				'label' => 'Sponsor Level',
				'name' => 'sponsor_level',
				'type' => 'radio',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'choices' => array(
					'primary' => 'Tier 1',
					'default' => 'Default',
				),
				'allow_null' => 0,
				'other_choice' => 0,
				'default_value' => 'default',
				'layout' => 'horizontal',
				'return_format' => 'value',
				'save_other_choice' => 0,
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'ads',
				),
			),
		),
		'menu_order' => 0,
		'position' => 'normal',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => '',
		'active' => 1,
		'description' => '',
	));

endif;
