<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Integration\Sulu3;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\EventListener\CustomEntityTranslationSubscriber;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Application\TestKernel;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures\Sulu3\TestDimensionContent;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures\Sulu3\TestEntity;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Fixtures\TestDomainEvent;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\SuluVersion;
use Sulu\Bundle\AiBundle\Expert\Translator\TranslatorResponse;
use Sulu\Bundle\AiBundle\Testing\TestTranslator\TestTranslator;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Drives the whole pipeline: a copied locale is discovered through its domain event, its fields
 * are derived from the template metadata, translated and written back to the dimension content.
 */
class TranslateCopiedLocaleTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected function setUp(): void
    {
        if (!SuluVersion::isSulu3()) {
            self::markTestSkipped('Requires Sulu 3.');
        }

        self::bootKernel();
    }

    public function testTranslatesTheCopiedLocale(): void
    {
        $entityManager = $this->prepareSchema();
        $entity = $this->createEntityWithCopiedLocale($entityManager);

        $this->mockTranslations();
        $this->givenACopyLocaleRequest();

        $this->subscriber()->onDomainEvent(new TestDomainEvent(
            resourceKey: 'test_entities',
            resourceId: (string) $entity->getId(),
            resourceLocale: 'en',
        ));

        self::assertSame(
            ['title' => 'Glossary term', 'description' => '<p>An explanation</p>', 'reference' => null],
            $this->englishContent($entity)->getTemplateData(),
        );
    }

    public function testTranslatesFieldsOfConfiguredProjectSpecificTypes(): void
    {
        $entityManager = $this->prepareSchema();
        $entity = $this->createEntityWithCopiedLocale($entityManager, 'custom_types');

        $this->mockTranslations();
        $this->givenACopyLocaleRequest();

        $this->subscriber()->onDomainEvent(new TestDomainEvent(
            resourceKey: 'test_entities',
            resourceId: (string) $entity->getId(),
            resourceLocale: 'en',
        ));

        self::assertSame(
            ['title' => 'Glossary term', 'description' => '<p>An explanation</p>', 'reference' => null],
            $this->englishContent($entity)->getTemplateData(),
        );
    }

    private function mockTranslations(): void
    {
        /** @var TestTranslator $translator */
        $translator = self::getContainer()->get('sulu_ai_platform.translator');

        $translator->addMock(
            static fn (array $texts, string $target, ?string $source, bool $html): bool => !$html,
            new TranslatorResponse('uuid', [
                ['text' => 'Glossary term', 'sourceLanguage' => 'de', 'targetLanguage' => 'en'],
            ]),
        );

        $translator->addMock(
            static fn (array $texts, string $target, ?string $source, bool $html): bool => $html,
            new TranslatorResponse('uuid', [
                ['text' => '<p>An explanation</p>', 'sourceLanguage' => 'de', 'targetLanguage' => 'en'],
            ]),
        );
    }

    private function givenACopyLocaleRequest(): void
    {
        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push(new Request([
            'action' => 'copy-locale',
            'src' => 'de',
            'dest' => 'en',
            'translate' => 'true',
        ]));
    }

    private function prepareSchema(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->createSchema([
            $entityManager->getClassMetadata(TestEntity::class),
            $entityManager->getClassMetadata(TestDimensionContent::class),
        ]);

        return $entityManager;
    }

    private function createEntityWithCopiedLocale(EntityManagerInterface $entityManager, string $templateKey = 'default'): TestEntity
    {
        $entity = new TestEntity();

        $german = $this->addDimensionContent($entity, 'de', $templateKey);
        $german->setTemplateData(['title' => 'Glossarbegriff', 'description' => '<p>Eine Erklärung</p>', 'reference' => null]);

        // This is what copy-locale leaves behind: the source content under the target locale.
        $english = $this->addDimensionContent($entity, 'en', $templateKey);
        $english->setTemplateData(['title' => 'Glossarbegriff', 'description' => '<p>Eine Erklärung</p>', 'reference' => null]);

        $entityManager->persist($entity);
        $entityManager->persist($german);
        $entityManager->persist($english);
        $entityManager->flush();

        return $entity;
    }

    private function addDimensionContent(TestEntity $entity, string $locale, string $templateKey): TestDimensionContent
    {
        $dimensionContent = new TestDimensionContent($entity);
        $dimensionContent->setLocale($locale);
        $dimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);
        $dimensionContent->setTemplateKey($templateKey);
        $entity->addDimensionContent($dimensionContent);

        return $dimensionContent;
    }

    private function englishContent(TestEntity $entity): TestDimensionContent
    {
        foreach ($entity->getDimensionContents() as $dimensionContent) {
            if ('en' === $dimensionContent->getLocale()) {
                return $dimensionContent;
            }
        }

        self::fail('No english dimension content found.');
    }

    private function subscriber(): CustomEntityTranslationSubscriber
    {
        /** @var CustomEntityTranslationSubscriber $subscriber */
        $subscriber = self::getContainer()->get('sulu_ai_entity_translation.translation_subscriber');

        return $subscriber;
    }
}
