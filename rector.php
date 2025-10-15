<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use RectorLaravel\Rector\Class_\AddExtendsAnnotationToModelFactoriesRector;
use RectorLaravel\Rector\ClassMethod\MigrateToSimplifiedAttributeRector;
use RectorLaravel\Rector\PropertyFetch\ReplaceFakerInstanceWithHelperRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);

    $rectorConfig->sets([
        // PHP Level sets
        LevelSetList::UP_TO_PHP_82,

        // General code quality sets
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
        SetList::NAMING,
        SetList::INSTANCEOF,
        SetList::STRICT_BOOLEANS,
        SetList::PHP_81,
        SetList::PHP_82,
    ]);

    // Laravel-specific rules
    $rectorConfig->rules([
        AddExtendsAnnotationToModelFactoriesRector::class,
        MigrateToSimplifiedAttributeRector::class,
        ReplaceFakerInstanceWithHelperRector::class,
    ]);

    $rectorConfig->skip([
        // Skip certain rules if needed
    ]);

    $rectorConfig->parallel();
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();
};
