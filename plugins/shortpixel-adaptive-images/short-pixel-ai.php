<?php
/*
 * Plugin Name: ShortPixel Adaptive Images
 * Plugin URI: https://shortpixel.com/
 * Description: Display properly sized, smart cropped and optimized images on your website. Images are processed on the fly and served from our CDN.
 * Version: 1.3.4
 * Author: ShortPixel
 * Author URI: https://shortpixel.com
 * Text Domain: shortpixel-adaptive-images
 */
! defined( 'ABSPATH' ) and exit;

define( 'SHORTPIXEL_AI_VERSION', '1.3.4' );
define('SHORTPIXEL_AI_PLUGIN_FILE', __FILE__);

if(!defined('SHORTPIXEL_AI_DEBUG')) {
    define('SHORTPIXEL_AI_DEBUG', isset($_GET['SHORTPIXEL_AI_DEBUG']));
}
if(SHORTPIXEL_AI_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    $old_error_handler = set_error_handler("shortPixelDebugErrorHandler");
}

function shortPixelDebugErrorHandler($errno, $errstr, $errfile, $errline)
{
    $type = 'UNKNOWN';
    switch ($errno) {
        case E_USER_ERROR:
            $type = 'ERROR';
            break;
        case E_USER_WARNING:
            $type = 'WARNING';
            break;
        case E_USER_NOTICE:
            $type = 'NOTICE';
            break;
    }
    $upload_dir = wp_upload_dir();
    $logPath = $upload_dir['basedir'] . '/' . 'shortpixel_ai_log';
    file_put_contents($logPath, '[' . date('Y-m-d H:i:s') . "] Got $type $errstr in $errfile at $errline " . "\n", FILE_APPEND);
}

if(!class_exists('ShortPixelAI') ) {
	require_once(__DIR__ . '/inc/url-tools.class.php');
    require_once(__DIR__ . '/inc/logger.class.php');
    require_once(__DIR__ . '/inc/css-parser.class.php');
    require_once(__DIR__ . '/inc/regex-parser.class.php');
    //require_once(__DIR__ . '/inc/dom-parser.class.php');
    require_once(__DIR__ . '/inc/simple-dom-parser.class.php');
    //require_once(__DIR__ . '/lib/simple_html_dom.php');
    require_once(__DIR__ . '/inc/short-pixel-ai.class.php');

    register_activation_hook( __FILE__, array( 'ShortPixelAI', 'activate' ) );
    register_deactivation_hook( __FILE__, array( 'ShortPixelAI', 'deactivate' ) );

    //init the singleton
	ShortPixelAI::instance(__FILE__);
}
