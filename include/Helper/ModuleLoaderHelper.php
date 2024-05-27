<?php

/**
 * @package     module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2020, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

/**
 * Module loader helper
 *
 * base class used to load ada/wisp modules
 *
 * Loaded module configuration file is searched in the following order:
 * 1. ROOT_DIR / config / MODULENAME / CONFIG FILE
 * 2. ROOT_DIR / modules / MODULENAME / config / CONFIG FILE
 *
 */

namespace Lynxlab\ADA\Main\Helper;

use Exception;

class ModuleLoaderHelper
{
    /**
     * default config directory name
     *
     * @var string
     */
    protected const CONFIGDIR = 'config';

    /**
     * default config file name
     *
     * @var string
     */
    protected const DEFAULTFILE = 'config.inc.php';

    protected const PREFIX = 'MODULES_';

    /**
     * look for the passed module configuration file
     * if no $configfile is passed, the defaultfile is use (i.e. config.inc.php)
     *
     * @param string $modulename
     * @param string|null $moduledir
     * @param string $configfile
     * @return string|null
     */
    protected static function getModuleIncludeConfig($modulename, $moduledir = null, $configfile = self::DEFAULTFILE)
    {
        $noconfig = [
            'codeman',
        ];
        $checks = [
            ROOT_DIR . DIRECTORY_SEPARATOR . self::CONFIGDIR . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $modulename . DIRECTORY_SEPARATOR . $configfile,
            MODULES_DIR . DIRECTORY_SEPARATOR . $modulename . DIRECTORY_SEPARATOR . self::CONFIGDIR . DIRECTORY_SEPARATOR . $configfile,
        ];
        if (!is_null($moduledir)) {
            array_push(
                $checks,
                ROOT_DIR . DIRECTORY_SEPARATOR . self::CONFIGDIR . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $moduledir . DIRECTORY_SEPARATOR . $configfile,
                MODULES_DIR . DIRECTORY_SEPARATOR . $moduledir . DIRECTORY_SEPARATOR . self::CONFIGDIR . DIRECTORY_SEPARATOR . $configfile
            );
        }
        foreach (array_unique($checks) as $check) {
            if (file_exists($check)) {
                return $check;
            }
        }
        return (in_array($modulename, $noconfig) ? '' : null);
    }

    /**
     * load condition for the module. if this module returns false the module will not be loaded
     *
     * @param string $modulename
     * @param string $moduledir
     * @return bool
     */
    protected static function checkModuleLoadCondtion($modulename, $moduledir)
    {
        switch ($modulename) {
            case 'test':
                return
                    file_exists(MODULES_DIR . DIRECTORY_SEPARATOR . $moduledir . '/index.php') &&
                    file_exists(MODULES_DIR . DIRECTORY_SEPARATOR . $moduledir . '/edit_test.php') &&
                    file_exists(MODULES_DIR . DIRECTORY_SEPARATOR . $moduledir . '/tutor.php');
                break;
            case 'login':
                return
                    file_exists(MODULES_DIR . DIRECTORY_SEPARATOR . $moduledir . '/include/AbstractLogin.php');
                break;
            case 'apps':
            case 'classbudget':
            case 'classagenda':
            case 'classroom':
            case 'codeman':
            case 'servicecomplete':
            case 'slideimport':
                return
                    file_exists(MODULES_DIR . DIRECTORY_SEPARATOR . $moduledir . '/index.php');
                break;
            default:
                return null !== static::getModuleIncludeConfig($modulename, $moduledir);
                break;
        }
    }

    /**
     * loads a single module
     *
     * @param string $modulename
     * @param string|null $moduledir
     * @param bool $forcedisable
     * @return void
     */
    public static function loadModule($modulename, $moduledir = null, $forcedisable = false)
    {
        if (!static::isLoaded($modulename)) {
            if (is_null($moduledir)) {
                $moduledir = $modulename;
            }
            $basedefine = strtoupper(self::PREFIX . $modulename);
            $defval = false;
            if (!defined($basedefine) && !$forcedisable && static::checkModuleLoadCondtion($modulename, $moduledir)) {
                $defval = static::requireAutoloader($modulename, $moduledir);
            }
            /*
             * $basedefine should be defined in the required module
             * config file. If it's not, define it here.
             */
            if (!defined($basedefine)) {
                define($basedefine, $defval);
            }
        }
    }

    /**
     * Check if a module has been loaded.
     *
     * Accecpts as parameter the full or short module name (e.g. 'test' and 'MODULES_TEST').
     * The check is done case-insensitive.
     *
     * @param string $module
     * @return boolean
     */
    public static function isLoaded($module)
    {
        if (!str_starts_with($module, self::PREFIX)) {
            $module = strtoupper(self::PREFIX . $module);
        }
        return defined($module) && constant($module);
    }

    /**
     * loads multiple modules at once, passed as array of 'name' and 'dirname'
     *
     * @param array $modules
     * @return void
     */
    public static function loadModuleFromArray($modules)
    {
        if (is_array($modules)) {
            foreach ($modules as $module) {
                if (array_key_exists('name', $module)) {
                    if (!array_key_exists('dirname', $module)) {
                        $module['dirname'] = $module['name'];
                    }
                    if (!array_key_exists('forcedisable', $module)) {
                        $module['forcedisable'] = false;
                    }
                    self::loadModule($module['name'], $module['dirname'], $module['forcedisable']);
                }
            }
        }
    }

    private static function requireAutoloader($modulename, $moduledir)
    {
        try {
            if (!@include_once(MODULES_DIR . DIRECTORY_SEPARATOR . $moduledir . '/vendor/autoload.php')) {
                // @ - to suppress warnings,
                throw new Exception(
                    json_encode([
                        'header' => $modulename . ' module will not work because autoload file cannot be found!',
                        'message' => 'Please run <code>composer install</code> in the module subdir',
                    ])
                );
            } else {
                $modConfig = static::getModuleIncludeConfig($modulename, $moduledir);
                if (strlen($modConfig) > 0) {
                    // require module own config file.
                    require_once($modConfig);
                }
                return true;
            }
        } catch (Exception $e) {
            $text = json_decode($e->getMessage(), true);
            // populating $_GET['message'] is a dirty hack to force the error message to appear in the home page at least
            if (!isset($_GET['message'])) {
                $_GET['message'] = '';
            }
            $_GET['message'] .= '<div class="ui icon error message"><i class="ban circle icon"></i><div class="content">';
            if (array_key_exists('header', $text) && strlen($text['header']) > 0) {
                $_GET['message'] .= '<div class="header">' . $text['header'] . '</div>';
            }
            if (array_key_exists('message', $text) && strlen($text['message']) > 0) {
                $_GET['message'] .= '<p>' . $text['message'] . '</p>';
            }
            $_GET['message'] .= '</div></div>';
        }
        return false;
    }

    /**
     * returns an array of sql files to be imported
     * in the common db and each provider db if multiprovider eq 0
     *
     * @return array
     */
    public static function inBothIfNonMulti()
    {
        // put here filenames to be imported in the common db and each provider db if multiprovider eq 0
        $inBothIfNonMulti = [];

        return $inBothIfNonMulti;
    }


    /**
     * returns an array of sql files to be imported
     * in the common db if multiprovider eq 1
     *
     * @return array
     */
    public static function inCommonIfMulti()
    {
        // put here filenames to be imported in the common db if multiprovider eq 1
        $inCommonIfMulti = ['ada_gdpr_policy.sql', 'ada_login_module.sql'];

        return $inCommonIfMulti;
    }

    /**
     * returns an array of sql files to be imported
     * ALWAYS in the common db
     *
     * @return array
     */
    public static function inCommon()
    {
        // put here filenames to be ALWAYS imported in the common db
        $inCommon = ['ada_apps_module.sql',  'ada_secretquestion_module.sql', 'ada_impexport_module.sql'];

        return $inCommon;
    }
}
