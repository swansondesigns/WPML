<?php
// @codingStandardsIgnoreFile
// !!! TODO Refactor
/**
 * Envira Log Class.
 *
 * @since 1.8.5
 *
 * @package Envira_Gallery
 * @author  Envira Gallery Team
 */

namespace Envira\Admin;

/**
 * Logs class.
 *
 * @since 1.8.5
 */
class Logs {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.8.5
	 */
	public function __construct() {

		// Determine if whitelabeling is enabled.
		$envira_whitelabel = apply_filters( 'envira_whitelabel', false );

		// Build the labels for the post type.
		$labels = apply_filters(
			'envira_logs_post_type_labels',
			array(
				'name'               => $envira_whitelabel ? apply_filters( 'envira_log_whitelabel_name_plural', false ) : __( 'Envira Logs', 'envira-gallery' ),
				'name_admin_bar'     => $envira_whitelabel ? apply_filters( 'envira_log_whitelabel_name_plural', false ) : __( 'Envira Logs', 'envira-gallery' ),
				'singular_name'      => $envira_whitelabel ? apply_filters( 'envira_log_whitelabel_name', false ) : __( 'Envira Gallery', 'envira-gallery' ),
				'add_new'            => __( 'Add New', 'envira-Logs' ),
				'add_new_item'       => $envira_whitelabel ? __( 'Add New Log', 'envira-gallery' ) : __( 'Add New Envira Log', 'envira-Logs' ),
				'edit_item'          => $envira_whitelabel ? __( 'Edit Log', 'envira-gallery' ) : __( 'Edit Envira Log', 'envira-Logs' ),
				'new_item'           => $envira_whitelabel ? __( 'New Log', 'envira-gallery' ) : __( 'New Envira Log', 'envira-Logs' ),
				'view_item'          => $envira_whitelabel ? __( 'View Log', 'envira-gallery' ) : __( 'View Envira Log', 'envira-Logs' ),
				'search_items'       => $envira_whitelabel ? __( 'Search Logs', 'envira-gallery' ) : __( 'Search Envira Logs', 'envira-gallery' ),
				'not_found'          => $envira_whitelabel ? __( 'No Logs found.', 'envira-gallery' ) : __( 'No Envira Logs found.', 'envira-Logs' ),
				'not_found_in_trash' => $envira_whitelabel ? __( 'No Logs found in trash.', 'envira-gallery' ) : __( 'No Envira Logs found in trash.', 'envira-Logs' ),
				'parent_item_colon'  => '',
				'menu_name'          => __( 'Logs', 'envira-Logs' ),
			)
		);

		// Build out the post type arguments.
		$args = apply_filters(
			'envira_logs_post_type_args',
			array(
				'labels'              => $labels,
				'public'              => false,
				'exclude_from_search' => true,
				'show_ui'             => false,
				'show_in_admin_bar'   => false,
				'rewrite'             => false,
				'query_var'           => false,
				'show_in_menu'        => false,
				'supports'            => array( 'title', 'author' ),
				'capabilities'        => array(
					// Meta caps.
					'edit_post'              => 'edit_envira_log',
					'read_post'              => 'read_envira_log',
					'delete_post'            => 'delete_envira_log',

					// Primitive caps outside map_meta_cap().
					'edit_posts'             => 'edit_envira_logs',
					'edit_others_posts'      => 'edit_other_envira_logs',
					'publish_posts'          => 'publish_envira_logs',
					'read_private_posts'     => 'read_private_envira_logs',

					// Primitive caps used within map_meta_cap().
					'read'                   => 'read',
					'delete_posts'           => 'delete_envira_logs',
					'delete_private_posts'   => 'delete_private_envira_logs',
					'delete_published_posts' => 'delete_published_envira_logs',
					'delete_others_posts'    => 'delete_others_envira_logs',
					'edit_private_posts'     => 'edit_private_envira_logs',
					'edit_published_posts'   => 'edit_published_envira_logs',
					'edit_posts'             => 'create_envira_logs',
				),
				'map_meta_cap'        => true,

			)
		);

		// Register the post type with WordPress.
		register_post_type( 'envira_log', $args );

		// Register custom category taxonomies.
		register_taxonomy(
			'envira_log_category',
			'envira_log',
			array(
				'label'        => __( 'Log Categories' ),
				'hierarchical' => false,
			)
		);

		// If this is the first time it's been registered, check and make sure default terms are installed.
		$terms = array( 'gallery', 'album', 'plugin' );
		$this->register_terms( $terms );

		// Hook into various actions to add log entries
			// Galleries
		// Updating Settings.
		add_action( 'envira_gallery_pre_save_settings', array( $this, 'log_envira_gallery_saved_settings' ), 10, 3 );

			// Albums
			// Plugins
			// API.
	}

	/**
	 * Write log when a gallery is saved
	 *
	 * @since 1.8.5
	 */
	public function log_envira_gallery_saved_settings( $settings, $post_id, $post ) {

		if ( get_post_type( $post_id ) != 'envira' ) {
			return;
		}

		$settings_before = get_post_meta( $post_id, '_eg_gallery_data', true );

		// print_r ($data);
		// print_r ($settings_before);
		// print_r ($settings);
		// exit;
		// combine settings
		$envira_settings  = isset( $_POST['_envira_gallery'] ) ? $_POST['_envira_gallery'] : array();
		$tax_settings     = isset( $_POST['tax_input'] ) ? $_POST['tax_input'] : array();
		$general_settings = isset( $_POST['_general'] ) ? $_POST['_general'] : array();
		$settings         = array_merge( $envira_settings, $general_settings, $tax_settings );
		$details          = false;

		// try to compare settings, note what changed.
		if ( ! empty( $settings_before['config'] ) ) {
			foreach ( $settings_before['config'] as $settings_before_key => $settings_before_value ) {
				if ( ! empty( $envira_settings[ $settings_before_key ] ) ) {
					if ( $envira_settings[ $settings_before_key ] != '' && $settings_before['config'][ $settings_before_key ] != '' && $envira_settings[ $settings_before_key ] != $settings_before['config'][ $settings_before_key ] ) {
						$details[ $settings_before_key ] = array(
							'before' => $settings_before['config'][ $settings_before_key ],
							'after'  => $envira_settings[ $settings_before_key ],
						);
					}
				}
			}
		}

		$this->add_log_entry(
			array(
				'name'            => 'Updated Gallery',
				'description'     => false,
				'item_type'       => 'gallery',
				'action'          => 'update',
				'primary_item_id' => $post_id,
				'settings'        => $settings,
				'details'         => $details,
				'post_parent'     => $post_id,
				'categories'      => array( 'gallery' ),
			)
		);

	}

	/**
	 * Get logs
	 *
	 * @since 1.8.5
	 */
	public function get_log_entries( $args = '' ) {

		$args = wp_parse_args(
			$args,
			array(
				'user_id'           => false,
				'date_created'      => null,
				'post_type'         => 'envira_log',
				'status'            => 'publish',
				'item_type'         => false, /* possible values: plugin, gallery, album, image? */
				'action'            => false, /* possible values: add, delete, update, activate */
				'primary_item_id'   => false, /* would be the id of the main item, so a gallery ID or a plugin slug */
				'secondary_item_id' => false, /* in case it's needed */
				'posts_per_page'    => -1,
			)
		);

		// check for category filtering.
		if ( isset( $_GET['envira-log-filter'] ) && ( intval( $_GET['envira-log-filter'] ) > 0 ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'envira_log_category',
					'field'    => 'term_id',
					'terms'    => array( $_GET['envira-log-filter'] ),
				),
			);
		}

		$query = new \ WP_Query( $args );

		$returned_data = array();

		if ( ! empty( $query->posts ) ) {
			$log_entries = $query->posts;
			foreach ( $log_entries as $key => $log_entry ) {
				$user = get_user_by( 'id', $log_entry->post_author );
				$meta = get_post_meta( $log_entry->ID, 'envira_log_metadata', true );
				switch ( strtolower( $meta['item_type'] ) ) {
					case 'gallery':
						$prefix = 'Gallery';
						$post   = get_post( $meta['primary_item_id'] );
						break;
					case 'album':
						$prefix = 'Album';
						$post   = get_post( $meta['primary_item_id'] );
						break;
					default:
						$prefix = '';
						break;
				}
				$returned_data[]           = array(
					'ID'      => $log_entry->ID,
					'action'  => ucwords( $meta['item_type'] . ' ' . $meta['action'] ),
					'item_id' => '<a target="_blank" href="' . get_edit_post_link( $meta['primary_item_id'] ) . '">"' . $post->post_title . '" (' . $prefix . ' #' . $meta['primary_item_id'] . ')</a>',
					'user'    => '<a target="_blank" href="' . get_edit_user_link( $log_entry->post_author ) . '">' . $user->user_login . '</a>',
					'date'    => date( 'F j, Y g:ia', strtotime( $log_entry->post_date ) ),
					'details' => ! empty( $meta['details'] ) ? $meta['details'] : false,
				);
				$log_entries[ $key ]->meta = $meta;
			}
			return $returned_data;
		}

	}

	/**
	 * Get Change/Details Of Log
	 *
	 * @since 1.8.5
	 */
	public function get_details( $log_id = false, $details = false ) {

		if ( empty( $log_id ) || empty( $details ) ) {
			return;
		}

		$html = '
            <table class="envira-log-changed-table">
            <thead>
                <th></th>
                <th>Before</th>
                <th>After</th>
            </thead>
            <tbody> ';

		foreach ( $details as $detail_label => $detail_info ) {

			$html .= '
                <tr>
                    <th class="key-value">' . $detail_label . '</th>
                    <td>' . $detail_info['before'] . '</td>
                    <td>' . $detail_info['after'] . '</td>
                </tr> ';

		}

		$html .= '</tbody>
            </table>';

		return $html;

	}

	/**
	 * Adding log
	 *
	 * @since 1.8.5
	 */
	public function add_log_entry( $args = '' ) {

		/* what to record: date/time, action, gallery/album id */

		$user = is_user_logged_in() ? wp_get_current_user() : false;

		// Get the primary post information
		$args = wp_parse_args(
			$args,
			array(
				'name'              => '',
				'description'       => '',
				'user_id'           => ( $user ) ? $user->ID : 0,
				'date_created'      => null,
				'status'            => 'publish',
				'categories'        => false,   /* single or array of slugs */
				'item_type'         => '',      /* possible values: plugin, gallery, album, settings, api, image (?) */
				'action'            => '',      /* possible values: add, delete, update, activate */
				'primary_item_id'   => false,   /* would be the id of the main item i.e. a gallery ID, album ID, or a plugin slug */
				'secondary_item_id' => false,   /* in case it's needed */
				'settings'          => null,    /* stores saved settings of a gallery or album */
			)
		);

		// Gather post data.
		$post_data = array(
			'post_type'     => 'envira_log',
			'post_title'    => $args['name'],
			'post_content'  => $args['description'],
			'post_status'   => $args['status'],
			'post_author'   => $args['user_id'],
			'post_date'     => ( $args['date_created'] ) ? $args['date_created'] : false,
			'post_date_gmt' => ( $args['date_created'] ) ? $args['date_created'] : false,
		);

		// Insert the log post into the database.
		$envira_log_id = wp_insert_post( $post_data );

		// Add log metadata.
		$meta_data = array(
			'item_type'         => $args['item_type'],
			'action'            => $args['action'],
			'primary_item_id'   => $args['primary_item_id'],
			'secondary_item_id' => $args['secondary_item_id'],
			'settings'          => ( $args['settings'] ) ? $args['settings'] : null,
			'details'           => ( $args['details'] ) ? $args['details'] : null,
		);

		$meta_id = add_post_meta( $envira_log_id, 'envira_log_metadata', $meta_data, true );

		// Assign log category.
		if ( ! empty( $args['categories'] ) ) {
			if ( is_array( $args['categories'] ) ) {
				foreach ( $args['categories'] as $category_term ) {
					$term_id = term_exists( $category_term, 'envira_log_category' );
					wp_set_post_terms( $envira_log_id, array( $term_id ), 'envira_log' );
				}
			} else {
				$term_id = term_exists( $args['categories'], 'envira_log_category' );
				wp_set_post_terms( $envira_log_id, array( $term_id ), 'envira_log' );
			}
		}

		// If metadata was recorded, it was a success
		if ( $meta_id ) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Register default envira category terms for custom taxonomy
	 *
	 * @since 1.8.5
	 */
	public function register_terms( $terms ) {

		if ( ! is_array( $terms ) ) {
			return;
		}

		foreach ( $terms as $term ) {
			if ( ! term_exists( $term, 'envira_log_category' ) ) {
				wp_insert_term(
					$term,
					'envira_log_category',
					array(
						'description' => 'This is an Envira log category.', // temp description
						'slug'        => 'envira-log-category-' . sanitize_title( $term ),
					)
				);
			}
		}

	}

}


/************************** CREATE A PACKAGE CLASS *****************************
 * ******************************************************************************
 * Create a new list table package that extends the core WP_List_Table class.
 * WP_List_Table contains most of the framework for generating the table, but we
 * need to define and override some methods so that our data can be displayed
 * exactly the way we need it to be.
 *
 * To display this example on a page, you will first need to instantiate the class,
 * then call $yourInstance->prepare_items() to handle any data manipulation, then
 * finally call $yourInstance->display() to render the table to the page.
 *
 * Our theme for this list table is going to be movies.
 */
class Envira_Log_List_Table extends Envira_List_Table {

	/** ************************************************************************
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 ***************************************************************************/
	function __construct() {
		global $status, $page;

		// Set parent defaults
		parent::__construct(
			array(
				'singular' => 'entry',     // singular name of the listed records
				'plural'   => 'entries',    // plural name of the listed records
				'ajax'     => false,        // does this table support ajax?
			)
		);

	}


	/** ************************************************************************
	 * Recommended. This method is called when the parent class can't find a method
	 * specifically build for a given column. Generally, it's recommended to include
	 * one method for each column you want to render, keeping your package class
	 * neat and organized. For example, if the class needs to process a column
	 * named 'title', it would first see if a method named $this->column_title()
	 * exists - if it does, that method will be used. If it doesn't, this one will
	 * be used. Generally, you should try to use custom column methods as much as
	 * possible.
	 *
	 * Since we have defined a column_title() method later on, this method doesn't
	 * need to concern itself with any column with a name of 'title'. Instead, it
	 * needs to handle everything else.
	 *
	 * For more detailed insight into how columns are handled, take a look at
	 * WP_List_Table::single_row_columns()
	 *
	 * @param array $item A singular item (one full row's worth of data)
	 * @param array $column_name The name/slug of the column to be processed
	 * @return string Text or HTML to be placed inside the column <td>
	 **************************************************************************/
	function column_default( $item, $column_name ) {

		$logs = new Logs();

		$actions = array(
			'edit'   => sprintf( ' <a class="editinline" href="#" onclick="return false;">Details</a>', 'envira', 'edit', $item['ID'] ),
			'delete' => sprintf( ' <a href="?post_type=%s&page=%s&action=%s&log_id=%d#envira-tab-logs">Delete</a>', 'envira', 'envira-gallery-tools', 'delete_log', $item['ID'] ),
		);

		switch ( $column_name ) {
			case 'ID':
				return sprintf(
					'%1$s <span style="color:silver ; display : none;">(id:%2$s)</span>%3$s
                    <div style="display:none;" class="test123">
                            ' . $logs->get_details( $item['ID'], $item['details'] ) . '
                    </div>',
					/*$1%s*/ $item['ID'],
					/*$2%s*/ $item['ID'],
					/*$3%s*/ $this->row_actions( $actions )
				);
			case 'action':
			case 'item_id':
			case 'user':
			case 'date':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ); // Show the whole array for troubleshooting purposes
		}
	}


	/** ************************************************************************
	 * Recommended. This is a custom column method and is responsible for what
	 * is rendered in any column with a name/slug of 'title'. Every time the class
	 * needs to render a column, it first looks for a method named
	 * column_{$column_title} - if it exists, that method is run. If it doesn't
	 * exist, column_default() is called instead.
	 *
	 * This example also illustrates how to implement rollover actions. Actions
	 * should be an associative array formatted as 'slug'=>'link html' - and you
	 * will need to generate the URLs yourself. You could even ensure the links
	 *
	 * @see WP_List_Table::::single_row_columns()
	 * @param array $item A singular item (one full row's worth of data)
	 * @return string Text to be placed inside the column <td> (movie title only)
	 **************************************************************************/
	// function column_title($item){
	// Build row actions
	// $actions = array(
	// 'edit'      => sprintf('<a href="?page=%s&action=%s&movie=%s">Edit</a>',$_REQUEST['page'],'edit',$item['ID']),
	// 'delete'    => sprintf('<a href="?page=%s&action=%s&movie=%s">Delete</a>',$_REQUEST['page'],'delete',$item['ID']),
	// );
	// Return the title contents
	// return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
	// *$1%s*/ $item['title'],
	// *$2%s*/ $item['ID'],
	// *$3%s*/ $this->row_actions($actions)
	// );
	// }


	/** ************************************************************************
	 * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
	 * is given special treatment when columns are processed. It ALWAYS needs to
	 * have it's own method.
	 *
	 * @see WP_List_Table::::single_row_columns()
	 * @param array $item A singular item (one full row's worth of data)
	 * @return string Text to be placed inside the column <td> (movie title only)
	 **************************************************************************/
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ $this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie")
			/*$2%s*/ $item['ID']                // The value of the checkbox should be the record's id
		);
	}


	/** ************************************************************************
	 * REQUIRED! This method dictates the table's columns and titles. This should
	 * return an array where the key is the column slug (and class) and the value
	 * is the column's title text. If you need a checkbox for bulk actions, refer
	 * to the $columns array below.
	 *
	 * The 'cb' column is treated differently than the rest. If including a checkbox
	 * column in your table you must create a column_cb() method. If you don't need
	 * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
	 *
	 * @see WP_List_Table::::single_row_columns()
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	 **************************************************************************/
	function get_columns() {
		$columns = array(
			'cb'      => '<input type="checkbox" />', // Render a checkbox instead of text
			'ID'      => 'ID',
			'action'  => 'Action',
			'item_id' => 'Item',
			'user'    => 'User',
			'date'    => 'Date',
		);
		return $columns;
	}


	/** ************************************************************************
	 * Optional. If you want one or more columns to be sortable (ASC/DESC toggle),
	 * you will need to register it here. This should return an array where the
	 * key is the column that needs to be sortable, and the value is db column to
	 * sort by. Often, the key and value will be the same, but this is not always
	 * the case (as the value is a column name from the database, not the list table).
	 *
	 * This method merely defines which columns should be sortable and makes them
	 * clickable - it does not handle the actual sorting. You still need to detect
	 * the ORDERBY and ORDER querystring variables within prepare_items() and sort
	 * your data accordingly (usually by modifying your query).
	 *
	 * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
	 **************************************************************************/
	function get_sortable_columns() {
		$sortable_columns = array(
			'ID' => array( 'ID', false ),     // true means it's already sorted
			// 'item_id'    => array('item_id',false),
			// 'date'  => array('date',false)
		);
		return $sortable_columns;
	}


	/** ************************************************************************
	 * Optional. If you need to include bulk actions in your list table, this is
	 * the place to define them. Bulk actions are an associative array in the format
	 * 'slug'=>'Visible Title'
	 *
	 * If this method returns an empty value, no bulk action will be rendered. If
	 * you specify any bulk actions, the bulk actions box will be rendered with
	 * the table automatically on display().
	 *
	 * Also note that list tables are not automatically wrapped in <form> elements,
	 * so you will need to create those manually in order for bulk actions to function.
	 *
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
	 **************************************************************************/
	function get_bulk_actions() {
		$actions = array(
			'delete' => 'Delete',
		);
		return $actions;
	}


	/** ************************************************************************
	 * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
	 * For this example package, we will handle it in the class to keep things
	 * clean and organized.
	 *
	 * @see $this->prepare_items()
	 **************************************************************************/
	function process_bulk_action() {

		$tools = new Tools();

		// Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {
			if ( ! empty( $_POST['entry'] ) && is_array( $_POST['entry'] ) ) {
				$tools->tools_delete_logs( $_POST['entry'] );
			}
		}

	}

	function search_box( $text, $input_id ) {

		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
			return;
		}

		$input_id = $input_id . '-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['order'] ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['post_mime_type'] ) ) {
			echo '<input type="hidden" name="post_mime_type" value="' . esc_attr( $_REQUEST['post_mime_type'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['detached'] ) ) {
			echo '<input type="hidden" name="detached" value="' . esc_attr( $_REQUEST['detached'] ) . '" />';
		}
		?>
	<p class="search-box">
		<label class="screen-reader-text" for="<?php echo $input_id; ?>"><?php echo $text; ?>:</label>
		<input type="search" id="<?php echo $input_id; ?>" name="s" value="<?php _admin_search_query(); ?>" />
		<?php submit_button( $text, 'button', 'Search Logs', false, array( 'id' => 'search-submit' ) ); ?>
	</p>
		<?php
	}

	function extra_tablenav( $which ) {
		global $wpdb, $testiURL, $tablename, $tablet;
		$move_on_url = '&envira-log-filter=';
		if ( $which === 'top' ) {
			?>
			<div class="alignleft actions bulkactions">
			<?php
			$cats = get_terms(
				array(
					'taxonomy'   => 'envira_log_category',
					'hide_empty' => false,
				)
			);
			if ( $cats ) {
				?>
				<select name="envira-filter" class="envira-log-filter-cat">
					<option value="">Filter by Category</option>
					<option value=""></option>
					<?php
					foreach ( $cats as $cat ) {
						$selected = '';
						if ( ! empty( $_GET['envira-log-filter'] ) && $_GET['envira-log-filter'] === $cat->term_id ) {
							$selected = ' selected = "selected"';
						}
						?>
					<option value="<?php echo $move_on_url . $cat->term_id; ?>" <?php echo $selected; ?>><?php echo ucwords( $cat->name ); ?></option>
						<?php
					}
					?>
				</select>
				<?php
			}
			?>

			</div>
			<div class="alignleft actions clearlogs">
				<a href="<?php echo admin_url( 'edit.php?post_type=envira&page=envira-gallery-tools&action=clear-envira-logs#!envira-tab-logs' ); ?>" id="clear-envira-logs" class="button action" value="Clear Logs">Clear Logs</a>
			</div>
			<?php
		}
		if ( $which === 'bottom' ) {
			// The code that goes after the table is there
		}
		?>

		<?php
	}

	/** ************************************************************************
	 * REQUIRED! This is where you prepare your data for display. This method will
	 * usually be used to query the database, sort and filter the data, and generally
	 * get it ready to be displayed. At a minimum, we should set $this->items and
	 * $this->set_pagination_args(), although the following properties and methods
	 * are frequently interacted with here...
	 *
	 * @global WPDB $wpdb
	 * @uses $this->_column_headers
	 * @uses $this->items
	 * @uses $this->get_columns()
	 * @uses $this->get_sortable_columns()
	 * @uses $this->get_pagenum()
	 * @uses $this->set_pagination_args()
	 **************************************************************************/
	public function prepare_items() {
		global $wpdb; // This is used only if making any database queries

		/**
		 * First, lets decide how many records per page to show
		 */
		$per_page = 5;

		/**
		 * REQUIRED. Now we need to define our column headers. This includes a complete
		 * array of columns to be displayed (slugs & titles), a list of columns
		 * to keep hidden, and a list of columns that are sortable. Each of these
		 * can be defined in another method (as we've done here) before being
		 * used to build the value for our _column_headers property.
		 */
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		/**
		 * REQUIRED. Finally, we build an array to be used by the class for column
		 * headers. The $this->_column_headers property takes an array which contains
		 * 3 other arrays. One for all columns, one for hidden columns, and one
		 * for sortable columns.
		 */
		$this->_column_headers = array( $columns, $hidden, $sortable );

		/**
		 * Optional. You can handle your bulk actions however you see fit. In this
		 * case, we'll handle them within our package just to keep things clean.
		 */
		$this->process_bulk_action();

		/**
		 * Instead of querying a database, we're going to fetch the example data
		 * property we created for use in this plugin. This makes this example
		 * package slightly different than one you might build on your own. In
		 * this example, we'll be using array manipulation to sort and paginate
		 * our data. In a real-world implementation, you will probably want to
		 * use sort and pagination data to build a custom query instead, as you'll
		 * be able to use your precisely-queried data immediately.
		 */
		$logs = new Logs();
		$data = $logs->get_log_entries();

		/**
		 * This checks for sorting input and sorts the data in our array accordingly.
		 *
		 * In a real-world situation involving a database, you would probably want
		 * to handle sorting by passing the 'orderby' and 'order' values directly
		 * to a custom query. The returned data will be pre-sorted, and this array
		 * sorting technique would be unnecessary.
		 */
		function usort_reorder( $a, $b ) {
			$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'title'; // If no sort, default to title
			$order   = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc'; // If no order, default to asc
			$result  = strcmp( $a[ $orderby ], $b[ $orderby ] ); // Determine sort order
			return ( $order === 'asc' ) ? $result : -$result; // Send final sort direction to usort
		}
		if ( ! empty( $data ) ) {
			usort( $data, array( $this, 'usort_reorder' ) );
		}

		/***********************************************************************
		 * ---------------------------------------------------------------------
		 * vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
		 *
		 * In a real-world situation, this is where you would place your query.
		 *
		 * For information on making queries in WordPress, see this Codex entry:
		 * http://codex.wordpress.org/Class_Reference/wpdb
		 *
		 * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
		 * ---------------------------------------------------------------------
		 */

		/**
		 * REQUIRED for pagination. Let's figure out what page the user is currently
		 * looking at. We'll need this later, so you should always include it in
		 * your own package classes.
		 */
		$current_page = $this->get_pagenum();

		/**
		 * REQUIRED for pagination. Let's check how many items are in our data array.
		 * In real-world use, this would be the total number of items in your database,
		 * without filtering. We'll need this later, so you should always include it
		 * in your own package classes.
		 */
		$total_items = count( $data );

		/**
		 * The WP_List_Table class does not handle pagination for us, so we need
		 * to ensure that the data is trimmed to only the current page. We can use
		 * array_slice() to
		 */
		if ( ! empty( $data ) ) {
			$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
		}

		/**
		 * REQUIRED. Now we can add our *sorted* data to the items property, where
		 * it can be used by the rest of the class.
		 */
		$this->items = $data;

		/**
		 * REQUIRED. We also have to register our pagination options & calculations.
		 */
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,                  // WE have to calculate the total number of items
				'per_page'    => $per_page,                     // WE have to determine how many items to show on a page
				'total_pages' => ceil( $total_items / $per_page ),   // WE have to calculate the total number of pages
			)
		);
	}


}
