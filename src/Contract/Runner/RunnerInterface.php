<?php declare(strict_types=1);

namespace Rector\NAI\Contract\Runner;

interface RunnerInterface
{
    public function isActive(): bool;

    public function run(string $repositoryDirectory): void;
}
