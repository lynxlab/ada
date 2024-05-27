<?php

if (!defined('DIRECTORY_SEPARATOR')) {
    define(
        'DIRECTORY_SEPARATOR',
        strtoupper(str_starts_with(PHP_OS, 'WIN')) ? '\\' : '/'
    ) ;
}
