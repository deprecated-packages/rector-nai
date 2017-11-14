<?php declare(strict_types=1);

namespace Rector\NAI\FileSystem;

final class SourceResolver
{
    /**
     * @var string[]
     */
    private $possiblePaths = ['/src', '/lib', '/test', '/tests'];

    /**
     * @return string[]
     */
    public function resolveFromDirectory(string $repositoryDirectory): array
    {
        $source = [];

        foreach ($this->possiblePaths as $possiblePath) {
            if (file_exists($repositoryDirectory . $possiblePath)) {
                $source[] = $repositoryDirectory . $possiblePath;
            }
        }

        return $source;
    }

    public function resolveTestDirectory(string $repositoryDirectory): ?string
    {
        if (file_exists($repositoryDirectory . '/tests')) {
            return $repositoryDirectory . '/tests';
        }

        if (file_exists($repositoryDirectory . '/test')) {
            return $repositoryDirectory . '/test';
        }

        // doesn't have tests
        return null;
    }
}
