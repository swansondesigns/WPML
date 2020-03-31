<?php
defined('ABSPATH') || defined('DUPXABSPATH') || exit;

class DUPX_RemoveRedundantData {

    private static function loadWP($wp_root_dir) {
        require_once($wp_root_dir.'wp-load.php');
        if (!class_exists( 'WP_Privacy_Policy_Content' ) ) {
			require_once($wp_root_dir . 'wp-admin/includes/misc.php');
		}
        if (!function_exists('request_filesystem_credentials')) {
            require_once($wp_root_dir.'wp-admin/includes/file.php');
        }
        $GLOBALS['wpdb']->show_errors(false);
    }

    private static function getWPRootDir($wp_content_dir, $ac) {
        return str_replace($ac->relative_content_dir, '', $wp_content_dir);
    }

    public static function isMultiSite($ac) {
        return ($ac->mu_mode > 0 && count($ac->subsites) > 0 && is_multisite());
    }

    /**
     * Uninstall a single plugin.
     *
     * Calls the uninstall hook, if it is available.
     *
     * @param string $plugin Path to the main plugin file from plugins directory.
     * @return true True if a plugin's uninstall.php file has been found and included.
     */
    private static function uninstallPlugin($plugin) {
        $file = plugin_basename($plugin);

        $uninstallable_plugins = (array) get_option('uninstall_plugins');

        /**
         * Fires in uninstall_plugin() immediately before the plugin is uninstalled.
         *
         * @since 4.5.0
         *
         * @param string $plugin                Path to the main plugin file from plugins directory.
         * @param array  $uninstallable_plugins Uninstallable plugins.
         */
        do_action('pre_uninstall_plugin', $plugin, $uninstallable_plugins);

        if (file_exists( WP_PLUGIN_DIR . '/' . dirname($file) . '/uninstall.php')) {
            if ( isset( $uninstallable_plugins[$file] ) ) {
                unset($uninstallable_plugins[$file]);
                update_option('uninstall_plugins', $uninstallable_plugins);
            }
            unset($uninstallable_plugins);

            if (defined('WP_UNINSTALL_PLUGIN')) {
                $already_defined_uninstall_const = true;
            } else {
                define('WP_UNINSTALL_PLUGIN', $file);
                $already_defined_uninstall_const = false;
            }

            wp_register_plugin_realpath( WP_PLUGIN_DIR . '/' . $file );

            if ($already_defined_uninstall_const) {
                $uninstall_file_content  = file_get_contents(WP_PLUGIN_DIR . '/' . dirname($file) . '/uninstall.php');
                $prohibited_codes = array(
                    'dirname( WP_UNINSTALL_PLUGIN )',
                    'dirname(WP_UNINSTALL_PLUGIN )',
                    'dirname( WP_UNINSTALL_PLUGIN)',
                    'dirname(WP_UNINSTALL_PLUGIN)',

                    'WP_UNINSTALL_PLUGIN =',
                    'WP_UNINSTALL_PLUGIN !=',
                    'WP_UNINSTALL_PLUGIN=',
                    'WP_UNINSTALL_PLUGIN!=',

                    '= WP_UNINSTALL_PLUGIN',
                    '!= WP_UNINSTALL_PLUGIN',
                    '=WP_UNINSTALL_PLUGIN=',
                    '!=WP_UNINSTALL_PLUGIN',

                    'current_user_can',
                );
                foreach ($prohibited_codes as $prohibited_code) {
                    if (false !== stripos($uninstall_file_content, $prohibited_code)) {
                        DUPX_Log::info("Can't include uninstall.php file of the ".$plugin." because prohibited code found");
                        return false;
                    }
                }
            }
            include(WP_PLUGIN_DIR . '/' . dirname($file) . '/uninstall.php');
            return true;
        } elseif (isset($uninstallable_plugins[$file])) {
            $callable = $uninstallable_plugins[$file];
            unset($uninstallable_plugins[$file]);
            update_option('uninstall_plugins', $uninstallable_plugins);
            unset($uninstallable_plugins);

            wp_register_plugin_realpath( WP_PLUGIN_DIR . '/' . $file);
            include_once(WP_PLUGIN_DIR . '/' . $file );

            add_action("uninstall_{$file}", $callable);

            /**
             * Fires in uninstall_plugin() once the plugin has been uninstalled.
             *
             * The action concatenates the 'uninstall_' prefix with the basename of the
             * plugin passed to uninstall_plugin() to create a dynamically-named action.
             *
             * @since 2.7.0
             */
            do_action( "uninstall_{$file}" );
            // Extra
            return true;
        // Extra
        } else { // The plugin was never activated so no need to call uninstallation hook
            return true;
        }
    }

    public static function deleteRedundantPlugins($wp_content_dir, $ac, $subsite_id = 0) {
        $nManager = DUPX_NOTICE_MANAGER::getInstance();
        DUPX_Log::info("\n--------------------\n".
            "DELETING INACTIVE PLUGINS");

        $wp_root_dir = self::getWPRootDir($wp_content_dir, $ac);
        define('WP_USE_THEMES', false);
        self::loadWP($wp_root_dir);
        if (!function_exists('get_plugins')) {
            require_once $wp_root_dir.'wp-admin/includes/plugin.php';
        }

        $all_plugins = array_keys(get_plugins());
        $is_mu = self::isMultiSite($ac);
        if ($is_mu) {
            $site_ids = DUPX_MU::getAllSiteIdsinWP();
            $active_plugins = array_keys(get_site_option('active_sitewide_plugins'));
            foreach ($site_ids as $site_id) {
                $site_plugins = get_blog_option($site_id, 'active_plugins', array());
                $site_plugins = array_values($site_plugins);
                if (!empty($site_plugins)) {
                    $active_plugins = array_merge($active_plugins, $site_plugins);
                }
            }
            $active_plugins = array_unique($active_plugins);
		} else {
            $active_plugins = false;
            if ($subsite_id > 0) {
                $active_plugins = get_option('dupx_retain_plugins', false);
            }
            if (false === $active_plugins) {
                $active_plugins = get_option('active_plugins', array());
            }
        }
        if (!in_array('duplicator-pro/duplicator-pro.php', $active_plugins)) {
            $active_plugins[] =  'duplicator-pro/duplicator-pro.php';
        }
        $active_plugins = array_unique($active_plugins);

        $temp_uninstallable_plugins = array_diff($all_plugins, $active_plugins);

        // Put jetpack plugin first
        if (in_array('jetpack/jetpack.php', $temp_uninstallable_plugins)) {
            $uninstallable_plugins = array('jetpack/jetpack.php');
            $jetpack_key = array_search('jetpack/jetpack.php', $temp_uninstallable_plugins);
            unset($temp_uninstallable_plugins[$jetpack_key]);
            $temp_uninstallable_plugins = array_values($temp_uninstallable_plugins);
            if (!empty($temp_uninstallable_plugins)) {
                $uninstallable_plugins = array_merge($uninstallable_plugins, $temp_uninstallable_plugins);
            }
        } else {
            $uninstallable_plugins = $temp_uninstallable_plugins;
        }

        foreach ($all_plugins as $c_plugin) {
            DUPX_Log::info('PLUGIN: '.DUPX_Log::varToString($c_plugin).' '.(in_array($c_plugin, $active_plugins) ? '[ACTIVE]' : '').(in_array($c_plugin, $uninstallable_plugins) ? '[UNINSTALLABLE]' : ''));
        }
        DUPX_Log::info("\n");

        $globa_uninstall_success = true;

        if (!empty($uninstallable_plugins)) {
            DUPX_Log::info("START DELETION LOOP", DUPX_Log::LV_DETAILED);
            $plugin_dir_path = $wp_root_dir.$ac->relative_plugins_dir;
            foreach ($uninstallable_plugins as $uninstallable_plugin) {
                try {
                    if (stripos($uninstallable_plugin, '/')) {
                        $temp_path =  $plugin_dir_path.'/'.$uninstallable_plugin;
                        $full_path =  dirname($temp_path);
                    } else {
                        $full_path =  $plugin_dir_path.'/'.$uninstallable_plugin;
                    }

                    DUPX_Log::info("REMOVE PLUGIN ".DUPX_Log::varToString($uninstallable_plugin)."[".(is_dir($full_path) ? 'DIR' : 'FILE')."] PATH: ".DUPX_Log::varToString($full_path), DUPX_Log::varToString($full_path));

                    // UNISTALL PLUGIN IF IS ACTIVE
                    $level = error_reporting(E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR);
                    if (DupProSnapLibUtil::wp_is_ini_value_changeable('display_errors')) {
                        @ini_set('display_errors', 0);
                    }
                    DUPX_Log::info("UNISTALL PLUGIN ".DUPX_Log::varToString($uninstallable_plugin), DUPX_Log::LV_DEBUG);
                    $unistall_result = self::uninstallPlugin($uninstallable_plugin);

                    if ($unistall_result) {
                        DUPX_Log::info("UNISTALL PLUGIN ".DUPX_Log::varToString($uninstallable_plugin).' DONE');
                    } else {
                        DUPX_Log::info("UNISTALL PLUGIN ".DUPX_Log::varToString($uninstallable_plugin).' FAILED');
                    }
                    error_reporting($level);

                    // DELETE PLUGIN IF UNISTALL IS OK
                    if ($unistall_result) {
                        if (is_dir($full_path)) {
                            $delete_result = DupProSnapLibIOU::rrmdir($full_path);
                        } else if(is_file($full_path)) {
                            $delete_result = unlink($full_path);
                        }

                        if ($delete_result) {
                            DUPX_Log::info("DELETE PLUGIN ".DUPX_Log::varToString($uninstallable_plugin).' DONE');
                        } else {
                            DUPX_Log::info("DELETE PLUGIN ".DUPX_Log::varToString($uninstallable_plugin).' FAILED');
                        }
                    } else {
                        $delete_result = false;
                    }

                    if (!$delete_result) {
                        $globa_uninstall_success = false;
                        $errorMsg = "**ERROR** The Inactive plugin ".$uninstallable_plugin." can't be deleted from your server's file storage";
                        DUPX_Log::info($errorMsg);
                        $nManager->addFinalReportNotice(array(
                            'shortMsg' => $errorMsg,
                            'level' => DUPX_NOTICE_ITEM::HARD_WARNING,
                            'longMsg' => 'Please delete the path '.$full_path.' manually',
                            'sections' => 'general'
                        ));
                    }
                } catch (Exception $e) {
                    $globa_uninstall_success = false;
                    $errorMsg = "**ERROR** The Inactive plugin ".$uninstallable_plugin." can't be deleted";
                    $longMsg  = 'Please delete the plugin '.$uninstallable_plugin.' manually'.PHP_EOL.
                        'Exception message: '.$e->getMessage().PHP_EOL.
                        'Trace: '.$e->getTraceAsString();
                    DUPX_Log::info($errorMsg);
                    $nManager->addFinalReportNotice(array(
                        'shortMsg' => $errorMsg,
                        'level' => DUPX_NOTICE_ITEM::HARD_WARNING,
                        'longMsg' => $longMsg,
                        'longMsgMode' => DUPX_NOTICE_ITEM::MSG_MODE_PRE,
                        'sections' => 'general'
                    ));
                } catch (Error $e) {
                    $globa_uninstall_success = false;
                    $errorMsg = "**ERROR** The Inactive plugin ".$uninstallable_plugin." can't be deleted";
                    $longMsg  = 'Please delete the plugin '.$uninstallable_plugin.' manually'.PHP_EOL.
                        'Exception message: '.$e->getMessage().PHP_EOL.
                        'Trace: '.$e->getTraceAsString();
                    DUPX_Log::info($errorMsg);
                    $nManager->addFinalReportNotice(array(
                        'shortMsg' => $errorMsg,
                        'level' => DUPX_NOTICE_ITEM::HARD_WARNING,
                        'longMsg' => $longMsg,
                        'longMsgMode' => DUPX_NOTICE_ITEM::MSG_MODE_PRE,
                        'sections' => 'general'
                    ));
                }
            }
            if (true === $globa_uninstall_success) {
                DUPX_Log::info("Inactive plugins are deleted successfully", DUPX_Log::LV_DETAILED);
            } else {
                $errorMsg = "**ERROR** Inactive plugins deletion failed";
            }
        }
        if (!$is_mu && $subsite_id > 0) {
            delete_option('dupx_retain_plugins');
        }
        $nManager->saveNotices();
    }

    private static function appendParentThemes($active_themes) {
        // For adding parent themes of child themes
        foreach ($active_themes as $active_theme) {
            $theme_obj = wp_get_theme($active_theme);
            if ($theme_obj->stylesheet  != $theme_obj->template) {
                $active_themes[] = $theme_obj->template;
            }
        }
        return $active_themes;
    }

    public static function deleteRedundantThemes($wp_content_dir, $ac, $subsite_id) {
        DUPX_Log::info("\n--------------------\n".
            "DELETING INACTIVE THEMES");

        $wp_root_dir = self::getWPRootDir($wp_content_dir, $ac);
        self::loadWP($wp_root_dir);

        if (!function_exists('delete_theme')) {
            require_once $wp_root_dir.'wp-admin/includes/theme.php';
        }

        $is_mu = self::isMultiSite($ac);
        if ($is_mu) {
            $active_themes = get_site_option('allowedthemes', array());
            $active_themes = array_keys($active_themes);
            $active_themes = self::appendParentThemes($active_themes);
        } else {
            if ($subsite_id > 0) {
                $active_themes = get_option('dupx_retain_themes');
                $active_themes = self::appendParentThemes($active_themes);
            } else {
                $stylesheet = get_stylesheet();
                DUPX_Log::info("STYLESHEET IS ".DUPX_Log::varToString($stylesheet));
                $template = get_template();
                DUPX_Log::info("TEMPLATE IS ".DUPX_Log::varToString($template));

                $active_themes = array(
                    $stylesheet,
                    $template,
                );
            }
        }

        // We shouldn't remove WP_DEFAULT_THEME defined theme
        $wpConfigPath	= "{$GLOBALS['DUPX_ROOT']}/wp-config.php";
        require_once($GLOBALS['DUPX_INIT'].'/lib/config/class.wp.config.tranformer.php');
        $config_transformer = new WPConfigTransformer($wpConfigPath);
        if ($config_transformer->exists('constant', 'WP_DEFAULT_THEME')) {
            $default_theme = $config_transformer->get_value('constant', 'WP_DEFAULT_THEME');
            if (is_string($default_theme)) {
                $active_themes[] = $default_theme;
                DUPX_Log::info("WP_DEFAULT_THEME: ".$default_theme);
            }
        }

        $active_themes = array_unique($active_themes);
        $all_themes = wp_get_themes();
        $all_themes = array_keys($all_themes);
        $uninstallable_themes = array_diff($all_themes, $active_themes);

        foreach ($all_themes as $cTheme) {
            DUPX_Log::info('THEME: '.DUPX_Log::varToString($cTheme).' '.(in_array($cTheme,$active_themes) ? '[ACTIVE]' : '').(in_array($cTheme,$uninstallable_themes) ? '[UNINSTALLABLE]' : ''));
        }
        DUPX_Log::info("\n");

        if (!empty($uninstallable_themes)) {
            foreach ($uninstallable_themes as $uninstallable_theme) {
                if (delete_theme($uninstallable_theme, '')) {
                    DUPX_Log::info('THEME: '.DUPX_Log::varToString($uninstallable_theme).' DELETED');
                } else {
                    $nManager = DUPX_NOTICE_MANAGER::getInstance();
                    $errorMsg = "**ERROR** The Inactive theme ".$uninstallable_theme." deletion failed";
                    DUPX_Log::info($errorMsg);
                    $nManager->addFinalReportNotice(array(
                        'shortMsg' => $errorMsg,
                        'level' => DUPX_NOTICE_ITEM::HARD_WARNING,
                        'longMsg' => 'Please delete the path '.$full_path.' manually',
                        'sections' => 'general'
                    ));
                }
            }
        }

        if (!$is_mu && $subsite_id > 0) {
            delete_option('dupx_retain_themes');
        }
    }
}
