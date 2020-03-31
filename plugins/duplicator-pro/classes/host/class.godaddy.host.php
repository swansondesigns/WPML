<?php
/**
 * godaddy custom hosting class
 *
 * Standard: PSR-2
 *
 * @package SC\DUPX\DB
 * @link http://www.php-fig.org/psr/psr-2/
 *
 */
defined('ABSPATH') || defined('DUPXABSPATH') || exit;

class DUP_PRO_GoDaddy_Host implements DUP_PRO_Host_interface
{

    public static function getIdentifier()
    {
        return DUP_PRO_Custom_Host_Manager::HOST_GODADDY;
    }

    public function isHosting()
    {
       // gethostname() only works on PHP 5.3+
       if(DUP_PRO_U::$on_php_53_plus) {
            $hostName                 = gethostname();
            $goDaddyHostNameSuffix    = '.secureserver.net';
            $lenGoDaddyHostNameSuffix = strlen($goDaddyHostNameSuffix);
            return apply_filters('duplicator_pro_godaddy_host_check', (false !== $hostName && substr($hostName, - $lenGoDaddyHostNameSuffix) === $goDaddyHostNameSuffix));
       } else {
           return apply_filters('duplicator_pro_godaddy_host_check', false);
       }
    }

    public function init()
    {
        add_filter('duplicator_pro_default_archive_build_mode', array('DUP_PRO_GoDaddy_Host', 'defaultArchiveBuildMode'), 10, 1);
    }

    public static function defaultArchiveBuildMode($archiveBuildMode)
    {
        return DUP_PRO_Archive_Build_Mode::DupArchive;
    }
}