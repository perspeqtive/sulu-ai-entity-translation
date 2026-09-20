<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Integration\Sulu3;

use ArrayObject;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Doctrine\ResourceKeyEntityRegistryInterface;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Domain\Event\TranslationFailedEvent;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Application\TestKernel;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures\TestDomainEvent;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\SuluVersion;
use PHPUnit\Framework\Attributes\DataProvider;
use Sulu\Bundle\ActivityBundle\Application\Dispatcher\DomainEventDispatcherInterface;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\ActivityBundle\Domain\Model\Activity;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use function sprintf;

class SkipBuiltInResourcesTest extends KernelTestCase
{
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
        $this->prepareActivitySchema();
    }

    private function prepareActivitySchema(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->createSchema([$entityManager->getClassMetadata(Activity::class)]);
    }

    public function testTheRegistryPicksUpBuiltInResources(): void
    {
        /** @var ResourceKeyEntityRegistryInterface $registry */
        $registry = self::getContainer()->get(ResourceKeyEntityRegistryInterface::class);

        self::assertNotNull($registry->findEntityClass('pages'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function builtInResourceKeyProvider(): iterable
    {
        yield 'pages' => ['pages'];
        yield 'articles' => ['articles'];
        yield 'snippets' => ['snippets'];
    }

    #[DataProvider('builtInResourceKeyProvider')]
    public function testLeavesBuiltInResourcesToSuluAi(string $resourceKey): void
    {
        /** @var ResourceKeyEntityRegistryInterface $registry */
        $registry = self::getContainer()->get(ResourceKeyEntityRegistryInterface::class);

        if (null === $registry->findEntityClass($resourceKey)) {
            self::markTestSkipped(sprintf('The test kernel has no "%s" entity.', $resourceKey));
        }

        $this->givenACopyLocaleRequest();
        $failures = $this->recordFailureEvents();

        /** @var DomainEventDispatcherInterface $domainEventDispatcher */
        $domainEventDispatcher = self::getContainer()->get('sulu_activity.domain_event_dispatcher');
        $domainEventDispatcher->dispatch(new TestDomainEvent(
            resourceKey: $resourceKey,
            resourceId: 'a1b2c3d4-0000-0000-0000-000000000000',
            resourceLocale: 'en',
        ));

        self::assertSame([], $failures->getArrayCopy());
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
}
