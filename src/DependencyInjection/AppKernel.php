<?php declare(strict_types=1);

namespace Rector\NAI\DependencyInjection;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;

final class AppKernel extends Kernel
{
    /**
     * @var string
     */
    private $config;

    public function __construct(string $config)
    {
        // random_int is used to prevent container name duplication during tests
        parent::__construct('dev' . random_int(1, 1000), true);

        $this->config = $config;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/../config/config.yml');
        $loader->load($this->config);
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/_rector_narrow_intelligence_cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/_rector_narrow_intelligence_logs';
    }

    /**
     * @return BundleInterface[]
     */
    public function registerBundles(): array
    {
        return [];
    }
}
