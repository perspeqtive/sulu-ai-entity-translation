<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures;

use RuntimeException;
use Sulu\Bundle\AiPlatformBundle\Compatibility\ContentPersister\ContentAdapterInterface;
use Sulu\Bundle\AiPlatformBundle\Compatibility\ContentPersister\ContentRepositoryInterface;
use Throwable;

use function count;

/**
 * Records lookups so tests can assert whether the subscriber acted on an event at all.
 */
final class RecordingContentRepository implements ContentRepositoryInterface
{
    /**
     * @var list<array{id: string, locale: string, resourceKey: string}>
     */
    public array $lookups = [];

    public function __construct(
        private readonly ?Throwable $failure = null,
        private readonly ?ContentAdapterInterface $content = null,
    ) {
    }

    public function find(string $id, string $locale, string $resourceKey): ContentAdapterInterface
    {
        $this->lookups[] = ['id' => $id, 'locale' => $locale, 'resourceKey' => $resourceKey];

        if (null !== $this->failure) {
            throw $this->failure;
        }

        return $this->content ?? throw new RuntimeException('No content configured');
    }

    public function persist(ContentAdapterInterface $content, string $locale): void
    {
    }

    public function flush(): void
    {
    }

    public function wasQueried(): bool
    {
        return count($this->lookups) > 0;
    }
}
