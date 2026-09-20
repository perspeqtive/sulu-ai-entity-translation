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

    // Sulu 2.6 registers template directories under sulu_core, Sulu 3 under sulu_admin.
    $templateDirectory = \dirname(__DIR__) . '/templates';

    if ('Sulu3' === $suluVersion) {
        $container->extension('sulu_admin', [
            'templates' => [
                'test_entity' => [
                    'default_type' => 'default',
                    'directories' => ['test_entity' => $templateDirectory],
                ],
            ],
        ]);
    } else {
        $container->extension('sulu_core', [
            'content' => [
                'structure' => [
                    'paths' => [
                        'test_entity' => [
                            'path' => $templateDirectory,
                            'type' => 'test_entity',
                        ],
                    ],
                ],
            ],
        ]);
    }

    $suluAIPlatformConfig = [
        'api_key' => 'test-api-key',
        'text_field_types' => ['text_line', 'text_area', 'custom_text_line'],
        'html_field_types' => ['text_editor', 'custom_editor'],
        'webhook' => [
            'secret' => 'test-webhook-secret',
        ],
    ];

    if ('Sulu3' === $suluVersion) {
        $suluAIPlatformConfig['contact_email'] = 'test@example.com';
    }
    $container->extension('sulu_ai_platform', $suluAIPlatformConfig);

    // Replace the real translator, so tests never reach the sulu.ai platform.
    $container->services()
        ->set('sulu_ai_platform.translator', TestTranslator::class)
        ->public();
};
