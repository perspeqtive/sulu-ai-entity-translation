<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * @implements ContentRichEntityInterface<TestDimensionContent>
 */
#[ORM\Entity]
class TestEntity implements ContentRichEntityInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id = 0;

    /**
     * @var Collection<int, TestDimensionContent>
     */
    #[ORM\OneToMany(targetEntity: TestDimensionContent::class, mappedBy: 'resource')]
    private Collection $dimensionContents;

    public function __construct()
    {
        $this->dimensionContents = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDimensionContents(): Collection
    {
        return $this->dimensionContents;
    }

    public function createDimensionContent(): TestDimensionContent
    {
        return new TestDimensionContent($this);
    }

    /**
     * @param TestDimensionContent $dimensionContent
     */
    public function addDimensionContent(DimensionContentInterface $dimensionContent): void
    {
        $this->dimensionContents->add($dimensionContent);
    }

    /**
     * @param TestDimensionContent $dimensionContent
     */
    public function removeDimensionContent(DimensionContentInterface $dimensionContent): void
    {
        $this->dimensionContents->removeElement($dimensionContent);
    }
}
