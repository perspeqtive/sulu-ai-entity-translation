<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\Content\Sulu26;

use PERSPEQTIVE\SuluAiEntityTranslationBundle\Content\Sulu26\DimensionContentAdapter;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures\Sulu26\TestDimensionContent;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures\Sulu26\TestEntity;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\SuluVersion;
use PHPUnit\Framework\TestCase;

class DimensionContentAdapterTest extends TestCase
{
    protected function setUp(): void
    {
        if (SuluVersion::isSulu3()) {
            self::markTestSkipped('Requires Sulu 2.6.');
        }
    }

    public function testExposesTemplateKeyAndLocale(): void
    {
        $dimensionContent = $this->createDimensionContent();
        $dimensionContent->setTemplateKey('default');
        $dimensionContent->setLocale('en');

        $adapter = new DimensionContentAdapter($dimensionContent);

        self::assertSame('default', $adapter->getTemplateKey());
        self::assertSame('en', $adapter->getLocale());
    }

    public function testExposesTemplateData(): void
    {
        $dimensionContent = $this->createDimensionContent();
        $dimensionContent->setTemplateData(['title' => 'Glossary term']);

        $adapter = new DimensionContentAdapter($dimensionContent);

        self::assertSame(['title' => 'Glossary term'], $adapter->getTemplateData());
    }

    public function testWritesTemplateDataBackToTheDimensionContent(): void
    {
        $dimensionContent = $this->createDimensionContent();
        $adapter = new DimensionContentAdapter($dimensionContent);

        $adapter->setTemplateData(['title' => 'Glossarbegriff'], '');

        self::assertSame(['title' => 'Glossarbegriff'], $dimensionContent->getTemplateData());
    }

    public function testDoesNotHandleSeoAndExcerptData(): void
    {
        $adapter = new DimensionContentAdapter($this->createDimensionContent());

        $adapter->setSeoData(['title' => 'ignored']);
        $adapter->setExcerptData(['title' => 'ignored']);

        self::assertSame([], $adapter->getSeoData());
        self::assertSame([], $adapter->getExcerptData());
    }

    public function testExposesTheWrappedDimensionContent(): void
    {
        $dimensionContent = $this->createDimensionContent();

        $adapter = new DimensionContentAdapter($dimensionContent);

        self::assertSame($dimensionContent, $adapter->getDimensionContent());
    }

    public function testExposesTemplateType(): void
    {
        $adapter = new DimensionContentAdapter($this->createDimensionContent());

        self::assertSame('test_entity', $adapter->getTemplateType());
    }

    private function createDimensionContent(): TestDimensionContent
    {
        return new TestDimensionContent(new TestEntity());
    }
}
