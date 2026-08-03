<?php

declare(strict_types=1);

use Sulu\Bundle\AiBundle\Testing\TestTranslator\TestTranslator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $suluVersion = \interface_exists('Sulu\\Content\\Domain\\Model\\DimensionContentInterface') ? 'Sulu3' : 'Sulu26';
    $fixtureDirectory = \dirname(__DIR__, 2) . '/Fixtures/' . $suluVersion;

    $container->extension('framework', [
        'test' => true,
    ]);

    $container->extension('doctrine', [
        'dbal' => [
            'driver' => 'pdo_sqlite',
            'url' => 'sqlite:///:memory:',
            'charset' => 'utf8mb4',
        ],
        'orm' => [
            'mappings' => [
                'TestFixtures' => [
                    'type' => 'attribute',
                    'dir' => $fixtureDirectory,
                    'prefix' => 'PERSPEQTIVE\\SuluAiEntityTranslationBundle\\Tests\\Fixtures\\' . $suluVersion,
                    'is_bundle' => false,
                ],
            ],
        ],
    ]);

    $container->extension('sulu_core', [
        'content' => [
            'structure' => [
                'paths' => [
                    'test_entity' => [
                        'path' => \dirname(__DIR__) . '/templates',
                        'type' => 'test_entity',
                    ],
                ],
            ],
        ],
    ]);

    $container->extension('sulu_ai_platform', [
        'api_key' => 'test-api-key',
        'webhook' => [
            'secret' => 'test-webhook-secret',
        ],
    ]);

    // Replace the real translator, so tests never reach the sulu.ai platform.
    $container->services()
        ->set('sulu_ai_platform.translator', TestTranslator::class)
        ->public();
};
