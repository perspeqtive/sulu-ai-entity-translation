<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Integration;

use PERSPEQTIVE\SuluAiEntityTranslationBundle\Doctrine\ResourceKeyEntityRegistryInterface;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\EventListener\CustomEntityTranslationSubscriber;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Application\TestKernel;
use Sulu\Bundle\AiPlatformBundle\Compatibility\ContentPersister\ContentRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Proves that the service definitions reference services that actually exist. Several of the
 * identifiers differ between Sulu generations, so a booted container is the only honest check.
 */
class ServiceWiringTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testTheTranslationSubscriberIsWired(): void
    {
        self::bootKernel();

        $subscriber = self::getContainer()->get('sulu_ai_entity_translation.translation_subscriber');

        self::assertInstanceOf(CustomEntityTranslationSubscriber::class, $subscriber);
    }

    public function testTheContentRepositoryIsWired(): void
    {
        self::bootKernel();

        $repository = self::getContainer()->get('sulu_ai_entity_translation.content_repository');

        self::assertInstanceOf(ContentRepositoryInterface::class, $repository);
    }

    public function testTheResourceKeyRegistryIsWired(): void
    {
        self::bootKernel();

        $registry = self::getContainer()->get(ResourceKeyEntityRegistryInterface::class);

        self::assertInstanceOf(ResourceKeyEntityRegistryInterface::class, $registry);
    }
}
