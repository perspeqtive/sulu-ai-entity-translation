<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\Mocks;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataLoaderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataInterface;
use Throwable;

final class MockFormMetadataLoader implements FormMetadataLoaderInterface
{
    public function __construct(
        private readonly ?Throwable $failure = null,
        private readonly ?MetadataInterface $metadata = null,
    ) {
    }

    public function getMetadata(string $key, string $locale, array $metadataOptions): ?MetadataInterface
    {
        if (null !== $this->failure) {
            throw $this->failure;
        }

        return $this->metadata;
    }
}
