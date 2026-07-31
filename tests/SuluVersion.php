<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests;

use function interface_exists;

/**
 * Tells which Sulu generation the test run is executing against.
 *
 * The bundle supports Sulu 2.6 and Sulu 3, whose content interfaces live in different
 * namespaces. Only one of them is installed at a time, so version specific fixtures and
 * tests have to be selected at runtime.
 */
final class SuluVersion
{
    public static function isSulu3(): bool
    {
        return interface_exists('Sulu\\Content\\Domain\\Model\\DimensionContentInterface');
    }

    public static function fixtureDirectory(): string
    {
        return __DIR__ . '/Fixtures/' . (self::isSulu3() ? 'Sulu3' : 'Sulu26');
    }

    /**
     * @return class-string
     */
    public static function entityClass(): string
    {
        /** @var class-string $class */
        $class = self::isSulu3()
            ? 'PERSPEQTIVE\\SuluAiEntityTranslationBundle\\Tests\\Fixtures\\Sulu3\\TestEntity'
            : 'PERSPEQTIVE\\SuluAiEntityTranslationBundle\\Tests\\Fixtures\\Sulu26\\TestEntity';

        return $class;
    }
}
