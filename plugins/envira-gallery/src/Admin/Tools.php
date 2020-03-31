<?php
/**
 * Tools class.
 *
 * @since 1.8.4.1
 *
 * @package Envira_Gallery
 * @author  Envira Gallery Team
 */

namespace Envira\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Envira\Admin\Logs;
use Envira\Admin\Support;

/**
 * Tools class.
 *
 * @since 1.8.4.1
 *
 * @package Envira_Gallery
 * @author  Envira Gallery Team
 */
class Tools {

	/**
	 * Holds the submenu pagehook.
	 *
	 * @since 1.7.0
	 *
	 * @var string
	 */
	public $hook;

	/**
	 * Holds the tools page slug.
	 *
	 * @since 1.7.0
	 *
	 * @var string
	 */
	public $page_slug = 'envira-gallery-tools';

	/**
	 * Primary class constructor.
	 *
	 * @since 1.7.0
	 */
	public function __construct() {

		// Add custom tools submenu.
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 15 );

		// Add callbacks for tools tabs.
		add_action( 'envira_gallery_tab_tools_tools', array( $this, 'tools_tools_tab' ) );
		add_action( 'envira_gallery_tab_tools_status', array( $this, 'tools_status_tab' ) );
		add_action( 'envira_gallery_tab_tools_logs', array( $this, 'tools_logs_tab' ) );
		add_action( 'envira_gallery_tab_tools_rollback', array( $this, 'tools_rollback_tab' ) );

		// Add the tools menu item to the Plugins table.
		add_filter( 'plugin_action_links_' . plugin_basename( ENVIRA_FILE ), array( $this, 'tools_link' ) );

		// Detect actions.
		add_action( 'admin_init', array( $this, 'tools_log_actions' ), 10 );

		// Perform actions.
		add_action( 'admin_init', array( $this, 'clear_all_envira_cache' ), 10 );
		add_action( 'admin_init', array( $this, 'clear_all_transients' ), 10 );
		add_action( 'admin_init', array( $this, 'toggle_gallery_transient_setting' ), 10 );
		add_action( 'admin_init', array( $this, 'toggle_album_transient_setting' ), 10 );
		add_action( 'admin_init', array( $this, 'clear_all_envira_options' ), 10 );

	}

	/**
	 * Register the Tools submenu item for Envira.
	 *
	 * @since 1.7.0
	 */
	public function admin_menu() {

		$label = apply_filters( 'envira_whitelabel', false ) ? '' : __( 'Envira Gallery ', 'envira-gallery' );
		// Register the submenu.
		$this->hook = add_submenu_page(
			'edit.php?post_type=envira',
			$label . __( 'Tools', 'envira-gallery' ),
			'<span> ' . __( 'Tools', 'envira-gallery' ) . '</span>',
			apply_filters( 'envira_gallery_menu_cap', 'manage_options' ),
			ENVIRA_SLUG . '-tools',
			array( $this, 'tools_page' )
		);

		// If successful, load admin assets only on that page and check for tools refresh.
		if ( $this->hook ) {
			add_action( 'load-' . $this->hook, array( $this, 'tools_page_assets' ) );
			add_action( 'load-' . $this->hook, array( $this, 'maybe_fix_migration' ) );
		}

	}

	/**
	 * Loads assets for the tools page.
	 *
	 * @since 1.7.0
	 */
	public function tools_page_assets() {

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

	}

	/**
	 * Register and enqueue tools page specific CSS.
	 *
	 * @since 1.7.0
	 */
	public function enqueue_admin_styles() {

		wp_register_style( ENVIRA_SLUG . '-tools-style', plugins_url( 'assets/css/tools.css', ENVIRA_FILE ), array(), ENVIRA_VERSION );
		wp_enqueue_style( ENVIRA_SLUG . '-tools-style' );

		wp_register_style( ENVIRA_SLUG . '-table-style', plugins_url( 'assets/css/table.css', ENVIRA_FILE ), array(), ENVIRA_VERSION );
		wp_enqueue_style( ENVIRA_SLUG . '-table-style' );

		// Run a hook to load in custom styles.
		do_action( 'envira_gallery_tools_styles' );

	}

	/**
	 * Register and enqueue tools page specific JS.
	 *
	 * @since 1.7.0
	 */
	public function enqueue_admin_scripts() {

		// Tabs.
		wp_register_script( ENVIRA_SLUG . '-tabs-script', plugins_url( 'assets/js/tabs.js', ENVIRA_FILE ), array( 'jquery' ), ENVIRA_VERSION, true );
		wp_enqueue_script( ENVIRA_SLUG . '-tabs-script' );

		// Tools.
		wp_register_script( ENVIRA_SLUG . '-tools-script', plugins_url( 'assets/js/tools.js', ENVIRA_FILE ), array( 'jquery' ), ENVIRA_VERSION, true );
		wp_enqueue_script( ENVIRA_SLUG . '-tools-script' );

		// Run a hook to load in custom scripts.
		do_action( 'envira_gallery_tools_scripts' );

	}

	/**
	 * Clear Envira Cache
	 *
	 * @since 1.8.5
	 */
	public function clear_all_envira_cache() {

		if ( empty( $_GET['page'] ) || $this->page_slug !== $_GET['page'] || ( $this->page_slug === $_GET['page'] && empty( $_POST ) ) ) { // @codingStandardsIgnoreLine
			return;
		}

		if ( isset( $_POST['action'] ) && 'flush-cache' === $_POST['action'] ) { // @codingStandardsIgnoreLine

			if ( function_exists( 'envira_flush_all_cache' ) ) {
				envira_flush_all_cache();
				add_action( 'envira_gallery_tools_tab_notice', array( $this, 'clear_envira_cache_message' ), 10 );
			}
		}

	}

	/**
	 * Display updated envira clear cache message
	 *
	 * @since 1.8.5
	 */
	public function clear_envira_cache_message() {

		?>

		<div class="notice notice-warning">

			<?php

				echo '<p>Envira Gallery cache cleared.</p>';

			?>

		</div>

		<?php

	}

	/**
	 * Clear all transients
	 *
	 * @since 1.8.5
	 */
	public function clear_all_transients() {

		if ( empty( $_GET['page'] ) || $this->page_slug !== $_GET['page'] || ( $this->page_slug === $_GET['page'] && empty( $_POST ) ) ) { // @codingStandardsIgnoreLine
			return;
		}

		if ( isset( $_POST['action'] ) && 'clear-all' === $_POST['action'] ) { // @codingStandardsIgnoreLine

			// this mimics the way envira_flush_all_cache() works except it flushes everything, not just envira cache.
			global $wpdb;
			$transient_pattern = '_transient_%';

			$query = $wpdb->get_results( $wpdb->prepare( "SELECT option_name AS name, option_value AS value FROM $wpdb->options WHERE option_name LIKE %s", $transient_pattern ), OBJECT ); // @codingStandardsIgnoreLine

			if ( ! empty( $query ) ) {

				foreach ( $query as $result ) {

					$transient = $result->name;

					$key = str_replace( '_transient_timeout_', '', $transient );
					$key = str_replace( '_transient_', '', $transient );

					if ( wp_using_ext_object_cache() ) {

						if ( function_exists( 'wp_cache_delete' ) ) {

							wp_cache_delete( $key, 'transient' );

						}
					} else {

						delete_transient( $key );

					}
				}
			}
		}

	}

	/**
	 * Display updated envira clear cache message
	 *
	 * @since 1.8.5
	 */
	public function clear_all_transients_message() {

		?>

		<div class="notice notice-warning">

			<?php

				echo '<p>Transients cleared.</p>';

			?>

		</div>

		<?php

	}

	/**
	 * Toggle Enable/Disabling Gallery Transient Settings
	 *
	 * @since 1.8.5
	 */
	public function toggle_gallery_transient_setting() {

		if ( empty( $_GET['page'] ) || $this->page_slug !== $_GET['page'] || ( $this->page_slug === $_GET['page'] && empty( $_POST ) ) ) { // @codingStandardsIgnoreLine
			return;
		}

		if ( isset( $_POST['action'] ) && 'toggle-envira-gallery-transients' === $_POST['action'] ) { // @codingStandardsIgnoreLine

			wp_cache_delete( 'eg_t_gallery_status' );

			if ( get_option( 'eg_t_gallery_status' ) !== false ) {
				delete_option( 'eg_t_gallery_status' );
			} else {
				add_option( 'eg_t_gallery_status', 'disabled' );
			}

			add_action( 'envira_gallery_tools_tab_notice', array( $this, 'toggle_gallery_transients_message' ), 10 );
		}

	}

	/**
	 * Display updated envira clear cache message
	 *
	 * @since 1.8.5
	 */
	public function toggle_gallery_transients_message() {

		?>

		<div class="notice notice-warning">

			<?php

				$support           = new Support();
				$gallery_on_or_off = $support->is_gallery_on_or_off();
				$status            = strtolower( $gallery_on_or_off ) === 'off' ? 'off' : 'on'; // they are on by default.

				echo '<p>Envira gallery transients are now <strong>' . esc_html( $status ) . '</strong>.</p>';

			?>

		</div>

		<?php

	}

	/**
	 * Toggle Enable/Disabling Album Transient Settings
	 *
	 * @since 1.8.5
	 */
	public function toggle_album_transient_setting() {

		if ( empty( $_GET['page'] ) || $this->page_slug !== $_GET['page'] || ( $this->page_slug === $_GET['page'] && empty( $_POST ) ) ) { // @codingStandardsIgnoreLine
			return;
		}

		if ( isset( $_POST['action'] ) && 'toggle-envira-album-transients' === $_POST['action'] ) { // @codingStandardsIgnoreLine

			wp_cache_delete( 'eg_t_album_status' );

			if ( get_option( 'eg_t_album_status' ) !== false ) {
				delete_option( 'eg_t_album_status' );
			} else {
				add_option( 'eg_t_album_status', 'disabled' );
			}

			add_action( 'envira_gallery_tools_tab_notice', array( $this, 'toggle_album_transients_message' ), 10 );
		}

	}

	/**
	 * Display updated envira clear cache message
	 *
	 * @since 1.8.5
	 */
	public function toggle_album_transients_message() {
		?>

		<div class="notice notice-warning">

			<?php

				$support            = new Support();
				$is_album_on_or_off = $support->is_album_on_or_off();
				$status             = strtolower( $is_album_on_or_off ) === 'off' ? 'off' : 'on'; // they are on by default.

				echo '<p>Envira album transients are now <strong>' . esc_html( $status ) . '</strong>.</p>';

			?>

		</div>

		<?php

	}

	/**
	 * Delete Envira Options
	 *
	 * @since 1.8.5
	 */
	public function clear_all_envira_options() {

		if ( empty( $_GET['page'] ) || $this->page_slug !== $_GET['page'] || ( $this->page_slug === $_GET['page'] && empty( $_POST ) ) ) { // @codingStandardsIgnoreLine
			return;
		}

		if ( isset( $_POST['action'] ) && 'delete-options' === $_POST['action'] ) { // @codingStandardsIgnoreLine

			global $wpdb;

			$results = $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE ('eg_%');" ); // @codingStandardsIgnoreLine

			add_action( 'envira_gallery_tools_tab_notice', array( $this, 'clear_all_envira_options_message' ), 10 );

		}

	}

	/**
	 * Display updated envira clear cache message
	 *
	 * @since 1.8.5
	 */
	public function clear_all_envira_options_message() {

		?>

		<div class="notice notice-warning">

			<?php

				echo '<p>Envira gallery options are cleared.</p>';

			?>

		</div>

		<?php

	}

	/**
	 * Callback to output the Envira tools page.
	 *
	 * @since 1.7.0
	 */
	public function tools_page() {

		do_action( 'envira_head' );

		?>

		<!-- Tabs -->
		<h2 id="envira-tabs-nav" class="envira-tabs-nav" data-container="#<?php echo esc_attr( $this->page_slug ); ?>" data-update-hashbang="1">
			<?php
			$i = 0;
			foreach ( (array) $this->get_envira_tools_tab_nav() as $id => $title ) {
				$class = ( 0 === $i ? 'envira-active' : '' );
				?>
				<a class="nav-tab <?php echo esc_html( $class ); ?>" href="#envira-tab-<?php echo esc_attr( $id ); ?>" title="<?php echo esc_html( $title ); ?>"><?php echo esc_html( $title ); ?></a>
				<?php
				$i++;
			}
			?>
		</h2>

		<!-- Tab Panels -->
		<div id="<?php echo esc_attr( $this->page_slug ); ?>" class="wrap">
			<h1 class="envira-hideme"></h1>
			<div class="envira-gallery envira-clear">
				<div id="envira-tabs" class="envira-clear" data-navigation="#envira-tabs-nav">
					<?php
					$i = 0;
					foreach ( (array) $this->get_envira_tools_tab_nav() as $id => $title ) {
						$class = ( 0 === $i ? 'envira-active' : '' );
						?>
						<div id="envira-tab-<?php echo esc_html( $id ); ?>" class="envira-tab envira-clear <?php echo esc_html( $class ); ?>">
							<?php do_action( 'envira_gallery_tab_tools_' . $id ); ?>
						</div>
						<?php
						$i++;
					}
					?>
				</div>
			</div>
		</div>

		<?php

	}

	/**
	 * Callback for getting all of the tools tabs for Envira.
	 *
	 * @since 1.7.0
	 *
	 * @return array Array of tab information.
	 */
	public function get_envira_tools_tab_nav() {

		$tabs = array(
			'tools'  => __( 'Tools', 'envira-gallery' ), // This tab is required. DO NOT REMOVE VIA FILTERING.
			'status' => __( 'System Status', 'envira-gallery' ),
		);
		$tabs = apply_filters( 'envira_gallery_tools_tab_nav', $tabs );

		return $tabs;

	}

	/**
	 * Callback for displaying the UI for general settings tab.
	 *
	 * @since 1.7.0
	 */
	public function tools_tools_tab() {

		// Get settings.
		?>
		<div id="envira-tools-tools">
			<?php

			// Output any notices now.
			do_action( 'envira_gallery_tools_tab_notice' );

			$galleries   = _envira_get_galleries();
			$domain_name = get_bloginfo( 'url' );

			?>

			<table class="form-table">
				<tbody>
					<!-- Fix Broken Migration -->
					<tr id="envira-fix-migration-box">
						<th scope="row">
							<label for="envira-fix-migration"><?php esc_html_e( 'Fix Broken Migration', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<form id="envira-fix-migration" method="post">
								<?php wp_nonce_field( 'envira-fix-migration-nonce', 'envira-fix-migration-nonce' ); ?>
								<?php if ( ! empty( $galleries ) ) { ?>
									<select id="envira-fix-migration-gallery" name="envira_fix_migration_gallery">
										<option value="-1"><?php esc_html_e( 'All Galleries' ); ?></option>
									<?php
									foreach ( $galleries as $gallery ) {
										$post = get_post( $gallery['id'] );
										?>
										<option value="<?php echo esc_html( $gallery['id'] ); ?>"><?php echo esc_html( $post->post_title ); ?> -- <?php echo count( $gallery['gallery'] ); ?> <?php esc_html_e( 'Items (ID: ' ); ?><?php echo esc_attr( $gallery['id'] ); ?>)</option>
									<?php } ?>
								</select>
							<?php } ?>
								<?php submit_button( __( 'Fix', 'envira-gallery' ), 'primary', 'envira-fix-migration-submit', false ); ?>
								<p class="description"><?php esc_html_e( 'If you’ve recently installed an SSL or migrated your site to a new domain and notice your images aren’t showing, run this process to update the URLs to your images.', 'envira-gallery' ); ?></p>
							</form>
						</td>
					</tr>
				</tbody>
			</table>

			<table class="form-table">
				<tbody>
					<!-- Clear Cache/Transients -->
					<tr id="envira-clear-cache-box">

						<th scope="row">
							<label for="envira-fix-migration"><?php esc_html_e( 'Envira Cache', 'envira-gallery' ); ?></label>
						</th>

						<td>

							<?php

								$support          = new Support();
								$total            = $support->total_transients();
								$total_transients = ! empty( $total->total ) ? intval( $total->total ) : '0';

							?>

							<div style="display: inline-block; margin: 5px;">
								<form action="" method="post">
									<input type="hidden" name="action" value="flush-cache" />
									<input type="submit" class="button button-primary" value="<?php esc_html_e( 'Flush All Envira Cache', 'envira-gallery' ); ?> <?php echo '(' . intval( $support->total_envira_transients() ) . ')'; ?>" />
								</form>
							</div>
							<div style="display: inline-block; margin: 5px;">
								<form action="" method="post">
									<input type="hidden" name="action" value="clear-all" />
									<input type="submit" class="button button-warning" value="<?php esc_html_e( 'Clear ALL Transients', 'envira-gallery' ); ?> <?php echo '(' . intval( $total_transients ) . ')'; ?>" />
								</form>
							</div>
							<div style="display: inline-block; margin: 5px;">
								<?php
									$gallery_on_or_off = $support->is_gallery_on_or_off();
									$style_color       = strtolower( $gallery_on_or_off ) === 'off' ? 'red' : '';
									$text              = strtolower( $gallery_on_or_off ) === 'off' ? 'On' : 'Off'; // they are on by default.
								?>
								<form action="" method="post">
									<input type="hidden" name="action" value="toggle-envira-gallery-transients" />
									<input type="submit" class="button button-error" style="color: <?php echo esc_attr( $style_color ); ?>" value="<?php esc_html_e( 'Turn Gallery Cache ', 'envira-gallery' ); ?><?php echo esc_html( $text ); ?>" />
								</form>
							</div>
							<?php

							if ( class_exists( 'Envira_Albums' ) ) {

								?>
							<div style="display: inline-block; margin: 5px;">
								<?php
									$is_album_on_or_off = $support->is_album_on_or_off();
									$style_color        = strtolower( $is_album_on_or_off ) === 'off' ? 'red' : '';
									$text               = strtolower( $is_album_on_or_off ) === 'off' ? 'On' : 'Off'; // they are on by default.
								?>
								<form action="" method="post">
									<input type="hidden" name="action" value="toggle-envira-album-transients" />
									<input type="submit" class="button button-error" style="color: <?php echo esc_attr( $style_color ); ?>" value="<?php esc_html_e( 'Turn Album Cache ', 'envira-gallery' ); ?><?php echo esc_html( $text ); ?>" />
								</form>
							</div>
							<?php } ?>
							<p class="description"><?php esc_html_e( 'If your gallery changes aren’t showing on your gallery, you may need to flush the Envira cache and clear ALL transients to completely clear any residual cache on the gallery. You can also turn on or off the transients for Envira galleries and albums.', 'envira-gallery' ); ?></p>
						</td>

				</tbody>

			</table>

			<table class="form-table">
				<tbody>
					<!-- Clear Options -->
					<tr id="envira-clear-options-box">

						<th scope="row">
							<label for="envira-fix-migration"><?php esc_html_e( 'Envira Options', 'envira-gallery' ); ?></label>
						</th>

						<td>

							<?php

								$envira_options = $support->total_options();

							?>

							<div style="margin-top: 10px;">
								<form action="" method="post">
									<input type="hidden" name="action" value="delete-options" />
									<input type="submit" class="button button-primary" value="<?php esc_html_e( 'Clear Envira Specific Options (', 'envira-gallery' ); ?><?php echo count( $envira_options ); ?>)" />
								</form>
							</div>

							<p class="description"><?php esc_html_e( 'If expected addons aren\'t showing, click this button to clear any residual cached Envira option..', 'envira-gallery' ); ?></p>
						</td>

					</tr>

				</tbody>

			</table>

			</div>

		</div>
		<?php

	}

	/**
	 * Callback for displaying the UI for general settings tab.
	 *
	 * @since 1.7.0
	 */
	public function tools_status_tab() {

		// Get settings.
		?>
		<div id="envira-tools-status">
			<?php
			// Output any notices now.
			do_action( 'envira_gallery_tools_status_tab_notice' );

			$browser = new \Envira\Utils\Browser();

			if ( get_bloginfo( 'version' ) < '3.4' ) {
				$theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' ); // @codingStandardsIgnoreLine
				$theme      = $theme_data['Name'] . ' ' . $theme_data['Version'];
			} else {
				$theme_data = wp_get_theme();
				$theme      = $theme_data->get( 'Name' ) . ' ' . $theme_data->get( 'Version' );
			}

			// Try to identify the hosting provider.
			$host = false;

			if ( defined( 'WPE_APIKEY' ) ) {
				$host = 'WP Engine';
			} elseif ( defined( 'PAGELYBIN' ) ) {
				$host = 'Pagely';
			}

			$request['cmd'] = '_notify-validate';

			$params = array(
				'sslverify' => false,
				'timeout'   => 60,
				'body'      => $request,
			);

			$response = wp_remote_post( 'https://www.paypal.com/cgi-bin/webscr', $params );

			if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
				$wp_remote_post = 'wp_remote_post() works' . "\n";
			} else {
				$wp_remote_post = 'wp_remote_post() does not work' . "\n";
			}

			?>

			<div class="wrap">
					<div id="templateside">
						<p class="instructions"><?php esc_html_e( 'The information provided on this screen is intended to be shared with Envira Gallery when opening a new support ticket.', 'send-system-info' ); ?></p>
						<p class="instructions"><?php esc_html_e( 'This information can be downloaded as a text file, then uploaded to the support ticket.', 'send-system-info' ); ?></p>
						<p class="instructions">
							<?php

							$documentation = '<a target="_blank" href="https://enviragallery.com/docs/">See our documentation</a>';
							printf( esc_html__( '%s for more details.', 'my-text-domain' ), $documentation ); // @codingStandardsIgnoreLine

							?>

					</div>
					<div id="template">
						<?php // Form used to download .txt file. ?>
						<form action="<?php echo esc_url( self_admin_url( 'admin-ajax.php' ) ); ?>" method="post" enctype="multipart/form-data" >
							<input type="hidden" name="action" value="download_system_info" />
							<div>

							<?php

								envira_load_admin_partial(
									'settings-debug-output',
									array(
										'instance'  => $this,
										'browser'   => $browser,
										'theme'     => $theme,
										'host'      => $host,
										'wp_remote' => $wp_remote_post,
									)
								);
							?>
							</div>
							<p class="submit">
								<input type="submit" class="button button-primary" value="<?php esc_html_e( 'Download System Info as Text File', 'send-system-info' ); ?>" />
							</p>
						</form>
					</div>
			</div>

		</div>
		<?php

	}

	/**
	 * Callback for displaying the UI for general settings tab.
	 *
	 * @since 1.7.0
	 */
	public function tools_logs_tab() {

		$logs                  = new Logs();
		$envira_log_list_table = new Envira_Log_List_Table();

		// Fetch, prepare, sort, and filter our data...
		$envira_log_list_table->prepare_items();

		// Get settings.
		?>
		<div id="envira-tools-logs">
			<?php

			// Output any notices now.
			do_action( 'envira_gallery_tools_logs_tab_notice' );

			?>

			<!-- Logs -->
			<form id="logs-filter" method="post">
				<!-- For plugins, we also need to ensure that the form posts back to our current page -->
				<input type="hidden" name="page" value="<?php echo esc_html( $_REQUEST['page'] ); // @codingStandardsIgnoreLine ?>" />
				<!-- Now we can render the completed list table -->
				<?php $envira_log_list_table->display(); ?>
			</form>

		</div>
		<?php

	}

	/**
	 * Callback for displaying the UI for general settings tab.
	 *
	 * @since 1.7.0
	 */
	public function tools_rollback_tab() {

		// Get settings.
		?>
		<div id="envira-tools-rollback">
			<?php
			// Output any notices now.
			do_action( 'envira_gallery_tools_rollback_tab_notice' );
			?>

			Rollback

		</div>
		<?php

	}

	/**
	 * Retrieve the plugin basename from the plugin slug.
	 *
	 * @since 1.7.0
	 *
	 * @param string $slug The plugin slug.
	 * @return string      The plugin basename if found, else the plugin slug.
	 */
	public function get_plugin_basename_from_slug( $slug ) {

		$keys = array_keys( get_plugins() );

		foreach ( $keys as $key ) {
			if ( preg_match( '|^' . $slug . '|', $key ) ) {
				return $key;
			}
		}

		return $slug;

	}

	/**
	 * Add Tools page to plugin action links in the Plugins table.
	 *
	 * @since 1.7.0
	 *
	 * @param array $links    Default plugin action links.
	 * @return array $links   Amended plugin action links.
	 */
	public function tools_link( $links ) {

		$tools_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url(
				add_query_arg(
					array(
						'post_type' => 'envira',
						'page'      => ( class_exists( 'Envira_Gallery' ) ? $this->page_slug : 'envira-gallery-lite-tools' ),
					),
					admin_url( 'edit.php' )
				)
			),
			__( 'Tools', 'envira-gallery' )
		);
		array_unshift( $links, $tools_link );

		return $links;

	}

	/**
	 * Outputs the tool "box" on the tools page.
	 *
	 * @since 1.7.0
	 *
	 * @param   object $tool               Addon data from the API / transient call.
	 * @param   int    $counter            Index of this Addon in the collection.
	 * @param   bool   $is_licensed        Whether the Addon is licensed for use.
	 * @param   array  $installed_plugins  Installed WordPress Plugins.
	 */
	public function get_tool_card( $tool, $counter = 0, $is_licensed = false, $installed_plugins = false ) {

		// Setup some vars.
		$plugin_basename = $this->get_plugin_basename_from_slug( $tool->slug );
		$categories      = implode( ',', $tool->categories );
		if ( ! $installed_plugins ) {
			$installed_plugins = get_plugins();
		}

		// If the Addon doesn't supply an upgrade_url key, it's because the user hasn't provided a license
		// get_upgrade_link() will return the Lite or Pro link as necessary for us.
		if ( ! isset( $tool->upgrade_url ) ) {
			$tool->upgrade_url = envira_get_upgrade_link();
		}

		// Output the card.
		?>
		<div class="envira-tool">
			<h3 class="envira-tool-title"><?php echo esc_html( $tool->title ); ?></h3>
			<?php
			if ( ! empty( $tool->image ) ) {
				?>
				<img class="envira-tool-thumb" src="<?php echo esc_url( $tool->image ); ?>" alt="<?php echo esc_attr( $tool->title ); ?>" />
				<?php
			}
			?>

			<p class="envira-tool-excerpt"><?php echo esc_html( $tool->excerpt ); ?></p>

			<?php
			// If the Addon is unlicensed, show the upgrade button.
			if ( ! $is_licensed ) {
				?>
				<div class="envira-tool-active envira-tool-message">
					<div class="interior">
						<div class="envira-tool-upgrade">
							<a href="<?php echo esc_url( $tool->upgrade_url ); ?>" target="_blank" class="button button-primary envira-tool-upgrade-button"  rel="<?php echo esc_attr( $plugin_basename ); ?>">
								<?php esc_html_e( 'Upgrade Now', 'envira-gallery' ); ?>
							</a>
							<span class="spinner envira-gallery-spinner"></span>
						</div>
					</div>
				</div>
				<?php
			} else {
				// Addon is licensed.
				// If the plugin is not installed, display an install message and button.
				if ( ! isset( $installed_plugins[ $plugin_basename ] ) ) {
					?>
					<div class="envira-tool-not-installed envira-tool-message">
						<div class="interior">
							<span class="tool-status"><?php esc_html_e( 'Status:', 'envira-gallery' ); ?><span><?php esc_html_e( 'Not Installed', 'envira-gallery' ); ?></span>
							<div class="envira-tool-action">
								<a class="button button-primary envira-tool-action-button envira-install-tool" href="#" rel="<?php echo esc_url( $tool->url ); ?>">
									<i class="envira-cloud-download"></i>
									<?php esc_html_e( 'Install', 'envira-gallery' ); ?>
								</a>
								<span class="spinner envira-gallery-spinner"></span>
							</div>
						</div>
					</div>
					<?php
				} else {
					// Plugin is installed.
					if ( is_plugin_active( $plugin_basename ) ) {
						// Plugin is active. Display the active message and deactivate button.
						?>
						<div class="envira-tool-active envira-tool-message">
							<div class="interior">
								<span class="tool-status"><?php esc_html_e( 'Status:', 'envira-gallery' ); ?><span><?php esc_html_e( 'Active', 'envira-gallery' ); ?></span>
								<div class="envira-tool-action">
									<a class="button button-primary envira-tool-action-button envira-deactivate-tool" href="#" rel="<?php echo esc_attr( $plugin_basename ); ?>">
										<i class="envira-toggle-on"></i>
										<?php esc_html_e( 'Deactivate', 'envira-gallery' ); ?>
									</a>
									<span class="spinner envira-gallery-spinner"></span>
								</div>
							</div>
						</div>
						<?php
					} else {
						// Plugin is inactivate. Display the inactivate mesage and activate button.
						?>
						<div class="envira-tool-inactive envira-tool-message">
							<div class="interior">
								<span class="tool-status"><?php esc_html_e( 'Status:', 'envira-gallery' ); ?><span><?php esc_html_e( 'Inactive', 'envira-gallery' ); ?></span></span>
								<div class="envira-tool-action">
									<a class="button button-primary envira-tool-action-button envira-activate-tool" href="#" rel="<?php echo esc_attr( $plugin_basename ); ?>">
										<i class="envira-toggle-on"></i>
										<?php esc_html_e( 'Activate', 'envira-gallery' ); ?>
									</a>
									<span class="spinner envira-gallery-spinner"></span>
								</div>
							</div>
						</div>
						<?php
					}
				}
			}
			?>
		</div>
		<?php

	}

	/**
	 * Maybe fixes the broken migration.
	 *
	 * @since 1.7.0
	 *
	 * @return null Return early if not fixing the broken migration
	 */
	public function maybe_fix_migration() {

		// Check if user pressed 'Fix' button and nonce is valid.
		if ( ! isset( $_POST['envira-fix-migration-submit'] ) ) {
			return;
		}
		if ( isset( $_POST['envira-fix-migration-nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['envira-fix-migration-nonce'] ) ), 'envira-fix-migration-nonce' ) ) {
			return;
		}

		// If here, fix potentially broken migration
		// Get WPDB and fix-migration class.
		global $wpdb, $fixed_galleries;

		// Keep count of the number of galleries that get fixed.
		$fixed_galleries = 0;

		if ( '-1' !== $_POST['envira_fix_migration_gallery'] ) { // @codingStandardsIgnoreLine

			// Query to get all Envira CPTs.
			$galleries = new \ WP_Query(
				array(
					'post_type'   => 'envira',
					'post_status' => 'any',
					'p'     	    => intval( $_POST['envira_fix_migration_gallery'] ), // @codingStandardsIgnoreLine
				)
			);

		} else {

			// Query to get all Envira CPTs.
			$galleries = new \ WP_Query(
				array(
					'post_type'      => 'envira',
					'post_status'    => 'any',
					'posts_per_page' => -1,
				)
			);

		}

		// Iterate through galleries.
		if ( $galleries->posts ) {
			foreach ( $galleries->posts as $gallery ) {

				$fixed = false;

				// Attempt to get gallery data.
				$gallery_data = get_post_meta( $gallery->ID, '_eg_gallery_data', true );

				// Skip empty galleries.
				if ( empty( $gallery_data ) ) {
					continue;
				}

				// If gallery data isn't an array, something broke.
				if ( ! is_array( $gallery_data ) ) {
					// Need to fix the broken serialized string for this gallery
					// Get raw string from DB.
					$query            = $wpdb->prepare(
						'	SELECT meta_value
												FROM ' . $wpdb->prefix . 'postmeta
												WHERE post_id = %d
												AND meta_key = %s
												LIMIT 1',
						$gallery->ID,
						'_eg_gallery_data'
					);
					$raw_gallery_data = $wpdb->get_row( $query ); // @codingStandardsIgnoreLine

					// Do the fix, which returns an unserialized array.
					$gallery_data = envira_fix_serialized_string( $raw_gallery_data->meta_value );

					// Check we now have an array of unserialized data.
					if ( ! is_array( $gallery_data ) ) {
						continue;
					}

					// Mark as fixed.
					$fixed = true;
				}

				// Next, check each gallery image has a valid URL
				// Some migrations seem to strip the leading HTTP URL, which causes us problems with thumbnail generation.
				if ( isset( $gallery_data['gallery'] ) ) {
					foreach ( $gallery_data['gallery'] as $id => $item ) {
						// Source.
						if ( isset( $item['src'] ) ) {
							if ( ! empty( $item['src'] ) && ! filter_var( $item['src'], FILTER_VALIDATE_URL ) ) {
								// Image isn't a valid URL - fix.
								$gallery_data['gallery'][ $id ]['src'] = get_bloginfo( 'url' ) . '/' . $item['src'];
								$fixed                                 = true;
							} else {
								// check to make sure the url is correct.
								$base_url = wp_parse_url( $item['src'] );
								if ( empty( $base_url ) || empty( $base_url['host'] ) ) {
									continue;
								}
								$base_url_current = wp_parse_url( get_bloginfo( 'url' ) );
								if ( $base_url['host'] !== $base_url_current['host'] ) {
									$gallery_data['gallery'][ $id ]['src'] = str_replace( $base_url['host'], $base_url_current['host'], $item['src'] );
									$fixed                                 = true;
								}
								// Check scheme (http vs https).
								if ( $base_url['scheme'] !== $base_url_current['scheme'] ) {
									$gallery_data['gallery'][ $id ]['src'] = str_replace( $base_url['scheme'] . '://', $base_url_current['scheme'] . '://', $item['src'] );
									$fixed                                 = true;
								}
							}
						}

						// Link.
						if ( isset( $item['link'] ) ) {
							if ( ! empty( $item['link'] ) && ! filter_var( $item['link'], FILTER_VALIDATE_URL ) ) {
								// Image isn't a valid URL - fix.
								$gallery_data['gallery'][ $id ]['link'] = get_bloginfo( 'url' ) . '/' . $item['link'];
								$fixed                                  = true;
							} else {
								// check to make sure the url is correct.
								$base_url = wp_parse_url( $item['link'] );
								if ( empty( $base_url ) || empty( $base_url['host'] ) ) {
									continue;
								}
								$base_url_current = wp_parse_url( get_bloginfo( 'url' ) );
								// Check hosts.
								if ( $base_url['host'] !== $base_url_current['host'] ) {
									$gallery_data['gallery'][ $id ]['link'] = str_replace( $base_url['host'], $base_url_current['host'], $item['link'] );
									$fixed                                  = true;
								}
								// Check scheme (http vs https).
								if ( $base_url['scheme'] !== $base_url_current['scheme'] ) {
									$gallery_data['gallery'][ $id ]['link'] = str_replace( $base_url['scheme'] . '://', $base_url_current['scheme'] . '://', $item['link'] );
									$fixed                                  = true;
								}
							}
						}

						// Thumbnail.
						if ( isset( $item['thumb'] ) ) {
							if ( ! empty( $item['thumb'] ) && ! filter_var( $item['thumb'], FILTER_VALIDATE_URL ) ) {
								// Thumbnail isn't a valid URL - fix.
								$gallery_data['gallery'][ $id ]['thumb'] = get_bloginfo( 'url' ) . '/' . $item['thumb'];
								$fixed                                   = true;
							} else {
								// check to make sure the url is correct.
								$base_url = wp_parse_url( $item['thumb'] );
								if ( empty( $base_url ) || empty( $base_url['host'] ) ) {
									continue;
								}
								$base_url_current = wp_parse_url( get_bloginfo( 'url' ) );
								if ( $base_url['host'] !== $base_url_current['host'] ) {
									$gallery_data['gallery'][ $id ]['thumb'] = str_replace( $base_url['host'], $base_url_current['host'], $item['thumb'] );
									$fixed                                   = true;
								}
								// Check scheme (http vs https).
								if ( $base_url['scheme'] !== $base_url_current['scheme'] ) {
									$gallery_data['gallery'][ $id ]['thumb'] = str_replace( $base_url['scheme'] . '://', $base_url_current['scheme'] . '://', $item['thumb'] );
									$fixed                                   = true;
								}
							}
						}
					}
				}

				// Finally, store the post meta if a fix was applied.
				if ( $fixed ) {
					update_post_meta( $gallery->ID, '_eg_gallery_data', $gallery_data );
					$fixed_galleries++;
				}
			}
		}

		// Output an admin notice so the user knows what happened.
		add_action( 'envira_gallery_tools_tab_notice', array( $this, 'fixed_migration' ) );

	}

	/**
	 * Outputs a WordPress style notification to tell the user how many galleries were
	 * fixed after running the migration fixer
	 *
	 * @since 1.7.0
	 */
	public function fixed_migration() {
		global $fixed_galleries;

		?>
		<div class="notice updated below-h2">
			<p><strong><?php echo esc_html( $fixed_galleries ); ?> <?php echo esc_html( ' galleries(s) fixed successfully.', 'envira-gallery' ); ?></strong></p>
		</div>
		<?php

	}

	/**
	 * Detects actions a user might be preforming on logs
	 *
	 * @since 1.7.0
	 */
	public function tools_log_actions() {

		if ( empty( $_REQUEST['action'] ) ) { // @codingStandardsIgnoreLine
			return;
		}

		switch ( sanitize_text_field( $_REQUEST['action'] ) ) { // @codingStandardsIgnoreLine
			case 'delete_log':
				if ( ! empty( $_REQUEST['log_id'] ) ) { // @codingStandardsIgnoreLine
					$this->tools_delete_logs( array( $_REQUEST['log_id'] ) ); // @codingStandardsIgnoreLine
				} else {
					return;
				}
				break;
			case 'clear-envira-logs':
				$all_posts = get_posts(
					array(
						'post_type'   => 'envira_log',
						'numberposts' => -1,
					)
				);
				foreach ( $all_posts as $each_post ) {
					wp_delete_post( $each_post->ID, true );
				}
				// Output an admin notice so the user knows what happened.
				add_action( 'envira_gallery_tools_logs_tab_notice', array( $this, 'envira_log_deleted_log' ) );
				break;
			default:
				break;
		}

	}

	/**
	 * Delete logs
	 *
	 * @param array $log_ids Log IDS.
	 * @since 1.7.0
	 */
	public function tools_delete_logs( $log_ids ) {

		if ( empty( $log_ids ) ) {
			return;
		}

		foreach ( $log_ids as $log_id ) {
			wp_delete_post( intval( $log_id ), true );
		}

		// Output an admin notice so the user knows what happened.
		add_action( 'envira_gallery_tools_logs_tab_notice', array( $this, 'envira_log_deleted_log' ) );

	}

	/**
	 * Outputs a WordPress style notification to tell the user how many galleries were
	 * fixed after running the migration fixer
	 *
	 * @since 1.7.0
	 */
	public function envira_log_deleted_log() {
		?>
		<div class="notice updated below-h2">
			<p><strong><?php esc_html_e( 'Log(s) deleted.', 'envira-gallery' ); ?></strong></p>
		</div>
		<?php

	}

}
