<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Doctrine;

interface ResourceKeyEntityRegistryInterface
{
    /**
     * @return class-string|null
     */
    public function findEntityClass(string $resourceKey): ?string;

    /**
     * @return array<string, class-string>
     */
    public function getMap(): array;
}
