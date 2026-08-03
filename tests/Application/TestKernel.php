<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Application;

use PERSPEQTIVE\SuluAiEntityTranslationBundle\SuluAiEntityTranslationBundle;
use Sulu\Bundle\AiBundle\SuluAiBundle;
use Sulu\Bundle\AiPlatformBundle\SuluAiPlatformBundle;
use Sulu\Bundle\ContentBundle\SuluContentBundle;
use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Symfony\AI\AiBundle\AiBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class TestKernel extends SuluTestKernel
{
    public function registerBundles(): iterable
    {
        /** @var list<BundleInterface> $bundles */
        $bundles = [...parent::registerBundles()];
        $bundles[] = new SuluContentBundle();
        $bundles[] = new AiBundle();
        // The platform bundle has to be registered before the AI bundle, it prepends its config.
        $bundles[] = new SuluAiPlatformBundle();
        $bundles[] = new SuluAiBundle();
        $bundles[] = new SuluAiEntityTranslationBundle();

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);

        $loader->load(__DIR__ . '/config/config.php');
    }
}
