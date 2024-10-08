<?php

namespace Lynxlab\ADA\Module\DebugBar;

use Lynxlab\ADA\Main\AMA\MultiPort;

class ADAAdminerHelper
{
    private const ADMINER_VERSION = '4.8.1';

    /**
     * Checks if adminer is available.
     *
     * @return bool
     */
    public static function hasAdminer()
    {
        return in_array($_SESSION['sess_userObj']->getType(), [AMA_TYPE_ADMIN, AMA_TYPE_SWITCHER]) &&
            is_readable(MODULES_DEBUGBAR_PATH . '/adminer/adminer.php') &&
            is_readable(MODULES_DEBUGBAR_PATH . '/adaadminer.php');
    }

    /**
     * Builds the POST data to be used when loading adminer.
     *
     * @param string $client
     * @return string[]
     * @throws \Error
     */
    public static function getPOSTData($client)
    {
        $infoDB = static::getInfoDB();
        if (array_key_exists($client, $infoDB)) {
            return [
                'driver' => 'server',
                'server' => $infoDB[$client]['host'],
                'db' => $infoDB[$client]['path'],
            ];
        }
        return [];
    }

    /**
     * Gets the databases names.
     * Used by ADAAdminerPlugin::databases
     *
     * @return array
     * @throws \Error
     */
    public static function getDBNames()
    {
        return array_values(
            array_map(
                fn ($el) => $el['path'],
                static::getInfoDB()
            )
        );
    }

    /**
     * Gets the credential for the passed client.
     * Used by ADAAdminerPlugin::credentials
     *
     * @param string $client
     * @return array
     */
    public static function getCredentials($client)
    {
        $infoDB = static::getInfoDB();
        if (array_key_exists($client, $infoDB)) {
            return [
                $infoDB[$client]['host'],
                $infoDB[$client]['user'],
                $infoDB[$client]['pass'],
            ];
        }
        return [];
    }

    /**
     * Gets ada client names.
     *
     * @return array
     */
    public static function getADAClients()
    {
        return array_keys(self::getInfoDB());
    }

    /**
     * Get all clients DSN strings
     *
     * @return array indexes are clients pointers.
     */
    private static function getInfoDB()
    {
        $dsnArr = ['common' => array_map(
            fn ($el) => trim($el, '/'),
            parse_url(ADA_COMMON_DB_TYPE . '://' . ADA_COMMON_DB_USER . ':'
                . ADA_COMMON_DB_PASS . '@' . ADA_COMMON_DB_HOST . '/'
                . ADA_COMMON_DB_NAME)
        )];

        foreach (array_keys(MultiPort::getTestersPointersAndIds()) as $pointer) {
            $dsnArr[$pointer] = array_map(
                fn ($el) => trim($el, '/'),
                parse_url(MultiPort::getDSN($pointer))
            );
        }
        return $dsnArr;
    }

    public static function installAdminer()
    {
        // download adminer.
        // and turn off error_reporting.
        $fp = fopen('./adminer/adminer.php', 'w+');
        $ch = curl_init('https://github.com/vrana/adminer/releases/download/v' . self::ADMINER_VERSION . '/adminer-' . self::ADMINER_VERSION . '-mysql.php');
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        //disable ssl cert verification to allow copying files from HTTPS
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // get curl response
        $contents = curl_exec($ch);
        if ($contents !== false) {
            fwrite($fp, preg_replace('/(error_reporting)\(\d+\);/', '$1(0);', $contents));
        }
        curl_close($ch);
        fclose($fp);

        // make dir and download adminer plugin base class.
        if (!is_dir('./adminer/plugins')) {
            mkdir('./adminer/plugins');
        }

        $fp = fopen('./adminer/plugins/plugin.php', 'w+');
        $ch = curl_init('https://raw.github.com/vrana/adminer/master/plugins/plugin.php');
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        //disable ssl cert verification to allow copying files from HTTPS
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // get curl response
        $contents = curl_exec($ch);
        if ($contents !== false) {
            fwrite($fp, $contents);
        }
        curl_close($ch);
        fclose($fp);

        if (php_sapi_name() === 'cli') {
            echo "adminer and adminer/plugin patched and installed/updated!\nYou may need to fix file permissions!\n";
        }
    }
}
