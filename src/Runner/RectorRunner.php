<?php declare(strict_types=1);

namespace Rector\NAI\Runner;

use Rector\NAI\Contract\Runner\RunnerInterface;
use Rector\NAI\FileSystem\SourceResolver;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class RectorRunner implements RunnerInterface
{
    /**
     * @var string
     */
    private $rectorLevel;

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    /**
     * @var SourceResolver
     */
    private $sourceResolver;

    public function __construct(string $rectorLevel, SymfonyStyle $symfonyStyle, SourceResolver $sourceResolver)
    {
        $this->rectorLevel = $rectorLevel;
        $this->symfonyStyle = $symfonyStyle;
        $this->sourceResolver = $sourceResolver;
    }

    public function run(string $repositoryDirectory): void
    {
        if ($this->rectorLevel === null) {
            return;
        }

        $commandLine = sprintf(
            'vendor/bin/rector process %s --level %s',
            implode(' ', $this->sourceResolver->resolveFromDirectory($repositoryDirectory)),
            $this->rectorLevel
        );

        $process = new Process($commandLine);
        $this->symfonyStyle->writeln('Running: ' . $commandLine);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
