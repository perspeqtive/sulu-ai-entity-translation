<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\Mocks;

use PERSPEQTIVE\SuluAiEntityTranslationBundle\Doctrine\ResourceKeyEntityRegistryInterface;

final class MockResourceKeyEntityRegistry implements ResourceKeyEntityRegistryInterface
{
    /**
     * @param class-string|null $entityClass
     */
    public function __construct(
        private readonly ?string $entityClass = null,
    ) {
    }

    public function findEntityClass(string $resourceKey): ?string
    {
        return $this->entityClass;
    }

    public function getMap(): array
    {
        if (null === $this->entityClass) {
            return [];
        }

        return ['test_entities' => $this->entityClass];
    }
}
