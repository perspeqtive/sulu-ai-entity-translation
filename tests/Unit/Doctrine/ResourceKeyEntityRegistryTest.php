<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\Doctrine;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Doctrine\ResourceKeyEntityRegistry;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\SuluVersion;
use PHPUnit\Framework\TestCase;

class ResourceKeyEntityRegistryTest extends TestCase
{
    public function testFindsEntityClassByResourceKey(): void
    {
        $registry = new ResourceKeyEntityRegistry($this->createEntityManager());

        self::assertSame(SuluVersion::entityClass(), $registry->findEntityClass('test_entities'));
    }

    public function testReturnsNullForUnknownResourceKey(): void
    {
        $registry = new ResourceKeyEntityRegistry($this->createEntityManager());

        self::assertNull($registry->findEntityClass('unknown'));
    }

    private function createEntityManager(): EntityManagerInterface
    {
        $config = ORMSetup::createAttributeMetadataConfiguration(
            [SuluVersion::fixtureDirectory()],
            true,
        );

        $connection = DriverManager::getConnection(
            ['driver' => 'pdo_sqlite', 'memory' => true],
            $config,
        );

        return new EntityManager($connection, $config);
    }
}
