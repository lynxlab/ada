<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'vendor',
        'browsing/include/graph',
        'services/media',
        'upload_file',
    ])
;

return (new PhpCsFixer\Config())
    // ->setRules([
    //     '@PER-CS' => true,
    //     '@PHP83Migration' => true,
    // ])
    ->setFinder($finder)
;
