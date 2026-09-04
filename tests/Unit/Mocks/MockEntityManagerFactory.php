<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\Mocks;

use Doctrine\ORM\EntityManagerInterface;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\SuluVersion;

/**
 * Doctrine ORM 2 and 3 declare EntityManagerInterface with different signatures, and Sulu 2.6
 * pins the older one, so the double exists once per generation and is selected by name.
 */
final class MockEntityManagerFactory
{
    public static function open(): EntityManagerInterface
    {
        /** @var class-string<EntityManagerInterface> $class */
        $class = true === SuluVersion::isSulu3()
            ? 'PERSPEQTIVE\\SuluAiEntityTranslationBundle\\Tests\\Unit\\Mocks\\Sulu3\\MockEntityManager'
            : 'PERSPEQTIVE\\SuluAiEntityTranslationBundle\\Tests\\Unit\\Mocks\\Sulu26\\MockEntityManager';

        return new $class();
    }

    public static function closed(): EntityManagerInterface
    {
        $entityManager = self::open();
        $entityManager->close();

        return $entityManager;
    }
}
