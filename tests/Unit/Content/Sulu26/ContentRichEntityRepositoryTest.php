<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\Content\Sulu26;

use InvalidArgumentException;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Content\Sulu26\ContentRichEntityRepository;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Content\Sulu26\DimensionContentAdapter;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures\Sulu26\TestDimensionContent;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures\Sulu26\TestEntity;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\SuluVersion;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\Mocks\MockResourceKeyEntityRegistry;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\Mocks\Sulu26\MockEntityManager;
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
            new MockEntityManager(),
            new MockResourceKeyEntityRegistry(),
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

        $entityManager = new MockEntityManager();

        $repository = new ContentRichEntityRepository($entityManager, new MockResourceKeyEntityRegistry(TestEntity::class));

        $repository->persist(new DimensionContentAdapter($dimensionContent), 'en');

        self::assertSame([$dimensionContent], $entityManager->persisted);
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
        $entityManager = new MockEntityManager([TestEntity::class => ['1' => $entity]]);

        return new ContentRichEntityRepository($entityManager, new MockResourceKeyEntityRegistry(TestEntity::class));
    }
}
