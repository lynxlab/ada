<?php

/**
 * AMA_DB DB abstraction layer.
 *
 * Manages switching between PDO and any other
 * possible layers that may be used in the future.
 *
 * Requires flags to be set in  file ada_config.php:
 *  flag PDO_DB
 *  ...other flags, one for each data type connection
 *
 *  flag DB_ABS_LAYER set to the proper flag you are going to use.
 *
 * @package     db
 * @author      Vito Modena <vito@lynxlab.com>
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        ama_pdo
 * @version     0.2
 */

namespace Lynxlab\ADA\Main\AMA;

use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMAError;
use Lynxlab\ADA\Main\AMA\AMAPDOWrapper;
use PDOException;

/**
 * Provides an abstraction layer for the db
 */
class AMADB
{
    /**
     * check if passed object is an instance of PDOException and returns true if success
     *
     * @param mixed $data
     * @param string code not used, kept for compatibility reasons
     * @return boolean true if is error
     *
     * @access public
     */
    public static function isError($data, $code = null)
    {
        return ($data instanceof PDOException || $data instanceof AMAError || $data instanceof ADAError);
    }

    /**
     * instantiate the proper class and estabilishes the connection
     *
     * @param mixed $dsn the string "data source name" as requested by PEAR (kept for compatibility)
     * @param array $options an associative array of option names and values as requested by selected ABS_LAYER
     * @return AMAPDOWrapper|\PDOException a new DB object
     *
     * @access public
     */
    public static function &connect($dsn, $options = false)
    {
        // check if DB_ABS_LAYER is not defined in ama_config.php and defaults to PDO
        if (!defined('DB_ABS_LAYER')) {
            define('DB_ABS_LAYER', PDO_DB);
        }

        switch (DB_ABS_LAYER) {
            case PDO_DB:
            default:
                $wrapper = new AMAPDOWrapper($dsn, $options);
                if (self::isError($wrapper->connectionObject())) {
                    // if there were errors, $wrapper->connection_object is a PDOException
                    // so we return it
                    $retval = $wrapper->connectionObject();
                    return $retval;
                }
                break;
                /**
                 * Pls handle other databases connection here by adding more cases
                 */
        }
        return $wrapper;
    }
}
