<?php

use Composer\Console\Application;
use Lynxlab\ADA\Admin\AdminHelper;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADAGuest;
use Lynxlab\ADA\Main\Utilities;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * INSTALLATION SCRIPT.
 *
 * @package     main
 * @author      Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2020, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

$created = [
    'files' => [],
    'dirs' => [],
];
$installSuccess = false;

function outputBufferOff()
{
    if (!headers_sent()) {
        // Disable gzip in PHP.
        ini_set('zlib.output_compression', 0);
        // Turn off output buffering
        ini_set('output_buffering', 'off');
        // Implicitly flush the buffer(s)
        ini_set('implicit_flush', true);
        // Force disable compression in a header.
        // Required for flush in some cases (Apache + mod_proxy, nginx, php-fpm).
        header('Content-Encoding: none');
        //prevent apache from buffering it for deflate/gzip
        header("Content-type: text/html");
        header('Cache-Control: no-cache'); // recommended to prevent caching of event data.
    }

    // Fill-up 5 kB buffer (should be enough in most cases).
    echo str_pad(' ', 5 * 1024);
    // Flush all buffers.
    do {
        $flushed = @ob_end_flush();
    } while ($flushed);
    // Output buffering can be layered and I've had cases where earlier code had made multiple levels.
    // This will clear them all.
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_implicit_flush(1);
    @ob_flush();
    flush();
}

function sendOK()
{
    return sendToBrowser('[  OK  ]');
}

function sendFail()
{
    return sendToBrowser('[ FAIL ]');
}

function sendSkip()
{
    return sendToBrowser('[ SKIP ]');
}

function getBaseUrl()
{
    // output: /myproject/index.php
    $currentPath = $_SERVER['PHP_SELF'];

    // output: Array ( [dirname] => /myproject [basename] => index.php [extension] => php [filename] => index )
    $pathInfo = pathinfo($currentPath);
    if ($pathInfo['dirname'] == '.') {
        $pathInfo['dirname'] = '';
    }

    // output: localhost
    $hostName = $_SERVER['HTTP_HOST'];

    // output: http://
    $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? $_SERVER["SERVER_PROTOCOL"];
    $protocol = strtolower(substr($proto, 0, 5)) == 'https' ? 'https' : 'http';
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https' : $protocol;

    // return: http://localhost/myproject/
    return $protocol . '://' . $hostName . rtrim($pathInfo['dirname'], " /") . "/";
}

function sendToBrowser($message)
{

    $style = '';
    $color = 'lightgray';

    if (str_contains($message, 'text/javascript')) {
        echo $message;
    } else {
        if (str_contains($message, '...')) {
            $style = 'width:auto; float: left; margin-right: 1em;';
            $message = sprintf("%-75s", $message);
        }
        if (str_contains($message, '[')  || str_contains($message, ' SKIP ')) {
            $color = 'yellow';
        }
        if (str_contains($message, '**') || str_contains($message, ' FAIL ')) {
            $color = 'red';
        }
        if (str_contains($message, ' OK ')) {
            $color = '#37fd37';
        }

        echo '<pre style=\'color:' . $color . '; margin:0; font-size:1.1em; font-family:monospace; ' . $style . '\'>';
        echo $message;
        echo '</pre>';
        echo '<script type="text/javascript">window.scrollTo(0,document.body.scrollHeight);</script>';
    }
    outputBufferOff();
}

if (!function_exists('localDelTree')) {
    function localDelTree($dir)
    {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                (is_dir("$dir/$file")) ? localDelTree("$dir/$file") : unlink("$dir/$file");
            }
            return rmdir($dir);
        }
    }
}

function makeClean()
{
    global $created;
    global $installSuccess;

    if (!$installSuccess) {
        foreach ($created['files'] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        foreach ($created['dirs'] as $dir) {
            localDelTree($dir);
        }
    }
}


function composerInstall($version = '2.7.2')
{
    // Composer in php code, thanks to https://stackoverflow.com/a/17244866
    define('COMPOSER_DIRECTORY', __DIR__ . '/upload_file/uploaded_files/composer');

    ini_set('memory_limit', '2048M');
    // Composer\Factory::getHomeDir() method needs COMPOSER_HOME environment variable set
    putenv('COMPOSER_HOME=' . COMPOSER_DIRECTORY);
    putenv('COMPOSER_MEMORY_LIMIT=2048M');

    if (!is_dir(COMPOSER_DIRECTORY)) {
        if (!mkdir(COMPOSER_DIRECTORY)) {
            die("Cannot make dir for composer: " . COMPOSER_DIRECTORY . ", aborting installation!");
        }
    }
    if (file_exists(COMPOSER_DIRECTORY . '/vendor/autoload.php') !== true) {
        set_time_limit(300);

        $COMPOSER_URL = 'https://getcomposer.org/download/' . $version . '/composer.phar';
        $fp = fopen(COMPOSER_DIRECTORY . DIRECTORY_SEPARATOR . 'Composer.phar', 'w+');
        $ch = curl_init($COMPOSER_URL);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        //disable ssl cert verification to allow copying files from HTTPS
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // write curl response to file
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // get curl response
        $exec = curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        if ($exec !== true) {
            if (is_file((COMPOSER_DIRECTORY . DIRECTORY_SEPARATOR . 'Composer.phar'))) {
                unlink(COMPOSER_DIRECTORY . DIRECTORY_SEPARATOR . 'Composer.phar');
            }
            die("Cannot download composer from url: $COMPOSER_URL");
        }
        $composerPhar = new Phar(COMPOSER_DIRECTORY . DIRECTORY_SEPARATOR . 'Composer.phar');
        $composerPhar->extractTo(COMPOSER_DIRECTORY);
        unset($composerPhar);
    }

    foreach ([__DIR__ . '/vendor', __DIR__ . '/js/vendor'] as $vendorDir) {
        if (!(is_dir($vendorDir) && is_writable($vendorDir))) {
            if (!@mkdir($vendorDir, 0770)) {
                die("$vendorDir does not exist, is not writable or could not be created: check webserver write permissions, aborting installation!");
            }
        }
    }
    //This requires the phar to have been extracted successfully.
    require_once(COMPOSER_DIRECTORY . '/vendor/autoload.php');

    if (!is_file(realpath(__DIR__) . '/vendor/autoload.php')) {
        // Create the commands
        $input = new StringInput('dumpautoload');
        // Create the application and run it with the commands
        // @phpstan-ignore-next-line
        $application = new Application();
        $application->setAutoExit(false); // prevent `$application->run` method from exitting the script
        $application->setCatchExceptions(false);
        $application->run($input);
    }
}

/**
 * redirect to homepage if ADA is installed, either with install script or manually
 */
if (is_file(realpath(__DIR__) . '/config_path.inc.php')) {
    require_once realpath(__DIR__) . '/config_path.inc.php';
}

if (
    defined('ROOT_DIR') && is_file(ROOT_DIR . '/config/config_install.inc.php') &&
    is_dir('clients') && count(glob(ROOT_DIR . "/clients/*/client_conf.inc.php")) > 0
) {
    Utilities::redirect(HTTP_ROOT_DIR);
    die();
}

register_shutdown_function('makeClean');
composerInstall();

putenv('PORTAL_NAME=ADA Install');
putenv('HTTP_ROOT_DIR=' . getBaseUrl());

/**
 * Files that MUST exists and be copied before doing anything
 */
foreach (
    [
        __DIR__ . '/config_path_DEFAULT.inc.php',
        __DIR__ . '/config/config_install_DEFAULT.inc.php',
    ] as $mustfile
) {
    if (!is_file($mustfile)) {
        die("NO $mustfile, aborting installation!");
    }
    $destfile = str_replace('_DEFAULT', '', $mustfile);
    if (!is_file($destfile)) {
        if (false === copy($mustfile, $destfile)) {
            die("Cannot copy to $destfile, aborting installation!");
        } else {
            $created['files'][] = $destfile;
        }
    }
}

require_once realpath(__DIR__) . '/config_path.inc.php';

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    define('COMPOSER_INSTALL_CMD', 'install -n --no-progress --no-cache --no-dev');
    putenv("COMPOSER_ROOT_VERSION=" . ADA_VERSION);
    if (session_status() !== PHP_SESSION_NONE) {
        session_start();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                ['expires' => time() - 42000, 'path' => $params["path"], 'domain' => $params["domain"], 'secure' => $params["secure"], 'httponly' => $params["httponly"]]
            );
        }
        session_destroy();
    }
    outputBufferOff();
    ini_set('max_execution_time', 300);
    $postData = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
    $postData = array_map(function ($el) {
        if (is_string($el)) {
            return trim($el);
        }
        return $el;
    }, $postData);
    if (array_key_exists('HTTP_ROOT_DIR', $postData)) {
        $postData['HTTP_ROOT_DIR'] = rtrim($postData['HTTP_ROOT_DIR'], '/') . '/';
    }
    $disabledModules = ['debugbar'];
    $modulesSQL = [];

    if (array_key_exists('MODULES_DISABLE', $postData)) {
        $disabledModules = array_merge($disabledModules, explode(',', $postData['MODULES_DISABLE']));
        $disabledModules = array_map('trim', $disabledModules);
    }

    $multiprovider = true;
    // put here filenames to be imported in the common db and each provider db if multiprovider eq 0
    // $inBothIfNonMulti=['ada_gdpr_policy.sql', 'ada_login_module.sql'];
    $inBothIfNonMulti = [];
    // put here filenames to be imported in the common db if multiprovider eq 1
    $inCommonIfMulti = ['ada_gdpr_policy.sql', 'ada_login_module.sql'];
    // put here filenames to be ALWAYS imported in the common db
    $inCommon = ['ada_apps_module.sql',  'ada_secretquestion_module.sql', 'ada_impexport_module.sql'];
    $defaultProvider = array_key_exists('DEFAULT_PROVIDER', $postData) && intval($postData['DEFAULT_PROVIDER']) > 0 ? intval($postData['DEFAULT_PROVIDER']) : 0;
    $adminUserId = 1; // id of the adminAda user
    $switcherIds = [
        'default' => [],
        'provider' => [],
    ];
    $authorIds = [
        'default' => [],
        'provider' => [],
    ];
    $newUsers = [];

    try {
        // Install composer dependencies as first
        if (is_file(ROOT_DIR . '/composer.json') && is_readable(ROOT_DIR . '/composer.json')) {
            set_time_limit(300);
            sendToBrowser(translateFN('Installazione dipendenze ADA') . ' ...');
            if ($logfile = @fopen(ROOT_DIR . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'composer-install.log', 'a')) {
                fwrite($logfile, sprintf("\n\n******** %s ********\n", 'ADA'));
            } else {
                $logfile = null;
            }
            chdir(__DIR__);
            // Create the commands
            $input = new StringInput(COMPOSER_INSTALL_CMD);
            // Create the application and run it with the commands
            // @phpstan-ignore-next-line
            $application = new Application();
            $application->setAutoExit(false); // prevent `$application->run` method from exitting the script
            $application->setCatchExceptions(false);
            if ($logfile) {
                $output = $application->run($input, new StreamOutput($logfile));
            } else {
                $output = $application->run($input);
            }
            if ($output == 0) {
                sendOK();
            } else {
                sendFail();
                sendToBrowser('** ' . translateFN('Problemi con composer'));
                die(translateFN('ADA NON INSTALLATA'));
            }
            if ($logfile) {
                fclose($logfile);
            }
        }

        if (array_key_exists('MYSQL', $postData) && array_key_exists('COMMON', $postData['MYSQL']) && is_array($postData['MYSQL']['COMMON']) && count($postData['MYSQL']['COMMON']) == 3) {
            $providers = isset($postData['PROVIDER']) && is_array($postData['PROVIDER']) ? $postData['PROVIDER'] : [];
            foreach ($providers as $i => $provider) {
                $providers[$i]['pointer'] = str_replace(' ', '_', trim($provider['NAME']));
            }
            $options = [
                // PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ];
            $commonExisted = true;
            $commonpdo = AdminHelper::checkDB($postData['MYSQL']['COMMON'], $postData['COMMONDB'], $options);
            if ($commonpdo === false) {
                sendToBrowser(sprintf(translateFN('Creazione Database %s') . ' ...', $postData['COMMONDB']));
                $commonExisted = false;
                $commonEmpty = true;
                $commonpdo = AdminHelper::createDB($postData['MYSQL']['COMMON'], $postData['COMMONDB'], $options);
                sendOK();
            } else {
                sendToBrowser(sprintf(translateFN('Database %s esistente') . ' ...', $postData['COMMONDB']));
                $commonEmpty = AdminHelper::isEmptyDB($commonpdo, $postData['COMMONDB']);
                sendOk();
            }
            sendToBrowser(translateFN("Importazione Database common") . ' ...');
            if ($commonEmpty) {
                AdminHelper::importSQL(ROOT_DIR . '/db/install/ada-empty-common.sql', $commonpdo);
                sendOK();
            } else {
                sendSkip();
            }

            // SET THE PASSWORD PROVIDED IN ADMIN_PASSWORD FOR USER 'adminAda'
            sendToBrowser(translateFN('Impostazione password utenti') . ' ...');
            $sql = "UPDATE `utente` SET `e_mail`=?, `password`=SHA1(\"" . $postData['ADMIN_PASSWORD'] . "\") WHERE `password`=\"\";";
            $stmt = $commonpdo->prepare($sql);
            $stmt->execute([$postData['ADA_ADMIN_MAIL_ADDRESS']]);
            sendOK();

            foreach ($providers as $i => $provider) {
                set_time_limit(300);
                $providers[$i]['pdoexisted'] = true;
                $providers[$i]['pdo'] = AdminHelper::checkDB($postData['MYSQL'][$i], $provider['DB'], $options);
                if ($providers[$i]['pdo'] === false) {
                    sendToBrowser(sprintf(translateFN('Creazione Database %s') . ' ...', $provider['DB']));
                    $providers[$i]['pdoexisted'] = false;
                    $providers[$i]['empty'] = true;
                    $providers[$i]['pdo'] = AdminHelper::createDB($postData['MYSQL'][$i], $provider['DB'], $options);
                    sendOK();
                } else {
                    sendToBrowser(sprintf(translateFN('Database %s esistente') . ' ...', $provider['DB']));
                    $providers[$i]['empty'] = AdminHelper::isEmptyDB($providers[$i]['pdo'], $provider['DB']);
                    sendOk();
                }
                sendToBrowser(sprintf(translateFN('Importazione Database %s') . ' ...', $provider['DB']));
                if ($providers[$i]['empty']) {
                    $sqlFile = ROOT_DIR . '/db/ada_provider_empty.sql';
                    $usersKey = 'provider';
                    if ($i == $defaultProvider) {
                        $usersKey = 'default';
                        if (is_readable(ROOT_DIR . '/db/install/ada_default_empty.sql')) {
                            $sqlFile = ROOT_DIR . '/db/install/ada_default_empty.sql';
                        }
                    } elseif (is_readable(ROOT_DIR . '/db/install/ada_provider_empty.sql')) {
                        $sqlFile = ROOT_DIR . '/db/install/ada_provider_empty.sql';
                    }
                    AdminHelper::importSQL($sqlFile, $providers[$i]['pdo']);

                    $sql = "INSERT INTO `tester`(`nome`,`puntatore`) VALUES ('" . $provider['NAME'] . "', '" . $providers[$i]['pointer'] . "');";
                    $stmt = $commonpdo->prepare($sql);
                    $stmt->execute();
                    $providerId = $commonpdo->lastInsertId();
                    unset($stmt);

                    foreach (array_merge([$adminUserId], $switcherIds[$usersKey], $authorIds[$usersKey]) as $anUserId) {
                        $uRow = "SELECT * FROM `" . $postData['COMMONDB'] . "`.`utente` WHERE `id_utente`=$anUserId;";
                        $stmt = $commonpdo->prepare($uRow);
                        $stmt->execute();
                        $uData = $stmt->fetch(PDO::FETCH_ASSOC);

                        $fields = '`' . implode('`, `', array_keys($uData)) . '`';
                        $fields_data = implode(', ', array_map(fn () => '?', $uData));
                        $sql =  "INSERT INTO `" . $provider['DB'] . "`.`utente` ({$fields}) VALUES ({$fields_data});";
                        $stmt = $providers[$i]['pdo']->prepare($sql);
                        $stmt->execute(array_values($uData));
                        unset($stmt);

                        $sql = "INSERT INTO `utente_tester`(`id_utente`, `id_tester`) VALUES ($anUserId, $providerId);";
                        $stmt = $commonpdo->prepare($sql);
                        $stmt->execute();
                        unset($stmt);

                        $updateUser = false;
                        if ($anUserId == $adminUserId) {
                            $sql = "INSERT INTO `" . $provider['DB'] . "`.`amministratore_sistema` (`id_utente_amministratore_sist`) VALUES ($adminUserId);";
                            $stmt = $providers[$i]['pdo']->prepare($sql);
                            $stmt->execute();
                            unset($stmt);
                        } elseif (in_array($anUserId, $authorIds[$usersKey])) {
                            $sql = "INSERT INTO `" . $provider['DB'] . "`.`autore` (`id_utente_autore`, `profilo`, `tariffa`) VALUES ($anUserId, NULL, 0);" .
                                "UPDATE `" . $provider['DB'] . "`.`modello_corso` SET `id_utente_autore`=$anUserId, `data_pubblicazione`=" . time() . ", `data_creazione`=" . time() . ";" .
                                "UPDATE `" . $provider['DB'] . "`.`nodo` SET `id_utente`=$anUserId, `data_creazione`=" . time() . ";";
                            $stmt = $providers[$i]['pdo']->prepare($sql);
                            $stmt->execute();
                            unset($stmt);
                            $updateUser = true;
                            $userPrefix = 'autore';
                        } elseif (in_array($anUserId, $switcherIds[$usersKey])) {
                            $updateUser = true;
                            $userPrefix = 'coordinatore';
                        }

                        if ($updateUser) {
                            $sql = "UPDATE `utente` SET `cognome`='" . $provider['NAME'] . "', `username`='$userPrefix." . $providers[$i]['pointer'] . "' WHERE `id_utente`=$anUserId;";
                            $stmt = $commonpdo->prepare($sql);
                            $stmt->execute();
                            unset($stmt);
                            $stmt = $providers[$i]['pdo']->prepare($sql);
                            $stmt->execute();
                            unset($stmt);
                            array_push($newUsers, $userPrefix . '.' . $providers[$i]['pointer']);
                            $updateUser = false;
                        }
                    }
                    sendOK();
                } else {
                    sendSkip();
                }

                sendToBrowser(sprintf(translateFN("Configurazione provider %s") . '...', $provider['NAME']));
                if (!is_file(ROOT_DIR . '/clients/' . $providers[$i]['pointer'] . '/client_conf.inc.php')) {
                    if (!is_dir(ROOT_DIR . '/clients/' . $providers[$i]['pointer'])) {
                        mkdir(ROOT_DIR . '/clients/' . $providers[$i]['pointer'], 0770, true);
                        $created['dirs'][] = ROOT_DIR . '/clients/' . $providers[$i]['pointer'];
                    }
                    $outfile = str_replace(
                        ['${UPPERPROVIDER}', '${ASISPROVIDER}_provider', '${PROV_HTTP}', '${MYSQL_USER}', '${MYSQL_PASSWORD}', '${MYSQL_HOST}',],
                        [strtoupper($providers[$i]['pointer']), $provider['DB'], $postData['HTTP_ROOT_DIR'], $postData['MYSQL'][$i]['USER'], $postData['MYSQL'][$i]['PASSWORD'], $postData['MYSQL'][$i]['HOST'],],
                        file_get_contents(ROOT_DIR . '/clients_DEFAULT/install-templates/client_conf.inc.php')
                    );
                    if (false === file_put_contents(ROOT_DIR . '/clients/' . $providers[$i]['pointer'] . '/client_conf.inc.php', $outfile)) {
                        throw new Exception(translateFN('Impossibile scrivere il file di configurazione del provider'));
                    } else {
                        sendOK();
                    }
                } else {
                    sendSkip();
                }
            }

            if (is_dir(MODULES_DIR)) {
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(MODULES_DIR . DIRECTORY_SEPARATOR));

                $regIter = new RegexIterator($iterator, '/^.+\.sql$/i', RecursiveRegexIterator::GET_MATCH);
                foreach ($regIter as $x) {
                    $modulesSQL = array_merge($modulesSQL, $x);
                }
                usort($modulesSQL, fn ($a, $b) =>
                // dirty hack to order by filename, having files that starts with a number as last elements
                strnatcmp('1' . basename($a) . DIRECTORY_SEPARATOR . $a, '1' . basename($b) . DIRECTORY_SEPARATOR . $b));

                // import modules sql in the databases
                if (is_array($modulesSQL) && count($modulesSQL) > 0) {
                    foreach ($modulesSQL as $sqlFile) {
                        set_time_limit(300);
                        if (
                            stristr($sqlFile, "menu") !== false ||
                            in_array(basename($sqlFile), $inCommon) ||
                            str_contains($sqlFile, "common") ||
                            (!$multiprovider && in_array(basename($sqlFile), $inBothIfNonMulti)) ||
                            ($multiprovider && in_array(basename($sqlFile), $inCommonIfMulti))
                        ) {
                            sendToBrowser(translateFN("Importazione") . ' ' . ltrim(str_replace(ROOT_DIR . '/modules', '', $sqlFile), '\/') . ' in ' . $postData['COMMONDB'] . ' ...');
                            if ($commonEmpty) {
                                AdminHelper::importSQL($sqlFile, $commonpdo);
                                sendOK();
                            } else {
                                sendSkip();
                            }
                        }
                    }
                    unset($commonpdo);
                    // done with the common db, now the providers
                    foreach ($providers as $i => $provider) {
                        foreach ($modulesSQL as $sqlFile) {
                            set_time_limit(300);
                            if (
                                stristr($sqlFile, "vendor") === false &&
                                stristr($sqlFile, "menu") === false &&
                                !in_array(basename($sqlFile), $inCommon) &&
                                !($multiprovider && in_array(basename($sqlFile), $inCommonIfMulti))
                            ) {
                                sendToBrowser(translateFN("Importazione") . ' ' . ltrim(str_replace(ROOT_DIR . '/modules', '', $sqlFile), '\/') . ' in ' . $provider['DB'] . ' ...');
                                if ($providers[$i]['empty']) {
                                    AdminHelper::importSQL($sqlFile, $provider['pdo']);
                                    sendOK();
                                } else {
                                    sendSkip();
                                }
                            }
                        }
                        unset($providers[$i]['pdo']);
                    }
                }
                gc_collect_cycles();

                // modules config files setup
                $regIter = new RegexIterator($iterator, '/^[a-z:|\/].+[\/|\\\]config\_DEFAULT\.inc\.php$/i', RecursiveRegexIterator::GET_MATCH);
                $configFiles = [];
                foreach ($regIter as $x) {
                    $configFiles = array_merge($configFiles, $x);
                }
                if (is_array($configFiles) && count($configFiles) > 0) {
                    foreach ($configFiles as $configFile) {
                        $dirname = dirname($configFile);
                        $modulename = basename(str_replace('config', '', $dirname));
                        sendToBrowser(translateFN("Configurazione modulo") . ' ' . $modulename . ' ...');
                        if (!in_array($modulename, $disabledModules)) {
                            if (is_dir($dirname) && is_writable($dirname)) {
                                $destFile = $dirname . DIRECTORY_SEPARATOR . str_replace('_DEFAULT', '', basename($configFile));
                                if (!is_file($destFile)) {
                                    if (copy($configFile, $destFile)) {
                                        $created['files'][] = $destFile;
                                        sendOK();
                                    } else {
                                        sendFail();
                                    }
                                } else {
                                    sendSkip();
                                }
                            } else {
                                sendFail();
                                sendToBrowser('** ' . translateFN('Impossibile scrivere nella directory del modulo'));
                            }
                        } else {
                            sendSkip();
                        }
                    }
                }

                // modules composer dependencies download
                $regIter = new RegexIterator($iterator, '/^[a-z:|\/].+[\/|\\\]composer\.json$/i', RecursiveRegexIterator::GET_MATCH);
                $composerFiles = [];
                foreach ($regIter as $x) {
                    $composerFiles = array_merge($composerFiles, $x);
                }
                if (is_array($composerFiles) && count($composerFiles) > 0) {
                    foreach ($composerFiles as $composerFile) {
                        $dirname = dirname($composerFile);
                        $modulename = basename($dirname);
                        if (stristr($composerFile, 'vendor') === false) {
                            set_time_limit(300);
                            sendToBrowser(translateFN('Installazione dipendenze per il modulo') . ' ' . $modulename . ' ...');
                            // if (!in_array($modulename, $disabledModules)) {
                            if (is_dir($dirname) && is_writable($dirname)) {
                                if ($logfile = @fopen(ROOT_DIR . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'composer-install.log', 'a')) {
                                    fwrite($logfile, sprintf("\n\n******** %s ********\n", $modulename));
                                }
                                chdir($dirname);
                                // Create the commands
                                $input = new StringInput(COMPOSER_INSTALL_CMD);
                                // Create the application and run it with the commands
                                // @phpstan-ignore-next-line
                                $application = new Application();
                                $application->setAutoExit(false); // prevent `$application->run` method from exitting the script
                                $application->setCatchExceptions(false);
                                if ($logfile) {
                                    $output = $application->run($input, new StreamOutput($logfile));
                                } else {
                                    $output = $application->run($input);
                                }
                                if ($output == 0) {
                                    sendOK();
                                } else {
                                    sendFail();
                                    sendToBrowser('** ' . translateFN('Problemi con composer'));
                                }
                                chdir(__DIR__);
                                if ($logfile) {
                                    fclose($logfile);
                                }
                            } else {
                                sendFail();
                                sendToBrowser('** ' . translateFN('Impossibile scrivere nella directory del modulo'));
                            }
                            // } else sendSkip();
                        }
                    }
                }
            }

            // create file with environment vars, this MUST BE the last step and if the ENV_FILENAME
            // is written without errors, it should be safe to consider ADA as installed
            sendToBrowser(translateFN('Generazione file configurazione') . ' ...');
            if (!is_file(ENV_FILENAME)) {
                // form variable to environment variable name mappings
                $formtoenv = [
                    'PORTAL_NAME' => 'PORTAL_NAME',
                    'COMMONDB' => 'MYSQL_DATABASE',
                    'HTTP_ROOT_DIR' => 'HTTP_ROOT_DIR',
                    'ADA_ADMIN_MAIL_ADDRESS' => 'ADA_ADMIN_MAIL_ADDRESS',
                    'ADA_NOREPLY_MAIL_ADDRESS' => 'ADA_NOREPLY_MAIL_ADDRESS',
                ];
                $envlines = [
                    'ADA_OR_WISP' => "putenv('ADA_OR_WISP=ADA')",
                    'MULTIPROVIDER' => "putenv('MULTIPROVIDER=" . intval($multiprovider) . "')",
                    'MYSQL_USER' => "putenv('MYSQL_USER=" . $postData['MYSQL']['COMMON']['USER'] . "')",
                    'MYSQL_PASSWORD' => "putenv('MYSQL_PASSWORD=" . $postData['MYSQL']['COMMON']['PASSWORD'] . "')",
                    'MYSQL_HOST' => "putenv('MYSQL_HOST=" . $postData['MYSQL']['COMMON']['HOST'] . "')",
                    'DEFAULT_PROVIDER_POINTER' => "putenv('DEFAULT_PROVIDER_POINTER=" . $providers[$defaultProvider]['pointer'] . "')",
                    'DEFAULT_PROVIDER_DB' => "putenv('DEFAULT_PROVIDER_DB=" . $providers[$defaultProvider]['DB'] . "')",
                    'DEFAULT_PROVIDER_DB_USER' => "putenv('DEFAULT_PROVIDER_DB_USER=" . $postData['MYSQL'][$defaultProvider]['USER'] . "')",
                    'DEFAULT_PROVIDER_DB_PASS' => "putenv('DEFAULT_PROVIDER_DB_PASS=" . $postData['MYSQL'][$defaultProvider]['PASSWORD'] . "')",
                    'DEFAULT_PROVIDER_DB_HOST' => "putenv('DEFAULT_PROVIDER_DB_HOST=" . $postData['MYSQL'][$defaultProvider]['HOST'] . "')",
                ];
                foreach ($formtoenv as $formkey => $envvar) {
                    if (array_key_exists($formkey, $postData) && strlen($postData[$formkey]) > 0) {
                        if ($formkey == 'HTTP_ROOT_DIR') {
                            $postData[$formkey] = rtrim($postData[$formkey], DIRECTORY_SEPARATOR);
                        }
                        $envlines[$formkey] = "putenv('$envvar=" . $postData[$formkey] . "')";
                    }
                }
                if (false === file_put_contents(ENV_FILENAME, "<?php" . PHP_EOL . PHP_EOL . implode(';' . PHP_EOL, array_values($envlines)) . ";" . PHP_EOL)) {
                    throw new Exception(translateFN('Impossibile scrivere il file di configurazione principale'));
                } else {
                    $created['files'][] = ENV_FILENAME;
                    chmod(ENV_FILENAME, 0440);
                    sendOK();
                }
            } else {
                sendSkip();
            }

            // Install is done, ensure that needed subdirs have been created.
            foreach (['docs/help', 'docs/info', 'docs/news'] as $ensureDir) {
                if (!is_dir($ensureDir)) {
                    mkdir($ensureDir, 0770, true);
                }
            }

            sendToBrowser(translateFN('Rimozione file temopranei') . ' ...');
            Utilities::delTree(COMPOSER_DIRECTORY) ? sendOK() : sendFail();

            if (is_array($newUsers) && count($newUsers) > 0) {
                sendToBrowser(PHP_EOL . PHP_EOL . "<div style='font-size:1.2em;color:#ff9d51;'>Trascrivere in un posto sicuro lo username e la password degli utenti generati che sono:<strong>" . PHP_EOL . implode(PHP_EOL, $newUsers) . PHP_EOL . "</strong>la password è quella fornita durante l'installazione.</div>" . PHP_EOL);
            }
            sendToBrowser('&nbsp;');
            sendToBrowser(PHP_EOL . "<strong>" . translateFN("ADA è installata, naviga su:") . " <a style='color:lime;' href='" .
                $postData['HTTP_ROOT_DIR'] . "' target='_top'>" . $postData['HTTP_ROOT_DIR'] . "</a></strong>");
            sendToBrowser('<script type="text/javascript">window.parent.postMessage("doneOK", "*");</script>');
            $installSuccess = true;
        } else {
            makeClean();
            throw new Exception(translateFN('Parametri MySQL/MariaDB non validi'), 1);
        }
    } catch (Exception $e) {
        makeClean();
        sendFail();
        sendToBrowser('** ' . $e->getMessage() . ' (' . $e->getCode() . ')');
        sendToBrowser('<script type="text/javascript">window.parent.postMessage("doneException", "*");</script>');
        die();
    }
} else {
    session_start();
    $_SESSION['sess_userObj'] = new ADAGuest();
    $self = Utilities::whoami();
    $modulesAv = [];
    $modulesDIS = ['secretquestion', 'code_man'];
    $modulesHidden = ['event-dispatcher', 'gdpr', 'login', 'test', 'debugbar', 'extract-logger'];
    if (is_dir(MODULES_DIR)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(MODULES_DIR . DIRECTORY_SEPARATOR));
        $regIter = new RegexIterator($iterator, '/^[a-z:|\/].+[\/|\\\]config\_DEFAULT\.inc\.php$/i', RecursiveRegexIterator::GET_MATCH);
        $configFiles = [];
        foreach ($regIter as $x) {
            $configFiles = array_merge($configFiles, $x);
        }
        if (is_array($configFiles) && count($configFiles) > 0) {
            foreach ($configFiles as $configFile) {
                $dirname = dirname($configFile);
                $modulesAv[] = basename(str_replace('config', '', $dirname));
            }
        }
    }
    $modulesAv = array_diff($modulesAv, $modulesHidden);
    sort($modulesAv);
    sort($modulesDIS);
    $modulesDIS = array_intersect($modulesDIS, $modulesAv);

    $modMessage = CDOMElement::create('div', 'class:ui visible warning small message');
    $modMessage->addChild(new CText(translateFN("ATTENZIONE: se si sta installando il modulo 'slideimport' leggere il suo README per informazioni sull'uso di ImageMagick e Ghostscript.")));
    /**
     * Sends data to the rendering engine
     */
    ARE::render(
        [
            'node_type' => null,
            'family' => 'ada_blu',
            'node_author_id' => null,
            'node_course_id' => null,
            'module_dir' => null,
        ],
        [
            'modsavailable' => count($modulesAv) > 0 ? translateFN('Moduli disabilitabili') . ': ' . implode(', ', $modulesAv) : null,
            'modsdisabled' => implode(', ', $modulesDIS),
            'modmessage' => $modMessage->getHtml(),
        ],
        null,
        [
            'onload_func' => 'initDoc();',
        ]
    );
}
