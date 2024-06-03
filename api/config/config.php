<?php

$fullpath = explode(DIRECTORY_SEPARATOR, str_replace(ROOT_DIR, '', __DIR__));
array_pop($fullpath);

return [
    'error' => [
        'displayErrorDetails' => true,
        'logErrors' => true,
        'logErrorDetails' => true,
    ],
    'basepath' => implode(DIRECTORY_SEPARATOR, $fullpath),
    'basenamespace' => '\\Lynxlab\\ADA\\API\\',
    'defaultFormat' => 'json',
    'supportedFormats' => [
        'json',
        'php',
        'xml',
    ],
    'latestversion' => 'v1',
];
