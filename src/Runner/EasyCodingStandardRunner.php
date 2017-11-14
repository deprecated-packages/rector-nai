<?php declare(strict_types=1);

namespace Rector\NAI\Runner;

use Rector\NAI\Contract\Runner\RunnerInterface;
use Rector\NAI\FileSystem\SourceResolver;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class EasyCodingStandardRunner implements RunnerInterface
{
    /**
     * @var string|null
     */
    private $ecsLevel;

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    /**
     * @var SourceResolver
     */
    private $sourceResolver;

    public function __construct(?string $ecsLevel, SymfonyStyle $symfonyStyle, SourceResolver $sourceResolver)
    {
        $this->ecsLevel = $ecsLevel;
        $this->symfonyStyle = $symfonyStyle;
        $this->sourceResolver = $sourceResolver;
    }

    public function isActive(): bool
    {
        return $this->ecsLevel !== null;
    }

    public function run(string $repositoryDirectory): void
    {
        $commandLine = sprintf(
            'vendor/bin/ecs check %s --config vendor/symplify/easy-coding-standard/config/%s.neon --fix',
            implode(' ', $this->sourceResolver->resolveFromDirectory($repositoryDirectory)),
            $this->ecsLevel
        );

        $process = new Process($commandLine);
        $this->symfonyStyle->writeln('Running: ' . $commandLine);

        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
