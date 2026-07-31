<?php

declare(strict_types=1);

use PERSPEQTIVE\SuluAiEntityTranslationBundle\Content\Sulu3\ContentRichEntityRepository as Sulu3ContentRichEntityRepository;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Content\Sulu26\ContentRichEntityRepository as Sulu26ContentRichEntityRepository;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Doctrine\ResourceKeyEntityRegistry;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\Doctrine\ResourceKeyEntityRegistryInterface;
use PERSPEQTIVE\SuluAiEntityTranslationBundle\EventListener\CustomEntityTranslationSubscriber;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

use function interface_exists;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // Sulu 2.6 and Sulu 3 expose their content model under different namespaces and name some
    // services differently. Referencing a class constant does not autoload the class, so only the
    // installed generation is ever loaded and no compiler pass is needed to pick between them.
    $isSulu3 = interface_exists('Sulu\\Content\\Domain\\Model\\DimensionContentInterface');

    $repositoryClass = $isSulu3
        ? Sulu3ContentRichEntityRepository::class
        : Sulu26ContentRichEntityRepository::class;

    $formMetadataLoader = $isSulu3
        ? 'sulu_admin.template_form_metadata_loader'
        : 'sulu_admin.structure_form_metadata_loader';

    $services->set(ResourceKeyEntityRegistry::class)
        ->args([
            service('doctrine.orm.entity_manager'),
        ]);

    $services->alias(ResourceKeyEntityRegistryInterface::class, ResourceKeyEntityRegistry::class);

    $services->set('sulu_ai_entity_translation.content_repository', $repositoryClass)
        ->args([
            service('doctrine.orm.entity_manager'),
            service(ResourceKeyEntityRegistryInterface::class),
        ]);

    $services->set('sulu_ai_entity_translation.translation_subscriber', CustomEntityTranslationSubscriber::class)
        ->args([
            service('sulu_ai_platform.translator'),
            service($formMetadataLoader),
            service('sulu_admin.metadata_provider_registry'),
            service('sulu_ai_entity_translation.content_repository'),
            service('request_stack'),
            service(SecurityCheckerInterface::class),
            service(ResourceKeyEntityRegistryInterface::class),
            service('sulu_activity.domain_event_dispatcher'),
            service('slugger'),
            service('logger'),
            param('sulu_ai_platform.property_type_translation_properties'),
        ])
        ->tag('kernel.event_subscriber')
        ->tag('monolog.logger', ['channel' => 'sulu_ai_entity_translation']);
};
