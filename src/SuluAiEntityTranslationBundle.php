<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function interface_exists;

class SuluAiEntityTranslationBundle extends AbstractBundle
{
    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import(__DIR__ . '/../config/services.yaml');

        // Sulu 2.6 and Sulu 3 keep their content model in different namespaces and name the form
        // metadata loader differently, so the services touching either are defined per generation.
        $container->import(interface_exists('Sulu\\Content\\Domain\\Model\\DimensionContentInterface')
            ? __DIR__ . '/../config/services_sulu3.yaml'
            : __DIR__ . '/../config/services_sulu26.yaml');
    }
}
