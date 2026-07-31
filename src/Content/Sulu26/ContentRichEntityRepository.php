<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Content\Sulu26;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Doctrine\ResourceKeyEntityRegistryInterface;
use RuntimeException;
use Sulu\Bundle\AiPlatformBundle\Compatibility\ContentPersister\ContentAdapterInterface;
use Sulu\Bundle\AiPlatformBundle\Compatibility\ContentPersister\ContentRepositoryInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;

use function sprintf;

/**
 * Reads and writes localized content of arbitrary Sulu content rich entities.
 *
 * Sulu's own implementation only knows pages, snippets and articles. This one resolves the
 * entity class from its resource key, which makes it work for any custom entity.
 */
final readonly class ContentRichEntityRepository implements ContentRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ResourceKeyEntityRegistryInterface $registry,
    ) {
    }

    public function find(string $id, string $locale, string $resourceKey): ContentAdapterInterface
    {
        $entityClass = $this->registry->findEntityClass($resourceKey);

        if (null === $entityClass) {
            throw new InvalidArgumentException(sprintf('Unsupported resource key "%s"', $resourceKey));
        }

        $entity = $this->entityManager->find($entityClass, $id);

        if (!$entity instanceof ContentRichEntityInterface) {
            throw new RuntimeException(sprintf('Entity "%s" with id "%s" not found', $entityClass, $id));
        }

        foreach ($entity->getDimensionContents() as $dimensionContent) {
            if (DimensionContentInterface::STAGE_DRAFT !== $dimensionContent->getStage()) {
                continue;
            }

            if ($dimensionContent->getLocale() !== $locale) {
                continue;
            }

            if (!$dimensionContent instanceof TemplateInterface) {
                continue;
            }

            return new DimensionContentAdapter($dimensionContent);
        }

        throw new RuntimeException(sprintf(
            'Draft dimension content for resource "%s" with id "%s" and locale "%s" not found',
            $resourceKey,
            $id,
            $locale,
        ));
    }

    public function persist(ContentAdapterInterface $content, string $locale): void
    {
        if (!$content instanceof DimensionContentAdapter) {
            throw new InvalidArgumentException(sprintf(
                'Expected content to be an instance of %s, got %s',
                DimensionContentAdapter::class,
                $content::class,
            ));
        }

        $this->entityManager->persist($content->getDimensionContent());
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }
}
