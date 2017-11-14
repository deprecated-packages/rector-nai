<?php declare(strict_types=1);

namespace Rector\NAI\Runner;

use Rector\NAI\Contract\Runner\RunnerInterface;
use Rector\NAI\FileSystem\SourceResolver;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class PhpUnitRunner implements RunnerInterface
{
    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;
    /**
     * @var SourceResolver
     */
    private $sourceResolver;

    public function __construct(SymfonyStyle $symfonyStyle, SourceResolver $sourceResolver)
    {
        $this->symfonyStyle = $symfonyStyle;
        $this->sourceResolver = $sourceResolver;
    }

    /**
     *  @todo improve
     * maybe use $repositoryDirectory here and verify presence of tests and phpunit
     */
    public function isActive(): bool
    {
        return true;
    }

    public function run(string $repositoryDirectory): void
    {
        $testDirectory = $this->sourceResolver->resolveTestDirectory($repositoryDirectory);
        if ($testDirectory === null) {
            return;
        }

        $commandLine = sprintf(
            '%s/vendor/phpunit/phpunit/phpunit %s', # safest path
            $repositoryDirectory,
            $testDirectory
        );

        $process = new Process($commandLine);
        $this->symfonyStyle->writeln('Running: ' . $commandLine);

        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
