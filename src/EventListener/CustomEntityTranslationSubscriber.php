<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Content\TemplateTypeAwareInterface;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Doctrine\ResourceKeyEntityRegistryInterface;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Domain\Event\TranslationFailedEvent;
use Psr\Log\LoggerInterface;
use Sulu\Bundle\ActivityBundle\Application\Dispatcher\DomainEventDispatcherInterface;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataLoaderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\AiBundle\Expert\Translator\TranslatorInterface;
use Sulu\Bundle\AiPlatformBundle\Compatibility\ContentPersister\ContentRepositoryInterface;
use Sulu\Bundle\AiPlatformBundle\Features\FullContentTranslation\AbstractFullContentTranslationSubscriber;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\String\Slugger\SluggerInterface;
use Throwable;

use function implode;
use function in_array;
use function is_string;
use function rtrim;

/**
 * Extends Sulu AI's full content translation to arbitrary Sulu custom entities.
 *
 * Sulu's own subscribers are bound to the page, article and snippet events. Custom entities each
 * dispatch their own domain event class, which cannot be enumerated up front. Sulu dispatches
 * every domain event under the name DomainEvent::class as well, so this subscriber listens there
 * and decides by resource key whether the event belongs to a content rich entity.
 */
final class CustomEntityTranslationSubscriber extends AbstractFullContentTranslationSubscriber implements EventSubscriberInterface
{
    private ?DomainEvent $currentEvent = null;

    /**
     * @param list<string> $builtInResourceKeys
     * @param array<string, list<string>> $propertyTypeTranslationProperties
     */
    public function __construct(
        TranslatorInterface $translator,
        FormMetadataLoaderInterface $formMetadataLoader,
        MetadataProviderRegistry $metadataProviderRegistry,
        ContentRepositoryInterface $contentRepository,
        RequestStack $requestStack,
        SecurityCheckerInterface $securityChecker,
        private readonly ResourceKeyEntityRegistryInterface $registry,
        private readonly DomainEventDispatcherInterface $domainEventDispatcher,
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger,
        private readonly LoggerInterface $logger,
        private readonly array $builtInResourceKeys = [],
        array $propertyTypeTranslationProperties = [],
    ) {
        parent::__construct(
            $translator,
            $formMetadataLoader,
            $metadataProviderRegistry,
            $contentRepository,
            $requestStack,
            $securityChecker,
            $propertyTypeTranslationProperties,
        );
    }

    /**
     * @return iterable<string, string>
     */
    public static function getSubscribedEvents(): iterable
    {
        yield DomainEvent::class => 'onDomainEvent';
    }

    public function onDomainEvent(DomainEvent $event): void
    {
        // Dispatching the failure event below re-enters this listener.
        if (true === $event instanceof TranslationFailedEvent) {
            return;
        }

        if (false === $this->isCopyLocaleRequest()) {
            return;
        }

        $resourceKey = $event->getResourceKey();

        if (true === in_array($resourceKey, $this->builtInResourceKeys, true)) {
            return;
        }

        if (null === $this->registry->findEntityClass($resourceKey)) {
            return;
        }

        $targetLocale = $event->getResourceLocale();

        if (null === $targetLocale) {
            return;
        }

        $this->currentEvent = $event;

        try {
            $this->translate($event, $resourceKey, $targetLocale);
        } catch (Throwable $throwable) {
            $this->onTranslationFailure($throwable, '', '', $targetLocale);
        } finally {
            $this->currentEvent = null;
        }
    }

    protected function generateUrl(array $parts, string $parentPath, string $urlFieldType, string $locale, ?string $routeSchema): string
    {
        $slug = $this->slugger->slug(implode('-', $parts), '-', $locale)->lower()->toString();

        return rtrim($parentPath, '/') . '/' . $slug;
    }

    private function translate(DomainEvent $event, string $resourceKey, string $targetLocale): void
    {
        $adapter = $this->contentRepository->find($event->getResourceId(), $targetLocale, $resourceKey);

        $templateKey = $adapter->getTemplateKey();

        if (null === $templateKey || false === $adapter instanceof TemplateTypeAwareInterface) {
            return;
        }

        $this->handleContentTranslation(
            $adapter->getTemplateType(),
            $adapter,
            $targetLocale,
            $this->resolveSourceLocale($event),
            $adapter->getTemplateData(),
            $templateKey,
        );
    }

    protected function onTranslationFailure(
        Throwable $throwable,
        string $resourceType,
        string $structureType,
        string $resourceLocale,
    ): void {
        $event = $this->currentEvent;

        $this->logger->error('AI translation of a copied locale failed.', [
            'resourceKey' => $event?->getResourceKey(),
            'resourceId' => $event?->getResourceId(),
            'resourceType' => $resourceType,
            'structureType' => $structureType,
            'locale' => $resourceLocale,
            'exception' => $throwable,
        ]);

        if (null === $event) {
            return;
        }

        if (false === $this->entityManager->isOpen()) {
            return;
        }

        $this->domainEventDispatcher->dispatch(new TranslationFailedEvent(
            $event->getResourceKey(),
            $event->getResourceId(),
            $resourceLocale,
            $event->getResourceTitle(),
            $throwable->getMessage(),
        ));
    }

    private function isCopyLocaleRequest(): bool
    {
        $request = $this->requestStack->getMainRequest();

        if (false === $request instanceof Request) {
            return false;
        }

        return 'copy-locale' === $request->query->get('action')
            && 'true' === $request->query->get('translate');
    }

    private function resolveSourceLocale(DomainEvent $event): ?string
    {
        $sourceLocale = $event->getEventContext()['sourceLocale'] ?? null;

        if (true === is_string($sourceLocale)) {
            return $sourceLocale;
        }

        $request = $this->requestStack->getMainRequest();
        $sourceLocale = $request?->query->get('src');

        if (true === is_string($sourceLocale)) {
            return $sourceLocale;
        }

        return null;
    }
}
