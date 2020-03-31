<?php
// Pull the logo from the customizer and use it on the login page.
function sd_login_logo() {
    $custom_logo_id = get_theme_mod( 'custom_logo' );
    $image = wp_get_attachment_image_src( $custom_logo_id , 'full' );
    $image_url = $image[0];
    $image_width = $image[1];
    $image_height = $image[2];

    if (!$image)
        return;

    ?>
    <style type="text/css">
        #login h1 a {
            background-image: url(<?php echo $image_url ?>);
            background-size: auto;
            width: <?php echo $image_width ?>px;
            height: <?php echo $image_height ?>px;
        }
    </style>
<?php }
add_action( 'login_enqueue_scripts', 'sd_login_logo' );

// Replace style-login.css with the name of your custom CSS file
function sd_custom_login_stylesheet() {
wp_enqueue_style( 'custom-login', get_stylesheet_directory_uri() . '/css/style.login.css' );
}
add_action( 'login_enqueue_scripts', 'sd_custom_login_stylesheet' );

// Remove the Wordpress logo from the top admin bar
function sd_admin_bar_remove_logo() {
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu( 'wp-logo' );
}
add_action( 'wp_before_admin_bar_render', 'sd_admin_bar_remove_logo', 0 );
