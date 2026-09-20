<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Integration\Sulu3;

use ArrayObject;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Domain\Event\TranslationFailedEvent;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\EventListener\CustomEntityTranslationSubscriber;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Application\TestKernel;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures\Sulu3\TestDimensionContent;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures\Sulu3\TestEntity;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures\TestDomainEvent;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\SuluVersion;
use RuntimeException;
use Sulu\Bundle\ActivityBundle\Application\Dispatcher\DomainEventDispatcherInterface;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\ActivityBundle\Domain\Model\Activity;
use Sulu\Bundle\AiBundle\Expert\Translator\TranslatorResponse;
use Sulu\Bundle\AiBundle\Testing\TestTranslator\TestTranslator;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Covers what happens when the translation fails: the copied locale has already been committed
 * when the domain event is dispatched, so the failure must not reach the caller.
 */
class RecordTranslationFailureTest extends KernelTestCase
{
    private const COPIED_DATA = ['title' => 'Glossarbegriff', 'description' => '<p>Eine Erklärung</p>', 'reference' => null];

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected function setUp(): void
    {
        if (false === SuluVersion::isSulu3()) {
            self::markTestSkipped('Requires Sulu 3.');
        }

        self::bootKernel();
    }

    public function testWritesTheFailureToTheActivityLog(): void
    {
        $entityManager = $this->prepareSchema();
        $entity = $this->createEntityWithCopiedLocale($entityManager);

        $this->givenAFailingTranslator(new RuntimeException('Quota exceeded'));
        $this->givenACopyLocaleRequest();

        $this->domainEventDispatcher()->dispatch(new TestDomainEvent(
            resourceKey: 'test_entities',
            resourceId: (string) $entity->getId(),
            resourceLocale: 'en',
        ));

        $activity = $this->findActivity($entityManager, 'ai_translation_failed');

        self::assertNotNull($activity);
        self::assertSame('test_entities', $activity->getResourceKey());
        self::assertSame((string) $entity->getId(), $activity->getResourceId());
        self::assertSame('en', $activity->getResourceLocale());
        self::assertSame(['reason' => 'Quota exceeded'], $activity->getContext());
    }

    public function testKeepsTheCopiedLocaleWhenTheTranslationFails(): void
    {
        $entityManager = $this->prepareSchema();
        $entity = $this->createEntityWithCopiedLocale($entityManager);

        $this->givenAFailingTranslator(new RuntimeException('Quota exceeded'));
        $this->givenACopyLocaleRequest();

        $this->domainEventDispatcher()->dispatch(new TestDomainEvent(
            resourceKey: 'test_entities',
            resourceId: (string) $entity->getId(),
            resourceLocale: 'en',
        ));

        self::assertSame(self::COPIED_DATA, $this->englishContent($entity)->getTemplateData());
    }

    public function testSkipsTheActivityLogWhenTheTranslationClosedTheEntityManager(): void
    {
        $entityManager = $this->prepareSchema();
        $entity = $this->createEntityWithCopiedLocale($entityManager);

        $this->givenATranslatorClosing($entityManager);
        $this->givenACopyLocaleRequest();

        $failures = $this->recordFailureEvents();

        $this->subscriber()->onDomainEvent(new TestDomainEvent(
            resourceKey: 'test_entities',
            resourceId: (string) $entity->getId(),
            resourceLocale: 'en',
        ));

        self::assertFalse($entityManager->isOpen());
        self::assertSame([], $failures->getArrayCopy());
    }

    private function givenAFailingTranslator(RuntimeException $failure): void
    {
        $this->translator()->addMock(static fn (): bool => true, $failure, 2);
    }

    private function givenATranslatorClosing(EntityManagerInterface $entityManager): void
    {
        $this->translator()->addMock(
            static function () use ($entityManager): bool {
                $entityManager->close();

                return true;
            },
            new TranslatorResponse('uuid', [
                ['text' => 'Glossary term', 'sourceLanguage' => 'de', 'targetLanguage' => 'en'],
            ]),
            2,
        );
    }

    private function findActivity(EntityManagerInterface $entityManager, string $type): ?Activity
    {
        return $entityManager->getRepository(Activity::class)->findOneBy(['type' => $type]);
    }

    /**
     * @return ArrayObject<int, TranslationFailedEvent>
     */
    private function recordFailureEvents(): ArrayObject
    {
        /** @var ArrayObject<int, TranslationFailedEvent> $failures */
        $failures = new ArrayObject();

        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = self::getContainer()->get('event_dispatcher');
        $eventDispatcher->addListener(
            DomainEvent::class,
            static function (DomainEvent $event) use ($failures): void {
                if (true === $event instanceof TranslationFailedEvent) {
                    $failures[] = $event;
                }
            },
            -1000,
        );

        return $failures;
    }

    private function givenACopyLocaleRequest(): void
    {
        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push(new Request([
            'action' => 'copy_locale',
            'src' => 'de',
            'dest' => 'en',
            'translate' => 'true',
        ]));
    }

    private function prepareSchema(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->createSchema([
            $entityManager->getClassMetadata(TestEntity::class),
            $entityManager->getClassMetadata(TestDimensionContent::class),
            $entityManager->getClassMetadata(Activity::class),
        ]);

        return $entityManager;
    }

    private function createEntityWithCopiedLocale(EntityManagerInterface $entityManager): TestEntity
    {
        $entity = new TestEntity();

        $german = $this->addDimensionContent($entity, 'de');
        $german->setTemplateData(self::COPIED_DATA);

        $english = $this->addDimensionContent($entity, 'en');
        $english->setTemplateData(self::COPIED_DATA);

        $entityManager->persist($entity);
        $entityManager->persist($german);
        $entityManager->persist($english);
        $entityManager->flush();

        return $entity;
    }

    private function addDimensionContent(TestEntity $entity, string $locale): TestDimensionContent
    {
        $dimensionContent = new TestDimensionContent($entity);
        $dimensionContent->setLocale($locale);
        $dimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);
        $dimensionContent->setTemplateKey('default');
        $entity->addDimensionContent($dimensionContent);

        return $dimensionContent;
    }

    private function englishContent(TestEntity $entity): TestDimensionContent
    {
        foreach ($entity->getDimensionContents() as $dimensionContent) {
            if ('en' === $dimensionContent->getLocale()) {
                return $dimensionContent;
            }
        }

        self::fail('No english dimension content found.');
    }

    private function translator(): TestTranslator
    {
        /** @var TestTranslator $translator */
        $translator = self::getContainer()->get('sulu_ai_platform.translator');

        return $translator;
    }

    private function domainEventDispatcher(): DomainEventDispatcherInterface
    {
        /** @var DomainEventDispatcherInterface $dispatcher */
        $dispatcher = self::getContainer()->get('sulu_activity.domain_event_dispatcher');

        return $dispatcher;
    }

    private function subscriber(): CustomEntityTranslationSubscriber
    {
        /** @var CustomEntityTranslationSubscriber $subscriber */
        $subscriber = self::getContainer()->get('sulu_ai_entity_translation.translation_subscriber');

        return $subscriber;
    }
}
