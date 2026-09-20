<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Domain\Event\TranslationFailedEvent;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\EventListener\CustomEntityTranslationSubscriber;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures\RecordingContentRepository;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures\TestContentAdapter;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures\TestDomainEvent;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\Mocks\MockDomainEventDispatcher;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\Mocks\MockEntityManagerFactory;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\Mocks\MockFormMetadataLoader;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\Mocks\MockResourceKeyEntityRegistry;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\Mocks\MockSecurityChecker;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\Mocks\MockTranslator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use stdClass;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\String\Slugger\AsciiSlugger;

use function iterator_to_array;

class CustomEntityTranslationSubscriberTest extends TestCase
{
    public function testSubscribesToAllDomainEvents(): void
    {
        self::assertArrayHasKey(
            DomainEvent::class,
            iterator_to_array(CustomEntityTranslationSubscriber::getSubscribedEvents()),
        );
    }

    #[DataProvider('provideCopyLocaleParameter')]
    public function testIgnoresResourcesThatAreNotContentRichEntities(string $actionName): void
    {
        $repository = new RecordingContentRepository();
        $subscriber = $this->createSubscriber($repository, $this->copyLocaleRequest($actionName), entityClass: null);

        $subscriber->onDomainEvent(new TestDomainEvent());

        self::assertFalse($repository->wasQueried());
    }

    #[DataProvider('provideCopyLocaleParameter')]
    public function testIgnoresResourcesSuluAiTranslatesItself(string $actionName): void
    {
        $repository = new RecordingContentRepository();
        $subscriber = $this->createSubscriber($repository, $this->copyLocaleRequest($actionName), builtInResourceKeys: ['pages']);

        $subscriber->onDomainEvent(new TestDomainEvent(resourceKey: 'pages'));

        self::assertFalse($repository->wasQueried());
    }

    public function testIgnoresRequestsThatAreNotCopyingALocale(): void
    {
        $repository = new RecordingContentRepository();
        $request = $this->copyLocaleRequest('something-unknown');
        $subscriber = $this->createSubscriber($repository, $request);

        $subscriber->onDomainEvent(new TestDomainEvent());

        self::assertFalse($repository->wasQueried());
    }

    public function testIgnoresRequestsWithoutTheTranslateFlag(): void
    {
        $repository = new RecordingContentRepository();
        $request = new Request(['action' => 'copy-locale', 'src' => 'de', 'dest' => 'en']);
        $subscriber = $this->createSubscriber($repository, $request);

        $subscriber->onDomainEvent(new TestDomainEvent());

        self::assertFalse($repository->wasQueried());
    }

    #[DataProvider('provideCopyLocaleParameter')]
    public function testIgnoresEventsWithoutATargetLocale(string $actionName): void
    {
        $repository = new RecordingContentRepository();
        $subscriber = $this->createSubscriber($repository, $this->copyLocaleRequest($actionName));

        $subscriber->onDomainEvent(new TestDomainEvent(resourceLocale: null));

        self::assertFalse($repository->wasQueried());
    }

    #[DataProvider('provideCopyLocaleParameter')]
    public function testIgnoresItsOwnFailureEvents(string $actionName): void
    {
        $repository = new RecordingContentRepository();
        $subscriber = $this->createSubscriber($repository, $this->copyLocaleRequest($actionName));

        $subscriber->onDomainEvent(new TranslationFailedEvent('test_entities', '1', 'en', null, 'boom'));

        self::assertFalse($repository->wasQueried());
    }

    #[DataProvider('provideCopyLocaleParameter')]
    public function testRecordsAFailureInsteadOfLettingTheRequestFail(string $actionName): void
    {
        $repository = new RecordingContentRepository(new RuntimeException('Quota exceeded'));
        $dispatcher = new MockDomainEventDispatcher();

        $subscriber = $this->createSubscriber($repository, $this->copyLocaleRequest($actionName), dispatcher: $dispatcher);

        $subscriber->onDomainEvent(new TestDomainEvent());

        self::assertTrue($repository->wasQueried());
        self::assertCount(1, $dispatcher->dispatched);
        self::assertInstanceOf(TranslationFailedEvent::class, $dispatcher->dispatched[0]);
        self::assertSame('test_entities', $dispatcher->dispatched[0]->getResourceKey());
        self::assertSame(['reason' => 'Quota exceeded'], $dispatcher->dispatched[0]->getEventContext());
    }

    #[DataProvider('provideCopyLocaleParameter')]
    public function testRecordsAFailureRaisedWhileTranslating(string $actionName): void
    {
        $repository = new RecordingContentRepository(content: new TestContentAdapter());
        $dispatcher = new MockDomainEventDispatcher();

        $subscriber = $this->createSubscriber(
            $repository,
            $this->copyLocaleRequest($actionName),
            dispatcher: $dispatcher,
            formMetadataLoader: new MockFormMetadataLoader(new RuntimeException('Quota exceeded')),
        );

        $subscriber->onDomainEvent(new TestDomainEvent());

        self::assertCount(1, $dispatcher->dispatched);
        self::assertInstanceOf(TranslationFailedEvent::class, $dispatcher->dispatched[0]);
        self::assertSame('test_entities', $dispatcher->dispatched[0]->getResourceKey());
        self::assertSame('1', $dispatcher->dispatched[0]->getResourceId());
        self::assertSame('en', $dispatcher->dispatched[0]->getResourceLocale());
        self::assertSame(['reason' => 'Quota exceeded'], $dispatcher->dispatched[0]->getEventContext());
    }

    #[DataProvider('provideCopyLocaleParameter')]
    public function testSkipsTheActivityLogWhenTheEntityManagerIsClosed(string $actionName): void
    {
        $repository = new RecordingContentRepository(content: new TestContentAdapter());
        $dispatcher = new MockDomainEventDispatcher();

        $subscriber = $this->createSubscriber(
            $repository,
            $this->copyLocaleRequest($actionName),
            dispatcher: $dispatcher,
            formMetadataLoader: new MockFormMetadataLoader(new RuntimeException('Deadlock')),
            entityManager: MockEntityManagerFactory::closed(),
        );

        $subscriber->onDomainEvent(new TestDomainEvent());

        self::assertSame([], $dispatcher->dispatched);
    }

    private function copyLocaleRequest(string $actionName = 'copy-locale'): Request
    {
        return new Request(['action' => $actionName, 'src' => 'de', 'dest' => 'en', 'translate' => 'true']);
    }

    /**
     * @param class-string|null $entityClass
     * @param list<string> $builtInResourceKeys
     */
    private function createSubscriber(
        RecordingContentRepository $repository,
        Request $request,
        ?string $entityClass = stdClass::class,
        ?MockDomainEventDispatcher $dispatcher = null,
        array $builtInResourceKeys = [],
        ?MockFormMetadataLoader $formMetadataLoader = null,
        ?EntityManagerInterface $entityManager = null,
    ): CustomEntityTranslationSubscriber {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new CustomEntityTranslationSubscriber(
            new MockTranslator(),
            $formMetadataLoader ?? new MockFormMetadataLoader(),
            new MetadataProviderRegistry(new ServiceLocator([])),
            $repository,
            $requestStack,
            new MockSecurityChecker(),
            new MockResourceKeyEntityRegistry($entityClass),
            $dispatcher ?? new MockDomainEventDispatcher(),
            $entityManager ?? MockEntityManagerFactory::open(),
            new AsciiSlugger(),
            new NullLogger(),
            $builtInResourceKeys,
        );
    }

    /**
     * @return array<string, list<string>>
     */
    public static function provideCopyLocaleParameter(): array
    {
        return [
            'sulu-2' => ['copy-locale'],
            'sulu-3' => ['copy_locale'],
        ];
    }
}
