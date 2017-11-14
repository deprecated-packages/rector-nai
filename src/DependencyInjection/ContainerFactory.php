<?php declare(strict_types=1);

namespace Rector\NAI\DependencyInjection;

use MyProject\Container;
use Psr\Container\ContainerInterface;

final class ContainerFactory
{
    /**
     * @return Container|ContainerInterface
     */
    public function createWithConfig(string $config): ContainerInterface
    {
        $appKernel = new AppKernel($config);
        $appKernel->boot();

        return $appKernel->getContainer();
    }
}
