<?php

declare(strict_types=1);

use PERSPEQTIVE\SuluAiEntityTranslationBundle\Content\Sulu3\ContentRichEntityRepository as Sulu3ContentRichEntityRepository;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Content\Sulu26\ContentRichEntityRepository as Sulu26ContentRichEntityRepository;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Doctrine\ResourceKeyEntityRegistry;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Doctrine\ResourceKeyEntityRegistryInterface;

use function interface_exists;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(ResourceKeyEntityRegistry::class)
        ->args([
            service('doctrine.orm.entity_manager'),
        ]);

    $services->alias(ResourceKeyEntityRegistryInterface::class, ResourceKeyEntityRegistry::class);

    // Sulu 2.6 and Sulu 3 expose their content model under different namespaces. Referencing a
    // class constant does not autoload the class, so only the installed one is ever loaded and
    // no compiler pass is needed to pick between them.
    $repositoryClass = interface_exists('Sulu\\Content\\Domain\\Model\\DimensionContentInterface')
        ? Sulu3ContentRichEntityRepository::class
        : Sulu26ContentRichEntityRepository::class;

    $services->set('sulu_ai_entity_translation.content_repository', $repositoryClass)
        ->args([
            service('doctrine.orm.entity_manager'),
            service(ResourceKeyEntityRegistryInterface::class),
        ]);
};
