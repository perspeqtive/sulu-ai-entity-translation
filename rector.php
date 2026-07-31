<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Identical\SimplifyBoolIdenticalTrueRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        SimplifyBoolIdenticalTrueRector::class,
    ])
    ->withComposerBased(phpunit: true, symfony: true)
    ->withIndent()
    ->withAttributesSets(
        symfony: true,
        doctrine: true,
        phpunit: true,
    )
    ->withPhpSets(
        php82: true,
    )
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        privatization: true,
        instanceOf: true,
        earlyReturn: true,
        strictBooleans: true,
        rectorPreset: true,
    )
    ->withTypeCoverageLevel(50)
    ->withImportNames(removeUnusedImports: true)
    ->withPHPStanConfigs([
        __DIR__ . '/phpstan.neon',
    ]);
