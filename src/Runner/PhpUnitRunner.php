<?php declare(strict_types=1);

namespace Rector\NAI\Runner;

use Rector\NAI\Contract\Runner\RunnerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class PhpUnitRunner implements RunnerInterface
{
    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    public function __construct(SymfonyStyle $symfonyStyle)
    {
        $this->symfonyStyle = $symfonyStyle;
    }

    public function isActive(string $repositoryDirectory): bool
    {
        $possiblePhpUnitBin = sprintf('%s/vendor/phpunit/phpunit/phpunit', $repositoryDirectory);

        return file_exists($possiblePhpUnitBin);
    }

    public function run(string $repositoryDirectory): void
    {
        $possiblePhpUnitBin = sprintf('%s/vendor/phpunit/phpunit/phpunit', $repositoryDirectory);
        if (! file_exists($possiblePhpUnitBin)) {
            return;
        }

        $commandLine = sprintf(
            '%s/vendor/phpunit/phpunit/phpunit %s', # safest path
            $repositoryDirectory,
            $repositoryDirectory
        );

        $process = new Process($commandLine);
        $this->symfonyStyle->writeln('Running: ' . $commandLine);

        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
