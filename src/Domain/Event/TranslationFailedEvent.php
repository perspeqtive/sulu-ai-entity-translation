<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;

/**
 * Records in Sulu's activity log that an AI translation did not complete.
 *
 * The locale copy itself is already persisted when this is dispatched, so the editor keeps the
 * untranslated copy and can translate it manually.
 */
final class TranslationFailedEvent extends DomainEvent
{
    public function __construct(
        private readonly string $resourceKey,
        private readonly string $resourceId,
        private readonly ?string $resourceLocale,
        private readonly ?string $resourceTitle,
        private readonly string $reason,
    ) {
        parent::__construct();
    }

    public function getEventType(): string
    {
        return 'ai_translation_failed';
    }

    public function getEventContext(): array
    {
        return ['reason' => $this->reason];
    }

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function getResourceLocale(): ?string
    {
        return $this->resourceLocale;
    }

    public function getResourceTitle(): ?string
    {
        return $this->resourceTitle;
    }
}
