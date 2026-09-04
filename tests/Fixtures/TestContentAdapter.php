<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures;

use PERSPEQTIVE\SuluAiEntityTranslationBundle\Content\TemplateTypeAwareInterface;
use Sulu\Bundle\AiPlatformBundle\Compatibility\ContentPersister\ContentAdapterInterface;

/**
 * Lets a test reach handleContentTranslation(), where the base class handles failures itself.
 */
final class TestContentAdapter implements ContentAdapterInterface, TemplateTypeAwareInterface
{
    /**
     * @param array<string, mixed> $templateData
     */
    public function __construct(
        private array $templateData = ['title' => 'Glossary term'],
        private readonly ?string $templateKey = 'default',
        private readonly string $templateType = 'test_entity',
        private readonly ?string $locale = 'en',
    ) {
    }

    public function getTemplateKey(): ?string
    {
        return $this->templateKey;
    }

    public function getTemplateType(): string
    {
        return $this->templateType;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getTemplateData(): array
    {
        return $this->templateData;
    }

    public function getSeoData(): array
    {
        return [];
    }

    public function getExcerptData(): array
    {
        return [];
    }

    public function setTemplateData(array $data, string $routePropertyName): void
    {
        $this->templateData = $data;
    }

    public function setSeoData(array $data): void
    {
    }

    public function setExcerptData(array $data): void
    {
    }
}
