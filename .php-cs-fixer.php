<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'browsing/include/graph',
        'services/media',
        'upload_file',
        'widgets/cache',
        'modules/debugbar/adminer',
        'vendor',
        'external/fckeditor/editor/dialog/fck_spellerpages/spellerpages/server-scripts/',
    ]);

return (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRules([
        'align_multiline_comment' => true,
        '@PSR12' => true,
        'ordered_imports' => [
            'imports_order' => [
                'class', 'function', 'const'
            ],
            'sort_algorithm' => 'alpha',
        ],
        '@PHP83Migration' => true,
        'heredoc_indentation' => false,
        'octal_notation' => false,
    ])
    ->setFinder($finder);
