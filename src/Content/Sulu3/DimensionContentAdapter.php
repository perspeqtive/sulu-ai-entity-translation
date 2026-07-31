<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Content\Sulu3;

use PERSPEQTIVE\SuluAiEntityTranslationBundle\Content\TemplateTypeAwareInterface;
use Sulu\Bundle\AiPlatformBundle\Compatibility\ContentPersister\ContentAdapterInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\TemplateInterface;

/**
 * Adapts a Sulu 3 dimension content to the contract Sulu AI expects.
 *
 * SEO and excerpt data are intentionally not handled: this bundle translates template
 * properties only. The methods stay as no-ops, mirroring Sulu's own snippet decorator.
 */
final readonly class DimensionContentAdapter implements ContentAdapterInterface, TemplateTypeAwareInterface
{
    public function __construct(
        private TemplateInterface&DimensionContentInterface $dimensionContent,
    ) {
    }

    public function getDimensionContent(): TemplateInterface&DimensionContentInterface
    {
        return $this->dimensionContent;
    }

    /**
     * The template type identifies the form metadata set of the entity, which is what Sulu AI
     * needs to discover translatable fields. It is not the same as the resource key.
     */
    public function getTemplateType(): string
    {
        return $this->dimensionContent::getTemplateType();
    }

    public function getTemplateKey(): ?string
    {
        return $this->dimensionContent->getTemplateKey();
    }

    public function getLocale(): ?string
    {
        return $this->dimensionContent->getLocale();
    }

    public function getTemplateData(): array
    {
        return $this->dimensionContent->getTemplateData();
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
        $this->dimensionContent->setTemplateData($data);
    }

    public function setSeoData(array $data): void
    {
    }

    public function setExcerptData(array $data): void
    {
    }
}
