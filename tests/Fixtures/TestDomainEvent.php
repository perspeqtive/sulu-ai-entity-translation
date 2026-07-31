<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;

final class TestDomainEvent extends DomainEvent
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private readonly string $resourceKey = 'test_entities',
        private readonly string $resourceId = '1',
        private readonly ?string $resourceLocale = 'en',
        private readonly array $context = ['sourceLocale' => 'de'],
    ) {
        parent::__construct();
    }

    public function getEventType(): string
    {
        return 'translation_copied';
    }

    public function getEventContext(): array
    {
        return $this->context;
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
}
