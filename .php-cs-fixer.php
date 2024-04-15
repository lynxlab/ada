<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'vendor',
        'browsing/include/graph',
        'services/media',
        'uploadFile',
        'widgets/cache',
        'api'
    ]);

return (new PhpCsFixer\Config())
    // ->setRules([
        // '@PSR12' => true,
        // 'ordered_imports' => [
        //     'imports_order' => [
        //         'class', 'function', 'const'
        //     ],
        //     'sort_algorithm' => 'alpha',
        // ]
        //     '@PER-CS' => true,
        //     '@PHP83Migration' => true,
    // ])
    ->setFinder($finder);
