<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\Content\Sulu26;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Content\Sulu26\ContentRichEntityRepository;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Content\Sulu26\DimensionContentAdapter;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Doctrine\ResourceKeyEntityRegistryInterface;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures\Sulu26\TestDimensionContent;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures\Sulu26\TestEntity;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\SuluVersion;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;

class ContentRichEntityRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        if (SuluVersion::isSulu3()) {
            self::markTestSkipped('Requires Sulu 2.6.');
        }
    }

    public function testFindsDraftDimensionContentForRequestedLocale(): void
    {
        $entity = new TestEntity();
        $german = $this->addDimensionContent($entity, 'de', DimensionContentInterface::STAGE_DRAFT);
        $english = $this->addDimensionContent($entity, 'en', DimensionContentInterface::STAGE_DRAFT);

        $repository = $this->createRepository($entity);

        $adapter = $repository->find('1', 'en', 'test_entities');

        self::assertInstanceOf(DimensionContentAdapter::class, $adapter);
        self::assertSame($english, $adapter->getDimensionContent());
        self::assertNotSame($german, $adapter->getDimensionContent());
    }

    public function testIgnoresLiveDimensionContent(): void
    {
        $entity = new TestEntity();
        $this->addDimensionContent($entity, 'en', DimensionContentInterface::STAGE_LIVE);
        $draft = $this->addDimensionContent($entity, 'en', DimensionContentInterface::STAGE_DRAFT);

        $repository = $this->createRepository($entity);

        $adapter = $repository->find('1', 'en', 'test_entities');

        self::assertInstanceOf(DimensionContentAdapter::class, $adapter);
        self::assertSame($draft, $adapter->getDimensionContent());
    }

    public function testThrowsForUnknownResourceKey(): void
    {
        $repository = new ContentRichEntityRepository(
            $this->createMock(EntityManagerInterface::class),
            $this->createRegistry(null),
        );

        $this->expectException(InvalidArgumentException::class);

        $repository->find('1', 'en', 'unknown');
    }

    public function testThrowsWhenNoDimensionContentMatchesTheLocale(): void
    {
        $entity = new TestEntity();
        $this->addDimensionContent($entity, 'de', DimensionContentInterface::STAGE_DRAFT);

        $repository = $this->createRepository($entity);

        $this->expectException(RuntimeException::class);

        $repository->find('1', 'en', 'test_entities');
    }

    public function testPersistsTheWrappedDimensionContent(): void
    {
        $dimensionContent = new TestDimensionContent(new TestEntity());

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with($dimensionContent);

        $repository = new ContentRichEntityRepository($entityManager, $this->createRegistry(TestEntity::class));

        $repository->persist(new DimensionContentAdapter($dimensionContent), 'en');
    }

    private function addDimensionContent(TestEntity $entity, string $locale, string $stage): TestDimensionContent
    {
        $dimensionContent = new TestDimensionContent($entity);
        $dimensionContent->setLocale($locale);
        $dimensionContent->setStage($stage);
        $entity->addDimensionContent($dimensionContent);

        return $dimensionContent;
    }

    private function createRepository(TestEntity $entity): ContentRichEntityRepository
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('find')->with(TestEntity::class, '1')->willReturn($entity);

        return new ContentRichEntityRepository($entityManager, $this->createRegistry(TestEntity::class));
    }

    /**
     * @param class-string|null $entityClass
     */
    private function createRegistry(?string $entityClass): ResourceKeyEntityRegistryInterface
    {
        $registry = $this->createMock(ResourceKeyEntityRegistryInterface::class);
        $registry->method('findEntityClass')->willReturn($entityClass);

        return $registry;
    }
}
