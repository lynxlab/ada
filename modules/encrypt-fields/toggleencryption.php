<?php

use Lynxlab\ADA\Module\Encryptfields\CypherUtils;
use Lynxlab\ADA\Module\Encryptfields\EchoHelper;

require_once(realpath(__DIR__) . '/../../config_path.inc.php');

if (php_sapi_name() == "cli") {
    if ($argc < 3) {
        die(EchoHelper::error('Usage: ') . $argv[0] . '--encrypt|--decrypt --dry-run|--force' . PHP_EOL);
    }

    if (isset($argv[1]) && $argv[1] === "--encrypt") {
        echo EchoHelper::warn('Doing a database fields encryption', true);
        $encrypt = true;
    } elseif (isset($argv[1]) && $argv[1] === "--decrypt") {
        echo EchoHelper::ok('Doing a database fields decryption', true);
        $encrypt = false;
    } else {
        die(EchoHelper::error('ERROR') . ' Please pass --encrypt or --decrypt as command' . PHP_EOL);
    }

    if (isset($argv[2]) && $argv[2] === "--dry-run") {
        echo EchoHelper::warn('Doing a dry-run as requested, nothing will be persisted to the DB', true);
        $dryRun = true;
    } elseif (isset($argv[2]) && $argv[2] === "--force") {
        echo EchoHelper::ok('--force option passed, persisting imported data to the DB', true);
        $dryRun = false;
    } else {
        die(EchoHelper::error('ERROR') . ' Please pass --dry-run or --force as command' . PHP_EOL);
    }
    if (isset($dryRun) && isset($encrypt)) {
        CypherUtils::toggleEncryption($dryRun, $encrypt);
    }
}
