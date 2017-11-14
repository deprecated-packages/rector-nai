<?php declare(strict_types=1);

namespace Rector\NAI\Github;

use Cache\Adapter\Filesystem\FilesystemCachePool;
use Github\Client;
use Symplify\PackageBuilder\Adapter\Symfony\Parameter\ParameterProvider;

final class ClientFactory
{
    /**
     * @var FilesystemCachePool
     */
    private $filesystemCachePool;
    /**
     * @var ParameterProvider
     */
    private $parameterProvider;

    public function __construct(FilesystemCachePool $filesystemCachePool, ParameterProvider $parameterProvider)
    {
        $this->filesystemCachePool = $filesystemCachePool;
        $this->parameterProvider = $parameterProvider;
    }

    public function create(): Client
    {
        $client = new Client();
        $client->addCache($this->filesystemCachePool);

        $client->authenticate(
            $this->parameterProvider->provide()['github_token'],
            null,
            Client::AUTH_URL_TOKEN
        );

        return $client;
    }
}
