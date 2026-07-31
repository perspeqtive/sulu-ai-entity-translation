<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\EventListener;

use PERSPEQTIVE\SuluAiEntityTranslationBundle\Doctrine\ResourceKeyEntityRegistryInterface;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Domain\Event\TranslationFailedEvent;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\EventListener\CustomEntityTranslationSubscriber;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures\RecordingContentRepository;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures\TestDomainEvent;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use stdClass;
use Sulu\Bundle\ActivityBundle\Application\Dispatcher\DomainEventDispatcherInterface;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataLoaderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\AiBundle\Expert\Translator\TranslatorInterface;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
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

    public function testIgnoresResourcesThatAreNotContentRichEntities(): void
    {
        $repository = new RecordingContentRepository();
        $subscriber = $this->createSubscriber($repository, $this->copyLocaleRequest(), entityClass: null);

        $subscriber->onDomainEvent(new TestDomainEvent());

        self::assertFalse($repository->wasQueried());
    }

    public function testIgnoresRequestsThatAreNotCopyingALocale(): void
    {
        $repository = new RecordingContentRepository();
        $request = new Request(['action' => 'publish', 'translate' => 'true']);
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

    public function testIgnoresEventsWithoutATargetLocale(): void
    {
        $repository = new RecordingContentRepository();
        $subscriber = $this->createSubscriber($repository, $this->copyLocaleRequest());

        $subscriber->onDomainEvent(new TestDomainEvent(resourceLocale: null));

        self::assertFalse($repository->wasQueried());
    }

    public function testIgnoresItsOwnFailureEvents(): void
    {
        $repository = new RecordingContentRepository();
        $subscriber = $this->createSubscriber($repository, $this->copyLocaleRequest());

        $subscriber->onDomainEvent(new TranslationFailedEvent('test_entities', '1', 'en', null, 'boom'));

        self::assertFalse($repository->wasQueried());
    }

    public function testRecordsAFailureInsteadOfLettingTheRequestFail(): void
    {
        $repository = new RecordingContentRepository(new RuntimeException('Quota exceeded'));

        $dispatched = [];
        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(
            static function (DomainEvent $event) use (&$dispatched): DomainEvent {
                $dispatched[] = $event;

                return $event;
            },
        );

        $subscriber = $this->createSubscriber($repository, $this->copyLocaleRequest(), dispatcher: $dispatcher);

        $subscriber->onDomainEvent(new TestDomainEvent());

        self::assertTrue($repository->wasQueried());
        self::assertCount(1, $dispatched);
        self::assertInstanceOf(TranslationFailedEvent::class, $dispatched[0]);
        self::assertSame('test_entities', $dispatched[0]->getResourceKey());
        self::assertSame(['reason' => 'Quota exceeded'], $dispatched[0]->getEventContext());
    }

    private function copyLocaleRequest(): Request
    {
        return new Request(['action' => 'copy-locale', 'src' => 'de', 'dest' => 'en', 'translate' => 'true']);
    }

    /**
     * @param class-string|null $entityClass
     */
    private function createSubscriber(
        RecordingContentRepository $repository,
        Request $request,
        ?string $entityClass = stdClass::class,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): CustomEntityTranslationSubscriber {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $registry = $this->createMock(ResourceKeyEntityRegistryInterface::class);
        $registry->method('findEntityClass')->willReturn($entityClass);

        $securityChecker = $this->createMock(SecurityCheckerInterface::class);
        $securityChecker->method('hasPermission')->willReturn(true);

        return new CustomEntityTranslationSubscriber(
            $this->createMock(TranslatorInterface::class),
            $this->createMock(FormMetadataLoaderInterface::class),
            $this->createMock(MetadataProviderRegistry::class),
            $repository,
            $requestStack,
            $securityChecker,
            $registry,
            $dispatcher ?? $this->createMock(DomainEventDispatcherInterface::class),
            new AsciiSlugger(),
            new NullLogger(),
        );
    }
}
