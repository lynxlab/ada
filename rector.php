<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php53\Rector\FuncCall\DirNameFileConstantToDirConstantRector;
use Rector\Php53\Rector\Ternary\TernaryToElvisRector;
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
use Rector\Php55\Rector\Class_\ClassConstantToSelfClassRector;
use Rector\Php55\Rector\FuncCall\GetCalledClassToStaticClassRector;
use Rector\Php56\Rector\FuncCall\PowToExpRector;
use Rector\Php70\Rector\FuncCall\RandomFunctionRector;
use Rector\Php70\Rector\If_\IfToSpaceshipRector;
use Rector\Php70\Rector\MethodCall\ThisCallOnStaticMethodToStaticCallRector;
use Rector\Php70\Rector\StmtsAwareInterface\IfIssetToCoalescingRector;
use Rector\Php70\Rector\Ternary\TernaryToNullCoalescingRector;
use Rector\Php70\Rector\Variable\WrapVariableVariableNameInCurlyBracesRector;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Php71\Rector\TryCatch\MultiExceptionCatchRector;
use Rector\Php73\Rector\FuncCall\ArrayKeyFirstLastRector;
use Rector\Php73\Rector\FuncCall\SetCookieRector;
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
    ->withPhpSets(php83: true)
    ->withBootstrapFiles([
        __DIR__ . '/config_path.inc.php',
    ])
    ->withSkip([
        __DIR__ . '/services/media/**/*',
        __DIR__ . '/upload_file',
        __DIR__ . '/widgets/cache',
        '*/vendor/*',
        __DIR__ . '/rector.php',
        __DIR__ . '/modules/debugbar/adminer',
        ChangeSwitchToMatchRector::class,
        RemoveExtraParametersRector::class,
        ArrayKeyExistsOnPropertyRector::class,
        MixedTypeRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,

        // ClassOnObjectRector::class,
        // GetCalledClassToStaticClassRector::class,
        // IfToSpaceshipRector::class,
        // DirNameFileConstantToDirConstantRector::class,
        // MultiExceptionCatchRector::class,
        // StrContainsRector::class,
        // StrStartsWithRector::class,
        // StrEndsWithRector::class,
        // RemoveUnusedVariableInCatchRector::class,
        // ClosureToArrowFunctionRector::class,
        // WrapVariableVariableNameInCurlyBracesRector::class,
        // ArrayKeyFirstLastRector::class,
        // IfIssetToCoalescingRector::class,
        // ClassConstantToSelfClassRector::class,
        // ThisCallOnStaticMethodToStaticCallRector::class,
        // StringifyStrNeedlesRector::class,
        // SensitiveHereNowDocRector::class,
        // RandomFunctionRector::class,
        // TernaryToElvisRector::class,
        // StringableForToStringRector::class,
        // SetCookieRector::class,
        // TernaryToNullCoalescingRector::class,
        // PowToExpRector::class,
    ]);
