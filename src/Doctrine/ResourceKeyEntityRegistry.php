<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\ClassMetadata;

use function is_a;
use function method_exists;

/**
 * Maps Sulu resource keys onto their content rich entity classes.
 *
 * The map is derived from Doctrine metadata: every entity implementing ContentRichEntityInterface
 * is associated with a dimension content entity, whose static getResourceKey() yields the key.
 *
 * The Sulu interfaces are referenced by name rather than by import, because their namespace
 * changed between Sulu 2.6 (Sulu\Bundle\ContentBundle\Content\Domain\Model) and Sulu 3
 * (Sulu\Content\Domain\Model). Keeping this class free of those imports lets it serve both.
 */
final class ResourceKeyEntityRegistry
{
    private const CONTENT_RICH_ENTITY_INTERFACES = [
        'Sulu\\Content\\Domain\\Model\\ContentRichEntityInterface',
        'Sulu\\Bundle\\ContentBundle\\Content\\Domain\\Model\\ContentRichEntityInterface',
    ];

    private const DIMENSION_CONTENT_INTERFACES = [
        'Sulu\\Content\\Domain\\Model\\DimensionContentInterface',
        'Sulu\\Bundle\\ContentBundle\\Content\\Domain\\Model\\DimensionContentInterface',
    ];

    /**
     * @var array<string, class-string>|null
     */
    private ?array $map = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return class-string|null
     */
    public function findEntityClass(string $resourceKey): ?string
    {
        return $this->getMap()[$resourceKey] ?? null;
    }

    /**
     * @return array<string, class-string>
     */
    public function getMap(): array
    {
        if (null !== $this->map) {
            return $this->map;
        }

        $map = [];

        foreach ($this->entityManager->getMetadataFactory()->getAllMetadata() as $metadata) {
            $entityClass = $metadata->getName();

            if (!$this->implementsAny($entityClass, self::CONTENT_RICH_ENTITY_INTERFACES)) {
                continue;
            }

            $resourceKey = $this->resolveResourceKey($metadata);

            if (null !== $resourceKey) {
                $map[$resourceKey] = $entityClass;
            }
        }

        return $this->map = $map;
    }

    /**
     * @param ClassMetadata<object> $metadata
     */
    private function resolveResourceKey(ClassMetadata $metadata): ?string
    {
        foreach ($metadata->getAssociationNames() as $associationName) {
            $targetClass = $metadata->getAssociationTargetClass($associationName);

            if (null === $targetClass || !$this->implementsAny($targetClass, self::DIMENSION_CONTENT_INTERFACES)) {
                continue;
            }

            if (!method_exists($targetClass, 'getResourceKey')) {
                continue;
            }

            /** @var string $resourceKey */
            $resourceKey = $targetClass::getResourceKey();

            return $resourceKey;
        }

        return null;
    }

    /**
     * @param list<string> $interfaces
     */
    private function implementsAny(string $class, array $interfaces): bool
    {
        foreach ($interfaces as $interface) {
            if (is_a($class, $interface, true)) {
                return true;
            }
        }

        return false;
    }
}
