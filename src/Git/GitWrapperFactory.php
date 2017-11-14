<?php declare(strict_types=1);

namespace Rector\NAI\Git;

use GitWrapper\GitWrapper;
use Symplify\PackageBuilder\Parameter\ParameterProvider;

final class GitWrapperFactory
{
    /**
     * @var ParameterProvider
     */
    private $parameterProvider;

    public function __construct(ParameterProvider $parameterProvider)
    {
        $this->parameterProvider = $parameterProvider;
    }

    public function create(): GitWrapper
    {
        $gitWrapper = new GitWrapper();
        $gitWrapper->setPrivateKey($this->parameterProvider->provideParameter('git_key_path'));

        return $gitWrapper;
    }
}
