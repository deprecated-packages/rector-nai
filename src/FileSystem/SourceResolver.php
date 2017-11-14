<?php declare(strict_types=1);

namespace Rector\NAI\FileSystem;

final class SourceResolver
{
    /**
     * @return string[]
     */
    public function resolveFromDirectory(string $repositoryDirectory): array
    {
        $source = [];

        if (file_exists($repositoryDirectory . '/src')) {
            $source[] = $repositoryDirectory . '/src';
        }
        if (file_exists($repositoryDirectory . '/tests')) {
            $source[] = $repositoryDirectory . '/tests';
        }

        return $source;
    }
}