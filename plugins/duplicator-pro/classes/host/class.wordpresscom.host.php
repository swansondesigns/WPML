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

class DUP_PRO_WordpressCom_Host implements DUP_PRO_Host_interface
{

    public static function getIdentifier()
    {
        return DUP_PRO_Custom_Host_Manager::HOST_WORDPRESSCOM;
    }

    public function isHosting()
    {
        return defined('WPCOMSH_VERSION');
    }

    public function init()
    {
    }
}
