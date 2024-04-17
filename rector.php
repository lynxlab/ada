<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector;
use Rector\Config\RectorConfig;
use Rector\Php53\Rector\Ternary\TernaryToElvisRector;
use Rector\Php55\Rector\Class_\ClassConstantToSelfClassRector;
use Rector\Php55\Rector\FuncCall\GetCalledClassToStaticClassRector;
use Rector\Php70\Rector\If_\IfToSpaceshipRector;
use Rector\Php70\Rector\MethodCall\ThisCallOnStaticMethodToStaticCallRector;
use Rector\Php70\Rector\StmtsAwareInterface\IfIssetToCoalescingRector;
use Rector\Php70\Rector\Variable\WrapVariableVariableNameInCurlyBracesRector;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Php71\Rector\TryCatch\MultiExceptionCatchRector;
use Rector\Php73\Rector\ConstFetch\SensitiveConstantNameRector;
use Rector\Php73\Rector\FuncCall\ArrayKeyFirstLastRector;
use Rector\Php73\Rector\FuncCall\StringifyStrNeedlesRector;
use Rector\Php73\Rector\String_\SensitiveHereNowDocRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php74\Rector\FuncCall\ArrayKeyExistsOnPropertyRector;
use Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\Class_\StringableForToStringRector;
use Rector\Php80\Rector\FuncCall\ClassOnObjectRector;
use Rector\Php80\Rector\FunctionLike\MixedTypeRector;
use Rector\Php80\Rector\Identical\StrEndsWithRector;
use Rector\Php80\Rector\Identical\StrStartsWithRector;
use Rector\Php80\Rector\NotIdentical\StrContainsRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;

return RectorConfig::configure()
    ->withImportNames(importShortClasses:true, removeUnusedImports: true, importDocBlockNames: false)
    ->withPhpSets(php80: true)
    ->withBootstrapFiles([
        __DIR__ . '/config_path.inc.php',
    ])
    ->withRules([
        OptionalParametersAfterRequiredRector::class,
    ])
    ->withSkip([
        __DIR__ . '/browsing/include/graph',
        __DIR__ . '/services/media',
        __DIR__ . '/upload_file',
        __DIR__ . '/widgets/cache',
        __DIR__ . '/api',
        '*/vendor/*',
        ChangeSwitchToMatchRector::class,
        RemoveExtraParametersRector::class,
        ArrayKeyExistsOnPropertyRector::class,
        MixedTypeRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        IfToSpaceshipRector::class,
        GetCalledClassToStaticClassRector::class,
        ClassOnObjectRector::class,
        // MultiExceptionCatchRector::class,
        // StrContainsRector::class,
        // RemoveUnusedVariableInCatchRector::class,
        // ClosureToArrowFunctionRector::class,
        // WrapVariableVariableNameInCurlyBracesRector::class,
        // ArrayKeyFirstLastRector::class,
        // IfIssetToCoalescingRector::class,
        // ClassConstantToSelfClassRector::class,
        // ThisCallOnStaticMethodToStaticCallRector::class,
        // StringifyStrNeedlesRector::class,
        // StrStartsWithRector::class,
        // StrEndsWithRector::class,
        // SensitiveHereNowDocRector::class,
        // SensitiveConstantNameRector::class,
        // RandomFunctionRector::class,
        // DirNameFileConstantToDirConstantRector::class,
        // TernaryToElvisRector::class,
        // StringableForToStringRector::class,
    ]);
