<?php

declare(strict_types=1);

use PERSPEQTIVE\SuluAiEntityTranslationBundle\Doctrine\ResourceKeyEntityRegistry;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(ResourceKeyEntityRegistry::class)
        ->args([
            service('doctrine.orm.entity_manager'),
        ]);
};
