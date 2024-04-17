<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'browsing/include/graph',
        'services/media',
        'upload_file',
        'widgets/cache',
        'api',
        'vendor',
    ]);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'ordered_imports' => [
            'imports_order' => [
                'class', 'function', 'const'
            ],
            'sort_algorithm' => 'alpha',
        ],
        '@PHP83Migration' => true,
    ])
    ->setFinder($finder);
