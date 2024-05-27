<?php

/**
 * Base config file
 */
require_once realpath(__DIR__).'/../config_path.inc.php';

/**
 * used to load css rules in fck editor
 */
header('Content-type: text/css');
echo '@CHARSET "UTF-8";'.PHP_EOL;
echo <<<CSS_WRAP
/**
 * OWN CSS RULES HERE
 */


CSS_WRAP;
