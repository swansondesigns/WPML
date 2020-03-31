<?php
/**
 * Welcome class.
 *
 * @since 1.8.1
 *
 * @package Envira_Gallery
 * @author  Envira Gallery Team
 */

namespace Envira\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Welcome Class
 *
 * @since 1.7.0
 *
 * @package Envira_Gallery
 * @author  Envira Gallery Team <support@enviragallery.com>
 */
class Welcome {

	/**
	 * Holds the submenu pagehook.
	 *
	 * @since 1.7.0
	 *
	 * @var string
	 */
	public $hook;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.8.1
	 */
	public function __construct() {

		if ( ( ! defined( 'ENVIRA_WELCOME_SCREEN' ) || false === ENVIRA_WELCOME_SCREEN ) || apply_filters( 'envira_whitelabel', false ) === true ) {
			return;
		}

		// Add custom addons submenu.
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 15 );

		// Add custom CSS class to body.
		add_filter( 'admin_body_class', array( $this, 'admin_welcome_css' ), 15 );

		// Add scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

		// Misc.
		add_action( 'admin_print_scripts', array( $this, 'disable_admin_notices' ) );

	}

	/**
	 * Add custom CSS to admin body tag.
	 *
	 * @since 1.8.1
	 * @param array $classes CSS Classes.
	 * @return array
	 */
	public function admin_welcome_css( $classes ) {

		if ( ! is_admin() ) {
			return;
		}

		$classes .= ' envira-welcome-enabled ';

		return $classes;

	}

	/**
	 * Register and enqueue addons page specific CSS.
	 *
	 * @since 1.8.1
	 */
	public function enqueue_admin_styles() {

		$welcome_pages = array( 'envira-gallery-get-started', 'envira-gallery-welcome', 'envira-gallery-support', 'envira-gallery-changelog' );

		if ( isset( $_GET['post_type'] ) && isset( $_GET['page'] ) && 'envira' === wp_unslash( $_GET['post_type'] ) && in_array( wp_unslash( $_GET['page'] ), $welcome_pages ) ) { // @codingStandardsIgnoreLine

			wp_register_style( ENVIRA_SLUG . '-welcome-style', plugins_url( 'assets/css/welcome.css', ENVIRA_FILE ), array(), ENVIRA_VERSION );
			wp_enqueue_style( ENVIRA_SLUG . '-welcome-style' );

		}

	}

	/**
	 * Making page as clean as possible
	 *
	 * @since 1.8.1
	 */
	public function disable_admin_notices() {

		global $wp_filter;

		$welcome_pages = array( 'envira-gallery-get-started', 'envira-gallery-welcome', 'envira-gallery-support', 'envira-gallery-changelog' );

		if ( isset( $_GET['post_type'] ) && isset( $_GET['page'] ) && 'envira' === wp_unslash( $_GET['post_type'] ) && in_array( wp_unslash( $_GET['page'] ), $welcome_pages ) ) { // @codingStandardsIgnoreLine

			if ( isset( $wp_filter['user_admin_notices'] ) ) {
				unset( $wp_filter['user_admin_notices'] );
			}
			if ( isset( $wp_filter['admin_notices'] ) ) {
				unset( $wp_filter['admin_notices'] );
			}
			if ( isset( $wp_filter['all_admin_notices'] ) ) {
				unset( $wp_filter['all_admin_notices'] );
			}
		}

	}

	/**
	 * Register the Welcome submenu item for Envira.
	 *
	 * @since 1.8.1
	 */
	public function admin_menu() {
		$whitelabel = apply_filters( 'envira_whitelabel', false ) ? '' : __( 'Envira Gallery ', 'envira-gallery' );
		// Register the submenus.
		add_submenu_page(
			'edit.php?post_type=envira',
			$whitelabel . __( 'Get Started', 'envira-gallery' ),
			'<span style="color:#FFA500"> ' . __( 'Get Started', 'envira-gallery' ) . '</span>',
			apply_filters( 'envira_gallery_menu_cap', 'manage_options' ),
			ENVIRA_SLUG . '-get-started',
			array( $this, 'help_page' )
		);

		add_submenu_page(
			'edit.php?post_type=envira',
			$whitelabel . __( 'Welcome', 'envira-gallery' ),
			'<span style="color:#FFA500"> ' . __( 'Welcome', 'envira-gallery' ) . '</span>',
			apply_filters( 'envira_gallery_menu_cap', 'manage_options' ),
			ENVIRA_SLUG . '-welcome',
			array( $this, 'welcome_page' )
		);

		add_submenu_page(
			'edit.php?post_type=envira',
			$whitelabel . __( 'Changelog', 'envira-gallery' ),
			'<span style="color:#FFA500"> ' . __( 'Changelog', 'envira-gallery' ) . '</span>',
			apply_filters( 'envira_gallery_menu_cap', 'manage_options' ),
			ENVIRA_SLUG . '-changelog',
			array( $this, 'changelog_page' )
		);

		add_submenu_page(
			'edit.php?post_type=envira',
			$whitelabel . __( 'Support', 'envira-gallery' ),
			'<span style="color:#FFA500"> ' . __( 'Support', 'envira-gallery' ) . '</span>',
			apply_filters( 'envira_gallery_menu_cap', 'manage_options' ),
			ENVIRA_SLUG . '-support',
			array( $this, 'support_page' )
		);

	}

	/**
	 * Output welcome text and badge for What's New and Credits pages.
	 *
	 * @since 1.8.1
	 */
	public static function welcome_text() {

		// Switch welcome text based on whether this is a new installation or not.
		$welcome_text = ( self::is_new_install() )
			? esc_html( 'Thank you for installing Envira! Envira provides great gallery features for your WordPress site!', 'envira-gallery' )
			: esc_html( 'Thank you for updating! Envira %s has many recent improvements that you will enjoy.', 'envira-gallery' );

		?>
		<?php /* translators: %s: version */ ?>
		<h1 class="welcome-header"><?php printf( esc_html__( 'Welcome to %1$s Envira Gallery %2$s', 'envira-gallery' ), '<span class="envira-leaf"></span>&nbsp;', esc_html( self::display_version() ) ); ?></h1>

		<div class="about-text">
			<?php
			if ( self::is_new_install() ) {
				echo esc_html( $welcome_text );
			} else {
				printf( $welcome_text, self::display_version() ); // @codingStandardsIgnoreLine
			}
			?>
		</div>

		<?php
	}

	/**
	 * Output tab navigation
	 *
	 * @since 2.2.0
	 *
	 * @param string $tab Tab to highlight as active.
	 */
	public static function tab_navigation( $tab = 'whats_new' ) {
		?>

		<h3 class="nav-tab-wrapper">
			<a class="nav-tab
			<?php
			if ( isset( $_GET['page'] ) && 'envira-gallery-welcome' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) : // @codingStandardsIgnoreLine
				?>
				nav-tab-active<?php endif; ?>" href="
				<?php
				echo esc_url(
					admin_url(
						add_query_arg(
							array(
								'post_type' => 'envira',
								'page'      => 'envira-gallery-welcome',
							),
							'edit.php'
						)
					)
				);
				?>
														">
				<?php esc_html_e( 'What&#8217;s New', 'envira-gallery' ); ?>
			</a>
			<a class="nav-tab
			<?php
			if ( isset( $_GET['page'] ) && 'envira-gallery-get-started' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) : // @codingStandardsIgnoreLine
				?>
				nav-tab-active<?php endif; ?>" href="
				<?php
				echo esc_url(
					admin_url(
						add_query_arg(
							array(
								'post_type' => 'envira',
								'page'      => 'envira-gallery-get-started',
							),
							'edit.php'
						)
					)
				);
				?>
														">
				<?php esc_html_e( 'Get Started', 'envira-gallery' ); ?>
			</a>
			<a class="nav-tab
			<?php
			if ( 'envira-gallery-addons' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) : // @codingStandardsIgnoreLine
				?>
				nav-tab-active<?php endif; ?>" href="
				<?php
				echo esc_url(
					admin_url(
						add_query_arg(
							array(
								'post_type' => 'envira',
								'page'      => 'envira-gallery-addons',
							),
							'edit.php'
						)
					)
				);
				?>
														">
				<?php esc_html_e( 'Addons', 'envira-gallery' ); ?>
			</a>
			<a class="nav-tab
			<?php
			if ( isset( $_GET['page'] ) && 'envira-gallery-support' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) : // @codingStandardsIgnoreLine
				?>
				nav-tab-active<?php endif; ?>" href="
				<?php
				echo esc_url(
					admin_url(
						add_query_arg(
							array(
								'post_type' => 'envira',
								'page'      => 'envira-gallery-support',
							),
							'edit.php'
						)
					)
				);
				?>
														">
				<?php esc_html_e( 'FAQ &amp; Support', 'envira-gallery' ); ?>
			</a>
			<a class="nav-tab
			<?php
			if ( isset( $_GET['page'] ) && 'envira-gallery-changelog' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) : // @codingStandardsIgnoreLine
				?>
				nav-tab-active<?php endif; ?>" href="
				<?php
				echo esc_url(
					admin_url(
						add_query_arg(
							array(
								'post_type' => 'envira',
								'page'      => 'envira-gallery-changelog',
							),
							'edit.php'
						)
					)
				);
				?>
														">
				<?php esc_html_e( 'Changelog', 'envira-gallery' ); ?>
			</a>
		</h3>

		<?php
	}

	/**
	 * Output the sidebar.
	 *
	 * @since 1.8.5
	 */
	public function sidebar() {

		global $wp_version;

		?>

			<div class="envira-welcome-sidebar">

				<?php

				if ( version_compare( PHP_VERSION, '5.6.0', '<' ) ) {

					?>

					<div class="sidebox warning php-warning">

					<h4><?php esc_html_e( 'Please Upgrade Your PHP Version!', 'envira-gallery' ); ?></h4>
					<p><?php echo wp_kses( 'Your hosting provider is using PHP <strong>' . PHP_VERSION . '</strong>, an outdated and unsupported version. Soon Envira Gallery will need a minimum of PHP <strong>5.6</strong>.', wp_kses_allowed_html( 'post' ) ); ?></p>
					<a target="_blank" href="https://enviragallery.com/docs/update-php" class="button button-primary">Learn More</a>

					</div>

				<?php } ?>

				<?php

				if ( ! empty( $wp_version ) && version_compare( $wp_version, '4.8', '<' ) ) {

					?>

				<div class="sidebox warning php-warning">

					<h4><?php esc_html_e( 'Please Upgrade Your WordPress Version!', 'envira-gallery' ); ?></h4>
					<p><?php echo wp_kses( 'You are currently using WordPress <strong>' . $wp_version . '</strong>, an outdated version. Soon Envira Gallery will need a minimum of WordPress <strong>4.8</strong>.', wp_kses_allowed_html( 'post' ) ); ?></p>
					<a target="_blank" href="https://enviragallery.com/docs/update-wordpress" class="button button-primary">Learn More</a>

				</div>

				<?php } ?>

				<?php

				if ( class_exists( 'Envira_Gallery' ) && envira_get_license_key() === false ) {

					?>

				<div class="sidebox">
					<form id="envira-settings-verify-key" method="post" action="<?php echo esc_url( admin_url( 'edit.php?post_type=envira&page=envira-gallery-settings' ) ); ?>">
						<h4><?php esc_html_e( 'Activate License Key', 'envira-gallery' ); ?></h4>
						<p><?php esc_html_e( 'License key to enable automatic updates for Envira. License key to enable automatic updates for Envira. ', 'send-system-info' ); ?></p>
						<input type="password" name="envira-license-key" id="envira-settings-key" value="" />
						<?php wp_nonce_field( 'envira-gallery-key-nonce', 'envira-gallery-key-nonce' ); ?>
						<?php submit_button( __( 'Verify Key', 'envira-gallery' ), 'primary', 'envira-gallery-verify-submit', false ); ?>
					</form>
				</div>

					<?php

				}
				?>
				<?php

				$url = 'https://wordpress.org/support/plugin/envira-gallery-lite/reviews/';

				?>
					<div class="sidebox">

							<h4><?php esc_html_e( 'We Need Your Help', 'envira-gallery' ); ?></h4>
							<?php /* translators: %1$s: url, %2$s url */ ?>
							<p><?php echo sprintf( __( 'Please rate <strong>Envira Gallery</strong> <a href="%1$s" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a> on <a href="%2$s" target="_blank">WordPress.org</a> to help us spread the word. Thank you from the Envira Gallery team!', 'envira-gallery' ), esc_url( $url ), esc_url( $url ) ); // @codingStandardsIgnoreLine ?></p>
							<a target="_blank" href="<?php echo esc_url( $url ); ?>" class="button button-primary">Rate It</a>

					</div>
				<div class="sidebox">
					<form action="https://enviragallery.us3.list-manage.com/subscribe/post?u=beaa9426dbd898ac91af5daca&amp;id=2ee2b5572e" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
						<h4><?php esc_html_e( 'Newsletter Signup', 'send-system-info' ); ?></h4>
						<p><?php esc_html_e( 'Get periodic updates, developer notices, special discounts, and invites to our exclusive beta programs.', 'send-system-info' ); ?></p>
						<div class="form-row"><input type="text" value="" name="FNAME" placeholder="Name" id="mce-FNAME"></div>
						<div class="form-row"><input type="email" placeholder="Email" name="EMAIL" required /></div>

						<input type="submit" class="button button-primary" value="Sign Up" />
					</form>
				</div>
				<div class="sidebox">
					<form action="https://enviragallery.com/account/support/" method="post" target="_blank">
						<h4><?php esc_html_e( 'Get Support', 'send-system-info' ); ?></h4>
						<p><?php esc_html_e( 'New to Envira? Our Knowledge Base has over 20 video tutorials, from installing Envira to working with addons and more.', 'send-system-info' ); ?></p>
						<input type="submit" value="Visit Our Support Center" class="button button-primary" />
					</form>
				</div>
			</div>


		<?php
	}

	/**
	 * Output the about screen.
	 *
	 * @since 1.8.5
	 */
	public function welcome_page() {
		?>

		<div class="envira-welcome-wrap envira-welcome">

				<div class="envira-title">

					<?php self::welcome_text(); ?>

				</div>

				<div class="envira-welcome-main">

					<?php self::tab_navigation( __METHOD__ ); ?>

					<div class="envira-welcome-panel">

						<div class="wraps about-wsrap">

							<?php if ( self::is_new_install() ) : ?>


							<?php endif; ?>

							<div class="envira-features-section">

								<h3 class="headline-title"><?php esc_html_e( 'Envira Gallery is the best responsive WordPress gallery plugin.', 'envira-gallery' ); ?></h3>

								<div class="envira-feature">
								<img class="icon" src="https://enviragallery.com/wp-content/uploads/2015/08/drag-drop-icon.png" />
								<h4 class="feature-title"><?php esc_html_e( 'Getting Better And Better!', 'envira-gallery' ); ?></h4>
								<?php /* translators: %1$s: url, %2$s url */ ?>
								<p><?php printf( esc_html__( 'This latest update contains enhancements and improvements - some of which are based on your user feedback! Check out %1$s and %2$s.', 'envira-gallery' ), '<a target="_blank" href="https://enviragallery.com/docs/how-to-configure-your-gallery-settings/#envira-changelog/">our changelog</a>', '<a target="_blank" href="https://enviragallery.com/contact/">tell us what you would to see</a> in future updates' ); ?></p>
								</div>

								<div class="envira-feature opposite">
									<img class="icon" src="<?php echo esc_url( plugins_url( 'assets/images/logos/gutenberg.svg', ENVIRA_FILE ) ); ?>" />
									<h4 class="feature-title">
										<?php esc_html_e( 'Envira Gutenberg Block', 'envira-gallery' ); ?>
										<span class="badge new">NEW</span>
									</h4>
									<p>
										<?php /* translators: %1$s: url, %2$s url */ ?>
										<?php printf( esc_html__( 'Envira is now ready for WordPress 5.0 and it\'s newest editor "%1$s". Find out how to use the new Envira Gutenberg block: %2$s', 'envira-gallery' ), '<a href="https://wordpress.org/gutenberg/" target="_blank">Gutenberg</a>', '<a href="https://enviragallery.com/docs/how-to-use-envira-with-gutenberg/" target="_blank">Read More</a>' ); ?>
										</p>
								</div>

								<div class="envira-feature">
								<img class="icon" src="https://enviragallery.com/wp-content/uploads/2015/10/social-icon.png" />
								<h4 class="feature-title"><?php esc_html_e( 'Social Addon', 'envira-gallery' ); ?> <span class="badge updated">UPDATED</span> </h4>
								<?php /* translators: %1$s: button */ ?>
								<p><?php printf( esc_html__( 'You can now allow users to share your photos via LinkedIn and WhatsApp, in addition to Facebook, Twitter, Google+, Pinterest, and email. %s', 'envira-gallery' ), '<a target="_blank" href="https://enviragallery.com/addons/social-addon/">Read More</a>' ); ?></p>
								</div>

								<div class="envira-feature opposite">
								<img class="icon" src="https://enviragallery.com/wp-content/uploads/2015/10/videos-icon.png" />
								<h4 class="feature-title"><?php esc_html_e( 'Video Addon', 'envira-gallery' ); ?> <span class="badge updated">UPDATED</span> </h4>
								<?php /* translators: %1$s: button */ ?>
								<p><?php printf( esc_html__( 'Now add videos from Facebook, Instagram, Twitch, VideoPress, Vimeo, Wistia, and Dailymotion to your galleries. Expanded self-hosted and YouTube features are now supported too! %s', 'envira-gallery' ), '<a target="_blank" href="https://enviragallery.com/announcing-new-video-integrations/">Read More</a>' ); ?></p>
								</div>

							</div>

							<div class="envira-recent-section">

								<h3 class="title"><?php esc_html_e( 'Recent Enhancements:', 'envira-gallery' ); ?></h3>
								<div class="envira-recent envirathree-column">
								<div class="enviracolumn">
										<h4 class="title"><?php esc_html_e( 'Improved CSS Editor', 'envira-gallery' ); ?></h4>
										<?php /* translators: %1$s: link */ ?>
										<p><?php printf( esc_html__( 'The %s now has an improved editor to write CSS code, including the ability to detect errors as you type them!', 'envira-gallery' ), '<a target="_blank" href="https://enviragallery.com/addons/css-addon/">CSS Addon</a>' ); ?></p>
								</div>
								<div class="enviracolumn">
										<h4 class="title"><?php esc_html_e( 'Pull Latest Galleries Dynamically', 'envira-gallery' ); ?></h4>
										<?php /* translators: %1$s: link */ ?>
										<p><?php printf( esc_html__( 'Additional support now in the %s for pulling galleries was added to this addon, including ability to pull X latest plus much more.', 'envira-gallery' ), '<a target="_blank"  href="https://enviragallery.com/addons/dynamic-addon/">Dynamic Addon</a>' ); ?></p>
								</div>

								<div class="enviracolumn">
										<h4 class="title"><?php esc_html_e( 'Customizable Password Protection Text', 'envira-gallery' ); ?></h4>
										<?php /* translators: %1$s: link */ ?>
										<p><?php printf( esc_html__( 'Update the text (via backend setting or WordPress filter) shown to visitors in the %s when they need to enter a password.', 'envira-gallery' ), '<a target="_blank"  href="https://enviragallery.com/addons/password-protection-addon/">Password Protection</a>' ); ?></p>
								</div>
								</div>

								<br/>

								<div class="envira-recent envirathree-column">
								<div class="enviracolumn">
										<h4 class="title"><?php esc_html_e( 'Zoom Cursor Options', 'envira-gallery' ); ?></h4>
										<?php /* translators: %1$s: link */ ?>
										<p><?php printf( esc_html__( 'Users updating to the latest version of the %s will be able to toggle cursor visibility in the lightbox.', 'envira-gallery' ), '<a target="_blank" href="https://enviragallery.com/addons/zoom-addon/">Zoom Addon</a>' ); ?></p>
								</div>
								<div class="enviracolumn">
										<h4 class="title"><?php esc_html_e( 'Additional Mobile Settings', 'envira-gallery' ); ?></h4>
										<p><?php printf( esc_html__( 'You now have the ability to enable or disable display of a gallery title and caption on mobile devices.', 'envira-gallery' ) ); ?></p>
								</div>
								<div class="enviracolumn">
										<h4 class="title"><?php esc_html_e( 'Third-Party Theme &amp; Plugin Compatibility', 'envira-gallery' ); ?></h4>
										<p><?php printf( esc_html__( 'Each version of Envira Gallery brings better workings with various popular WordPress plugins and themes.', 'envira-gallery' ) ); ?></p>
								</div>
								</div>

							</div>

							<?php $this->envira_posts(); ?>

							<?php $this->envira_assets(); ?>

						</div>

					</div>

				</div>

				<?php $this->sidebar(); ?>

		</div> <!-- wrap -->

		<?php
	}

	/**
	 * Output the support screen.
	 *
	 * @since 1.8.1
	 */
	public function support_page() {
		?>

		<div class="envira-welcome-wrap envira-support">

			<div class="envira-title">

				<?php self::welcome_text(); ?>

			</div>

			<?php $this->sidebar(); ?>

			<div class="envira-support-main">

				<?php self::tab_navigation( __METHOD__ ); ?>

				<div class="envira-support-panel">

					<div class="wraps about-wsrap">

						<h3 class="headline-title"><?php esc_html_e( 'Got A Question? We Can Help!', 'envira-gallery' ); ?></h3>

						<div class="envira-recent-section">

							<h3 class="title"><?php esc_html_e( 'Functionality:', 'envira-gallery' ); ?></h3>

							<article class="docs">

								<ul>
									<li>
									<a href="https://enviragallery.com/docs/how-to-add-animated-gifs-to-your-gallery/" title="How to Add Animated GIFs to Your Gallery">
									How to Add Animated GIFs to Your Gallery							</a>
									</li>
									<li>
									<a href="https://enviragallery.com/docs/add-facebook-application-id/" title="How to Add Your Facebook Application ID to the Social Addon">
									How to Add Your Facebook Application ID to the Social Addon							</a>
									</li>
									<li>
									<a href="https://enviragallery.com/docs/how-to-bulk-edit-gallery-images/" title="How to Bulk Edit Gallery Images">
									How to Bulk Edit Gallery Images							</a>
									</li>
									<li>
									<a href="https://enviragallery.com/docs/justified-image-grid-gallery/" title="How to Create a Justified Image Grid Gallery">
									How to Create a Justified Image Grid Gallery							</a>
									</li>
									<li>
									<a href="https://enviragallery.com/docs/import-export-galleries/" title="How to Import and Export Galleries">
									How to Import and Export Galleries							</a>
									</li>
									<li>
									<a href="https://enviragallery.com/docs/supersize-addon/" title="How to Supersize Lightbox Images">
									How to Supersize Lightbox Images							</a>
									</li>
									<li>
									<a href="https://enviragallery.com/docs/how-to-use-the-bulk-apply-settings/" title="How to Use the Bulk Apply Settings">
									How to Use the Bulk Apply Settings							</a>
									</li>
									<li>
									<a href="https://enviragallery.com/docs/add-envira-gallery-widget/" title="How to Use the Envira Gallery Widget">
									How to Use the Envira Gallery Widget							</a>
									</li>
									<li>
									<a href="https://enviragallery.com/docs/standalone-addon/" title="How to Use the Standalone Feature in Envira Gallery">
									How to Use the Standalone Feature in Envira Gallery							</a>
									</li>
									<li>
									<a href="https://enviragallery.com/docs/display-tag-based-dynamic-gallery/" title="Display a Tag Based Dynamic Gallery">
									Display a Tag Based Dynamic Gallery							</a>
									</li>
									<li>
									<a href="https://enviragallery.com/docs/display-image-thumbnails-random-order/" title="Display Image Thumbnails in a Random Order">
									Display Image Thumbnails in a Random Order							</a>
									</li>
									<li>
									<a href="https://enviragallery.com/docs/lightbox-arrows-inside-outside/" title="Display Lightbox Nav Arrows Inside/Outside of Image">
									Display Lightbox Nav Arrows Inside/Outside of Image							</a>
									</li>
									<li>
									<a href="https://enviragallery.com/docs/how-to-turn-off-the-lightbox-for-envira/" title="How to Turn Off the Lightbox for Envira">
									How to Turn Off the Lightbox for Envira							</a>
									</li>
									<li>
									<a href="https://enviragallery.com/docs/using-a-wordpress-user-role/" title="Using A WordPress User Role">
									Using A WordPress User Role							</a>
									</li>
									<li>
									<a href="https://enviragallery.com/docs/envira-gallery-lightbox-options/" title="Envira Gallery Lightbox Options">
									Envira Gallery Lightbox Options							</a>
									</li>
									<li>
									<a href="https://enviragallery.com/docs/using-envira-galleries-and-page-builder-tabbed-content/" title="Using Envira Galleries and Page Builder Tabbed Content">
									Using Envira Galleries and Page Builder Tabbed Content							</a>
									</li>
									<li>
									<a href="https://enviragallery.com/docs/how-to-enable-rtl-support/" title="How to Enable RTL Support">
									How to Enable RTL Support							</a>
									</li>
									<li>
									<a href="https://enviragallery.com/docs/how-to-preview-envira-galleries/" title="How to Preview Envira Galleries">
									How to Preview Envira Galleries							</a>
									</li>
									<li>
									<a href="https://enviragallery.com/docs/enable-shortcodes-in-gallery-descriptions/" title="Enable Shortcodes in Gallery Descriptions">
									Enable Shortcodes in Gallery Descriptions							</a>
									</li>
								</ul>
								</article>

								<div style="margin: 20px auto 0 auto;">
									<a  target="_blank" href="https://enviragallery.com/categories/docs/functionality/" class="button button-primary">See More Guides On Functionality</a>
								</div>

								<h3 class="title" style="margin-top: 30px;"><?php esc_html_e( 'Addons:', 'envira-gallery' ); ?></h3>

								<article class="docs">
									<ul>
										<li>
										<a href="https://enviragallery.com/docs/how-to-add-animated-gifs-to-your-gallery/" title="How to Add Animated GIFs to Your Gallery">
										How to Add Animated GIFs to Your Gallery							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/add-facebook-application-id/" title="How to Add Your Facebook Application ID to the Social Addon">
										How to Add Your Facebook Application ID to the Social Addon							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/how-to-bulk-edit-gallery-images/" title="How to Bulk Edit Gallery Images">
										How to Bulk Edit Gallery Images							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/justified-image-grid-gallery/" title="How to Create a Justified Image Grid Gallery">
										How to Create a Justified Image Grid Gallery							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/import-export-galleries/" title="How to Import and Export Galleries">
										How to Import and Export Galleries							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/supersize-addon/" title="How to Supersize Lightbox Images">
										How to Supersize Lightbox Images							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/how-to-use-the-bulk-apply-settings/" title="How to Use the Bulk Apply Settings">
										How to Use the Bulk Apply Settings							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/add-envira-gallery-widget/" title="How to Use the Envira Gallery Widget">
										How to Use the Envira Gallery Widget							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/standalone-addon/" title="How to Use the Standalone Feature in Envira Gallery">
										How to Use the Standalone Feature in Envira Gallery							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/display-tag-based-dynamic-gallery/" title="Display a Tag Based Dynamic Gallery">
										Display a Tag Based Dynamic Gallery							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/display-image-thumbnails-random-order/" title="Display Image Thumbnails in a Random Order">
										Display Image Thumbnails in a Random Order							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/lightbox-arrows-inside-outside/" title="Display Lightbox Nav Arrows Inside/Outside of Image">
										Display Lightbox Nav Arrows Inside/Outside of Image							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/how-to-turn-off-the-lightbox-for-envira/" title="How to Turn Off the Lightbox for Envira">
										How to Turn Off the Lightbox for Envira							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/using-a-wordpress-user-role/" title="Using A WordPress User Role">
										Using A WordPress User Role							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/envira-gallery-lightbox-options/" title="Envira Gallery Lightbox Options">
										Envira Gallery Lightbox Options							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/using-envira-galleries-and-page-builder-tabbed-content/" title="Using Envira Galleries and Page Builder Tabbed Content">
										Using Envira Galleries and Page Builder Tabbed Content							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/how-to-enable-rtl-support/" title="How to Enable RTL Support">
										How to Enable RTL Support							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/how-to-preview-envira-galleries/" title="How to Preview Envira Galleries">
										How to Preview Envira Galleries							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/enable-shortcodes-in-gallery-descriptions/" title="Enable Shortcodes in Gallery Descriptions">
										Enable Shortcodes in Gallery Descriptions							</a>
										</li>
									</ul>
								</article>

								<div style="margin: 20px auto 0 auto;">
									<a  target="_blank" href="https://enviragallery.com/categories/docs/addons/" class="button button-primary">See More Guides On Addons</a>
								</div>

								<h3 class="title" style="margin-top: 30px;"><?php esc_html_e( 'Styling:', 'envira-gallery' ); ?></h3>

								<article class="docs">
									<ul>
										<li>
										<a href="https://enviragallery.com/docs/how-to-add-animated-gifs-to-your-gallery/" title="How to Add Animated GIFs to Your Gallery">
										How to Add Animated GIFs to Your Gallery							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/add-facebook-application-id/" title="How to Add Your Facebook Application ID to the Social Addon">
										How to Add Your Facebook Application ID to the Social Addon							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/how-to-bulk-edit-gallery-images/" title="How to Bulk Edit Gallery Images">
										How to Bulk Edit Gallery Images							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/justified-image-grid-gallery/" title="How to Create a Justified Image Grid Gallery">
										How to Create a Justified Image Grid Gallery							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/import-export-galleries/" title="How to Import and Export Galleries">
										How to Import and Export Galleries							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/supersize-addon/" title="How to Supersize Lightbox Images">
										How to Supersize Lightbox Images							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/how-to-use-the-bulk-apply-settings/" title="How to Use the Bulk Apply Settings">
										How to Use the Bulk Apply Settings							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/add-envira-gallery-widget/" title="How to Use the Envira Gallery Widget">
										How to Use the Envira Gallery Widget							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/standalone-addon/" title="How to Use the Standalone Feature in Envira Gallery">
										How to Use the Standalone Feature in Envira Gallery							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/display-tag-based-dynamic-gallery/" title="Display a Tag Based Dynamic Gallery">
										Display a Tag Based Dynamic Gallery							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/display-image-thumbnails-random-order/" title="Display Image Thumbnails in a Random Order">
										Display Image Thumbnails in a Random Order							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/lightbox-arrows-inside-outside/" title="Display Lightbox Nav Arrows Inside/Outside of Image">
										Display Lightbox Nav Arrows Inside/Outside of Image							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/how-to-turn-off-the-lightbox-for-envira/" title="How to Turn Off the Lightbox for Envira">
										How to Turn Off the Lightbox for Envira							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/using-a-wordpress-user-role/" title="Using A WordPress User Role">
										Using A WordPress User Role							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/envira-gallery-lightbox-options/" title="Envira Gallery Lightbox Options">
										Envira Gallery Lightbox Options							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/using-envira-galleries-and-page-builder-tabbed-content/" title="Using Envira Galleries and Page Builder Tabbed Content">
										Using Envira Galleries and Page Builder Tabbed Content							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/how-to-enable-rtl-support/" title="How to Enable RTL Support">
										How to Enable RTL Support							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/how-to-preview-envira-galleries/" title="How to Preview Envira Galleries">
										How to Preview Envira Galleries							</a>
										</li>
										<li>
										<a href="https://enviragallery.com/docs/enable-shortcodes-in-gallery-descriptions/" title="Enable Shortcodes in Gallery Descriptions">
										Enable Shortcodes in Gallery Descriptions							</a>
										</li>
									</ul>
								</article>

								<div style="margin: 20px auto 0 auto;">
									<a target="_blank" href="https://enviragallery.com/categories/docs/styling/" class="button button-primary">See More Guides On Styling</a>
								</div>

								</div>

								<hr/>

				</div>

			</div>

		</div> <!-- wrap -->

		<?php
	}

	/**
	 * Output the about screen.
	 *
	 * @since 1.8.1
	 */
	public function help_page() {
		?>

		<div class="envira-welcome-wrap envira-help">

			<div class="envira-title">

				<?php self::welcome_text(); ?>

			</div>

			<?php $this->sidebar(); ?>

			<div class="envira-get-started-main">

				<?php self::tab_navigation( __METHOD__ ); ?>

				<div class="envira-get-started-panel">

					<div class="wraps about-wsrap">

						<div class="envira-features-section">

						<h3 class="headline-title"><?php esc_html_e( 'New To Envira? It\'s Easy To Get Started!', 'envira-gallery' ); ?>

						<?php

						$galleries = _envira_get_galleries();

						if ( count( $galleries ) === 0 ) {
							$text = 'Create Your First Gallery';
						} else {
							$text = 'Create Another Gallery';
						}

						?>

						<div class="envira-headline-button">
							<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=envira' ) ); ?>" class="button button-primary">
							<?php echo esc_html( $text ); ?></a>
						</div>

						</h3>

						<div class="envira-feature">
								<span class="envira-leaf envira-big-icon"></span>
								<h4 class="feature-title"><?php esc_html_e( 'How to Verify Your Envira License', 'envira-gallery' ); ?></h4>
								<p><?php printf( esc_html( 'We\'ll walk you through each step on how to verify your Envira license. %s', 'envira-gallery' ), '<a target="_blank" href="https://enviragallery.com/docs/verify-envira-license/">Read More</a>' ); ?></p>
						</div>

						<div class="envira-feature opposite">
								<span class="envira-leaf envira-big-icon"></span>
								<h4 class="feature-title"><?php esc_html_e( 'How to Activate Addons', 'envira-gallery' ); ?></h4>
								<p><?php printf( esc_html( 'Once your license is verified, it\'s now time to activate your addons. %s', 'envira-gallery' ), '<a target="_blank" href="https://enviragallery.com/docs/activate-addons/">Read More</a>' ); ?></p>
						</div>

						<div class="envira-feature">
								<span class="envira-leaf envira-big-icon"></span>
								<h4 class="feature-title"><?php esc_html_e( 'Creating Your First Envira Gallery', 'envira-gallery' ); ?></h4>
								<p><?php printf( esc_html( 'In this article, we\'ll help you create your very first Envira gallery. %s', 'envira-gallery' ), '<a target="_blank" href="https://enviragallery.com/docs/creating-first-envira-gallery/">Read More</a>' ); ?></p>

						</div>

						<div class="envira-feature opposite">
								<span class="envira-leaf envira-big-icon"></span>
								<h4 class="feature-title"><?php esc_html_e( 'Debugging Envira', 'envira-gallery' ); ?></h4>
								<p><?php printf( esc_html( 'Having trouble seeing your gallery? Take a look at some of our steps on troubleshooting your gallery. %s', 'envira-gallery' ), '<a target="_blank" href="https://enviragallery.com/docs/debugging-envira/">Read More</a>' ); ?></p>
								</p>
						</div>

					</div>

					<div class="envira-posts">

						<h3 class="title"><?php esc_html_e( 'Video Tutorials:', 'envira-gallery' ); ?></h3>

						<div class="envira-recent envirathree-column">
							<div class="enviracolumn">
								<iframe width="100%" src="https://www.youtube.com/embed/sE5ZEfT7388" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
								<h4 class="title"><?php esc_html_e( 'How to Create a Filterable Portfolio in WordPress', 'envira-gallery' ); ?></h4>
								<?php /* Translators: %s */ ?>
								<p><?php printf( esc_html__( 'Many photographers have large portfolios that have a variety of their favorite photos and some users want to filter down to the types they want to see. In this video we\'ll show you how to create a filterable portfolio. %s', 'envira-gallery' ), '<a target="_blank" href="https://www.youtube.com/watch?v=sE5ZEfT7388">Read More</a>' ); ?></p>
							</div>
							<div class="enviracolumn">
							<iframe width="100%" src="https://www.youtube.com/embed/S_4LgeQdb-I" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
								<h4 class="title"><?php esc_html_e( 'Envira Gallery Instagram Addon', 'envira-gallery' ); ?></h4>
								<?php /* Translators: %s */ ?>
								<p><?php printf( esc_html__( 'The Instagram addon allows you to dynamically add images from your Instagram account to your galleries. %s', 'envira-gallery' ), '<a target="_blank" href="https://www.youtube.com/watch?v=S_4LgeQdb-I">Read More</a>' ); ?></p>
							</div>

							<div class="enviracolumn">
							<iframe width="100%" src="https://www.youtube.com/embed/uGpz4YVb5UY" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
								<h4 class="title"><?php esc_html_e( 'Envira Gallery EXIF Addon', 'envira-gallery' ); ?></h4>
								<?php /* Translators: %s */ ?>
								<p><?php printf( esc_html__( 'Add your EXIF metadata in your galleries and lightbox images, including support for all the common EXIF data, including camera make/model, aperture and shutter speed. %s', 'envira-gallery' ), '<a  target="_blank" href="https://www.youtube.com/watch?v=uGpz4YVb5UY">Read More</a>' ); ?></p>
							</div>
						</div>

					</div>

					<hr/>

					<?php $this->envira_posts(); ?>

					<?php $this->envira_assets(); ?>

				</div>

			</div>

		</div>

		</div> <!-- wrap -->


		<?php
	}

	/**
	 * Output the changelog screen.
	 *
	 * @since 1.8.1
	 */
	public function changelog_page() {

		?>

		<div class="envira-welcome-wrap envira-changelog">

			<div class="envira-title">

				<?php self::welcome_text(); ?>

			</div>

			<?php $this->sidebar(); ?>

			<div class="envira-welcome-main changelog-main">

				<?php self::tab_navigation( __METHOD__ ); ?>

				<div class="envira-welcome-panel">

					<div class="wraps about-wsrap">

						<?php $this->return_changelog(); ?>

					</div>

				</div>

			</div>

		</div> <!-- wrap -->


		<?php
	}

	/**
	 * Changelog display.
	 *
	 * @since 1.8.1
	 */
	public function return_changelog() {
		?>

			<div id="changelog-envira-gallery">

			<h3>1.8.4.5 (10-31-2018)</h3>
				<ul>
				<li>Fix: Issue w/ standalone function and older versions of the Album addon.</li>
				</ul>
				<h3>1.8.4.4 (10-31-2018)</h3>
				<ul>
				<li>Added: When Lightbox is not in infinite loop, first previous and last next arrows no longer appear.</li>
				<li>Fix: Gallery title shows in gallery toolbar instead of page title.</li>
				<li>Fix: Minor bug fixes.</li>
				</ul>
				<h3>1.8.4.3 (10-18-2018)</h3>
				<ul>
				<li>Fix: Resolved issues for installs using older PHP versions.</li>
				</ul>
				<h3>1.8.4.2 (10-16-2018)</h3>
				<ul>
				<li>Enhancement: New "Hide Thumbnails On Opening Lightbox" option.</li>
				<li>Enhancement: New "Enable Title/Caption Below Image" option for column layouts on Mobile Gallery Settings tab.</li>
				<li>Enhancement: Improved Import/Export buttons</li>
				<li>Enhancement: Allow search by single or multiple post IDs for galleries in gallery admin list</li>
				<li>Added: Unofficial support for PDFs in Lightboxes</li>
				<li>Added: Importing gallery should automatically apply the gallery title to the new post</li>
				<li>Added: envira_import_gallery_post_data and envira_import_gallery_metadata WordPress filters for developers to alter imported data</li>
				<li>Added: envirabox_theme_image_counter WordPress filter to allow filtering of Image XX of XX text</li>
				<li>Added: Adjustment of margins and padding for non-supersized Lightbox images</li>
				<li>Fix: Tweaked admin UI and CSS on mobile and narrow width devices.</li>
				<li>Fix: If Gallery Theme Addon deactivated, revert galleries using themes to legacy.</li>
				<li>Fix: Various bug fixes, minor code tweaks, output to resolve W3C reported errors, and PHP compatability adjustments</li>
				</ul>
				<h3>1.8.4.1 (09-05-2018)</h3>
				<ul>
				<li>Hotfix: Fix false positive caused by WordFence.</li>
				</ul>
				<h3>1.8.4 (08-22-2018)</h3>
				<ul>
				<li>Added: Random image gallery widget.</li>
				<li>Added: Limit images option for standard gallery widget.</li>
				<li>Added: Filter for lightbox "idle time".</li>
				<li>Enhancement: Added support for featured images.</li>
				<li>Fix: Random galleries not random in certain circumstances.</li>
				<li>Fix: Minor bug fixes.</li>
				</ul>
				<h3>1.8.3 (05-30-2018)</h3>
				<ul>
				<li>Fix: Lightbox thumbnail custom sizes</li>
				<li>Fix: Multiple Envira link fixes</li>
				<li>Fix: Captions and titles missing from automatic layout</li>
				<li>Fix: Toolbar navigation </li>
				<li>Fix: Supersize appearance with no thumbnails</li>
				<li>Fix: Minor bug fixes</li>
				</ul>
				<h3>1.8.2.1 (05-01-2018)</h3>
				<ul>
				<li>Fix: Fix missing lightbox captions.</li>
				</ul>
				<h3>1.8.2 (05-01-2018)</h3>
				<ul>
				<li>Enhancement: Clear Envira Cache on update</li>
				<li>Enhancement: Using WordPress Importer adjust meta to new URL</li>
				<li>Enhancement: Image Dimensions set by default, option has been removed</li>
				<li>Fix: Random sorting not opening proper lightbox image</li>
				<li>Fix: Double images in Lightbox for Images with links in Captions</li>
				<li>Fix: Minor Bug fixes.</li>
				</ul>
				<h3>1.8.1 (04-20-2018)</h3>
				<ul>
				<li>Fix: Addressed performance issues.</li>
				<li>Fix: Resolved issue regarding using slug instead of id in envira shortcode in some situations.</li>
				<li>Fix: Resolved JS error w/ Yoast and Divi</li>
				<li>Fix: Minor Bug fixes.</li>
				</ul>

				<h3>1.8.0 (04-10-2018)</h3>
				<ul>
					<li>Added: Fancybox3</li>
					<li>Added: Link shortcode to open linkbox [envira_link id=GALLERYID]Link Text[/envira_link]</li>
					<li>Added: Filters to override or create Lightbox Themes Directly in themes</li>
					<li>Added: Gallery Scripts Optimization</li>
					<li>Fix: Cases that prevent cropping.</li>
					<li>Fix: Minor Bug fixes.</li>
					<li>Depricated: Justified Gallery Themes</li>
				</ul>

				<h3>1.7.3 (11-07-2017)</h3>
				<ul>
					<li>Fix: Tweaks to updater class to prevent multiple calls</li>
					<li>Fix: Minor bug fixes</li>
				</ul>
				<h3>1.7.1 (11-07-2017)</h3>
				<ul>
					<li>Added: UI spinner when adding photos to gallery</li>
					<li>Fix: Cases where thumbnails have double URLS</li>
					<li>Fix: Compatibility with 3rd Party builders</li>
					<li>Fix: Random sorting</li>
					<li>Fix: Retina Cropping</li>
					<li>Fix: Prevent Caption Line breaks not </li>
					<li>Fix: RTL admin styling</li>
					<li>Fix: Minor bug fixes</li>
				</ul>

				<h3>1.7.0.2 (10-23-2017)</h3>
				<ul>
					<li>Fix: Compatibility with X Theme</li>
					<li>Fix: Compatibility with Yoast SEO</li>
					<li>Added: ENVIRA_DEBUG to System Info</li>
				</ul>

				<h3>1.7.0.1 (10-19-2017)</h3>
				<ul>
					<li>Added: Cropping Position Option</li>
					<li>Fix: Minor bug fixes</li>
				</ul>

				<h3>1.7.0 (10-18-2017)</h3>
				<ul>
					<li>Added: Cropping is now done in a background process</li>
					<li>Added: Fragment Caching - gallery output is now cached utilizing fragment caching</li>
					<li>Added: Debug Screen</li>
					<li>Added: Sorting moved to config screen</li>
					<li>Added: Support for Search in Widget</li>
					<li>Added: French Translation</li>
					<li>Added: Russian Translation</li>
					<li>Fix: Url field linking to external</li>
					<li>Fix: Url on lightbox image using proper url</li>
					<li>Fix: Raw HTML in captions</li>
					<li>Fix: Minor bug fixes</li>
				</ul>
			</div>

		<?php
	}


	/**
	 * Returns a common row for posts from enviragallery.com.
	 *
	 * @since 1.8.5
	 */
	public function envira_posts() {
		?>

			<div class="envira-posts">

				<h3 class="title"><?php esc_html_e( 'Helpful Articles For Beginners:', 'envira-gallery' ); ?></h3>
				<div class="envira-recent envirathree-column">


					<div class="enviracolumn">
						<img class="post-image" src="https://enviragallery.com/wp-content/uploads/2018/10/Image-SEO-for-WordPress.png" />
						<h4 class="title"><?php esc_html_e( 'How to Optimize SEO Images for WordPress', 'envira-gallery' ); ?></h4>
						<?php /* Translators: %s */ ?>
						<p><?php printf( esc_html__( 'Thinking of designing an eCommerce website where you can sell your photos or building a WordPress portfolio where you can show off your photography skills? It’s important to think carefully about your SEO strategy. %s', 'envira-gallery' ), '<a href="https://enviragallery.com/optimize-seo-images-wordpress/" target="_blank">Read More</a>' ); ?></p>
					</div>

					<div class="enviracolumn">
						<img class="post-image" src="https://enviragallery.com/wp-content/uploads/2017/08/move-photography-site-from-flickr-to-wordpress.jpg" />
						<h4 class="title"><?php esc_html_e( 'How to Move Your Photography Site from Flickr to WordPress', 'envira-gallery' ); ?></h4>
						<?php /* Translators: %s */ ?>
						<p><?php printf( esc_html__( 'You may know that your photos aren’t safe at Flickr, and you should upload them to your self hosted site. In this tutorial, we will share how to move your photography site from Flickr to WordPress. %s', 'envira-gallery' ), '<a href="https://enviragallery.com/how-to-move-your-photography-site-from-flickr-to-wordpress/" target="_blank">Read More</a>' ); ?></p>
					</div>

					<div class="enviracolumn">
						<img class="post-image" src="https://enviragallery.com/wp-content/uploads/2018/09/vidoe-gallery.jpg" />
						<h4 class="title"><?php esc_html_e( 'Announcing New Video Integrations', 'envira-gallery' ); ?></h4>
						<?php /* Translators: %s */ ?>
						<p><?php printf( esc_html__( 'We’re pleased to introduce our expanded video gallery support options for Envira Gallery 1.8.1. More video platform integrations allow you to add more video sources for your galleries. %s', 'envira-gallery' ), '<a href="https://enviragallery.com/announcing-new-video-integrations/" target="_blank">Read More</a>' ); ?></p>
					</div>


				</div>

			</div>

		<?php
	}


	/**
	 * Returns a common footer
	 *
	 * @since 1.8.5
	 */
	public function envira_assets() {
		?>

		<div class="envira-assets">
			<p>
				<?php esc_html_e( 'Learn more:', 'envira-gallery' ); ?>&nbsp;<a href="https://enviragallery.com/blog/"><?php esc_html_e( 'Blog', 'envira-gallery' ); ?></a>
				&bullet; <a href="https://enviragallery.com/contact/"><?php esc_html_e( 'Support', 'envira-gallery' ); ?></a>
				&bullet; <a href="https://enviragallery.com/docs/"><?php esc_html_e( 'Documentation', 'envira-gallery' ); ?></a>
			<?php /* &bullet; <a href="https://enviragallery.com/dev/"><?php _ex( 'Development Blog', 'About screen, link to development blog', 'envira-gallery' ); ?></a> */ ?>
			</p>

			<p>
				<?php esc_html_e( 'Social:', 'envira-gallery' ); ?>

				<a target="_blank" href="https://twitter.com/enviragallery/"><?php esc_html_e( 'Twitter', 'envira-gallery' ); ?></a>

					&bullet;

				<a target="_blank" href="https://facebook.com/enviragallery/"><?php esc_html_e( 'Facebook', 'envira-gallery' ); ?></a>

					&bullet;

				<a target="_blank" href="https://www.instagram.com/enviragallery/"><?php esc_html_e( 'Instagram', 'envira-gallery' ); ?></a>

					&bullet;

				<a target="_blank" href="https://www.youtube.com/user/enviragallery/"><?php esc_html_e( 'YouTube', 'envira-gallery' ); ?></a>

			</p>

			<p>

				<?php esc_html_e( 'Also by us: ', 'envira-gallery' ); ?>

				<a target="_blank" href="http://soliloquywp.com"><?php esc_html_e( 'Soliloquy Slider', 'envira-gallery' ); ?></a>

			</p>

		</div>

		<?php
	}

	/**
	 * Return true/false based on whether a query argument is set.
	 *
	 * @return bool
	 */
	public static function is_new_install() {

		if ( get_transient( '_envira_is_new_install' ) ) {
			delete_transient( '_envira_is_new_install' );
			return true;
		}

		if ( isset( $_GET['is_new_install'] ) && 'true' === strtolower( sanitize_text_field( wp_unslash( $_GET['is_new_install'] ) ) ) ) { // @codingStandardsIgnoreLine
			return true;
		} elseif ( isset( $_GET['is_new_install'] ) ) { // @codingStandardsIgnoreLine
			return false;
		}

	}

	/**
	 * Return a user-friendly version-number string, for use in translations.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	public static function display_version() {

		return ENVIRA_VERSION;

	}


}
