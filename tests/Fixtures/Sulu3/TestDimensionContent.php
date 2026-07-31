<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures\Sulu3;

use Doctrine\ORM\Mapping as ORM;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\DimensionContentTrait;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Domain\Model\TemplateTrait;

/**
 * @implements DimensionContentInterface<TestEntity>
 */
#[ORM\Entity]
class TestDimensionContent implements DimensionContentInterface, TemplateInterface
{
    use DimensionContentTrait;
    use TemplateTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id = 0;

    #[ORM\ManyToOne(targetEntity: TestEntity::class, inversedBy: 'dimensionContents')]
    private TestEntity $resource;

    public function __construct(TestEntity $resource)
    {
        $this->resource = $resource;
    }

    public static function getResourceKey(): string
    {
        return 'test_entities';
    }

    public static function getTemplateType(): string
    {
        return 'test_entity';
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getResource(): TestEntity
    {
        return $this->resource;
    }
}
