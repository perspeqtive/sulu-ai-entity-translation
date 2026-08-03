<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Application;

use PERSPEQTIVE\SuluAiEntityTranslationBundle\SuluAiEntityTranslationBundle;
use RuntimeException;
use Sulu\Bundle\AiBundle\SuluAiBundle;
use Sulu\Bundle\AiPlatformBundle\SuluAiPlatformBundle;
use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Symfony\AI\AiBundle\AiBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

use function class_exists;

class TestKernel extends SuluTestKernel
{
    /**
     * Sulu 2.6 ships the content implementation as a separate bundle, Sulu 3 has it in core under
     * a different namespace. Only the installed one can be instantiated.
     */
    private const CONTENT_BUNDLES = [
        'Sulu\\Content\\Infrastructure\\Symfony\\HttpKernel\\SuluContentBundle',
        'Sulu\\Bundle\\ContentBundle\\SuluContentBundle',
    ];

    /**
     * Sulu AI's own content repository depends on the snippet repository, which the Sulu 3 test
     * kernel does not register on its own.
     */
    private const OPTIONAL_BUNDLES = [
        'Sulu\\Snippet\\Infrastructure\\Symfony\\HttpKernel\\SuluSnippetBundle',
    ];

    public function registerBundles(): iterable
    {
        /** @var list<BundleInterface> $bundles */
        $bundles = [...parent::registerBundles()];
        $contentBundle = $this->createContentBundle($bundles);

        if (null !== $contentBundle) {
            $bundles[] = $contentBundle;
        }

        foreach (self::OPTIONAL_BUNDLES as $class) {
            if (class_exists($class) && !$this->isRegistered($bundles, $class)) {
                $bundles[] = new $class();
            }
        }

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

    /**
     * Returns null when the parent kernel already registers it, which Sulu 3 does.
     *
     * @param list<BundleInterface> $registered
     */
    private function createContentBundle(array $registered): ?BundleInterface
    {
        if ($this->isRegistered($registered, ...self::CONTENT_BUNDLES)) {
            return null;
        }

        foreach (self::CONTENT_BUNDLES as $class) {
            if (class_exists($class)) {
                return new $class();
            }
        }

        throw new RuntimeException('No Sulu content bundle found.');
    }

    /**
     * @param list<BundleInterface> $registered
     */
    private function isRegistered(array $registered, string ...$classes): bool
    {
        foreach ($registered as $bundle) {
            foreach ($classes as $class) {
                if ($bundle instanceof $class) {
                    return true;
                }
            }
        }

        return false;
    }
}
