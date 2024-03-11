<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector;

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        // __DIR__ . '/admin',
        // __DIR__ . '/api',
        // __DIR__ . '/browsing',
        // __DIR__ . '/clients',
        // __DIR__ . '/clients_DEFAULT',
        // __DIR__ . '/comunica',
        // __DIR__ . '/config',
        // __DIR__ . '/external',
        // __DIR__ . '/include',
        // __DIR__ . '/js',
        // __DIR__ . '/modules',
        // __DIR__ . '/services',
        // __DIR__ . '/switcher',
        // __DIR__ . '/tutor',
        // __DIR__ . '/upload_file',
        // __DIR__ . '/widgets',
    ]);

    $rectorConfig->skip([
        // __DIR__ . '/include/Cezpdf',
        // __DIR__ . '/include/dompdf',
        __DIR__ . '/include/graph',
        __DIR__ . '/browsing/include/graph',
        // __DIR__ . '/include/phpMailer',
        // __DIR__ . '/include/MobileDetect',
        // __DIR__ . '/include/getid3',
        __DIR__ . '/vendor',
    ]);

    $rectorConfig->skip([
        Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector::class,
    ]);

    // register a single rule
    // $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);
    $rectorConfig->rule(OptionalParametersAfterRequiredRector::class);

    // define sets of rules
    // $rectorConfig->sets([
    //     LevelSetList::UP_TO_PHP_81
    // ]);
};




