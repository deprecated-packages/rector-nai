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

    /**
     * @var Process|null
     */
    private $failedProcess;

    public function __construct(?string $ecsLevel, SymfonyStyle $symfonyStyle, SourceResolver $sourceResolver)
    {
        $this->ecsLevel = $ecsLevel;
        $this->symfonyStyle = $symfonyStyle;
        $this->sourceResolver = $sourceResolver;
    }

    public function isActive(string $repositoryDirectory): bool
    {
        return $this->ecsLevel !== null;
    }

    public function run(string $repositoryDirectory): void
    {
        $levels = explode('|', $this->ecsLevel);
        foreach ($levels as $level) {
            $this->runForSingleLevel($repositoryDirectory, $level);
        }
    }

    private function runForSingleLevel(string $repositoryDirectory, string $level): void
    {
        $commandLine = sprintf(
            'vendor/bin/ecs check %s --config vendor/symplify/easy-coding-standard/config/%s.neon --fix --clear-cache',
            implode(' ', $this->sourceResolver->resolveFromDirectory($repositoryDirectory)),
            $level
        );

        $process = new Process($commandLine);
        $this->symfonyStyle->writeln('Running: ' . $commandLine);

        $process->run();

        if (! $process->isSuccessful()) {
            $this->failedProcess = $process;
        }
    }
}
