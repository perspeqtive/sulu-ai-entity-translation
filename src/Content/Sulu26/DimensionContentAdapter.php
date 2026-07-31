<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Content\Sulu26;

use Sulu\Bundle\AiPlatformBundle\Compatibility\ContentPersister\ContentAdapterInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;

/**
 * Adapts a Sulu 2.6 dimension content to the contract Sulu AI expects.
 *
 * SEO and excerpt data are intentionally not handled: this bundle translates template
 * properties only. The methods stay as no-ops, mirroring Sulu's own snippet decorator.
 */
final readonly class DimensionContentAdapter implements ContentAdapterInterface
{
    public function __construct(
        private TemplateInterface&DimensionContentInterface $dimensionContent,
    ) {
    }

    public function getDimensionContent(): TemplateInterface&DimensionContentInterface
    {
        return $this->dimensionContent;
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
