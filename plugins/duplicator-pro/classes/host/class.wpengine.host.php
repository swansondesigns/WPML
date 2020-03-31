<?php
/**
 * wpengine custom hosting class
 *
 * Standard: PSR-2
 *
 * @package SC\DUPX\DB
 * @link http://www.php-fig.org/psr/psr-2/
 *
 */
defined('ABSPATH') || defined('DUPXABSPATH') || exit;

class DUP_PRO_WPEngine_Host implements DUP_PRO_Host_interface
{

    public static function getIdentifier()
    {
        return DUP_PRO_Custom_Host_Manager::HOST_WPENGINE;
    }

    public function isHosting()
    {
        return apply_filters('duplicator_pro_wp_engine_host_check', file_exists(WPMU_PLUGIN_DIR.'/wpengine-common/mu-plugin.php'));
    }

    public function init()
    {
        add_filter('duplicator_pro_installer_file_path', array('DUP_PRO_WPEngine_Host', 'installerFilePath'), 10, 1);
    }

    public static function installerFilePath($path)
    {
        $path_info = pathinfo($path);
        $newPath   = $path;
        if ('php' == $path_info['extension']) {
            $newPath = substr_replace($path, '.txt', -4);
        }
        return $newPath;
    }
}