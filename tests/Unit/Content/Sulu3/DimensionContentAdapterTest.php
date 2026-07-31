<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\Content\Sulu3;

use PERSPEQTIVE\SuluAiEntityTranslationBundle\Content\Sulu3\DimensionContentAdapter;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures\TestDimensionContent;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures\TestEntity;
use PHPUnit\Framework\TestCase;

class DimensionContentAdapterTest extends TestCase
{
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

    private function createDimensionContent(): TestDimensionContent
    {
        return new TestDimensionContent(new TestEntity());
    }
}
