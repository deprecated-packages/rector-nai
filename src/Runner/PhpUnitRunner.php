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
        $phpunitConfig = $this->resolvePhpUnitConfig($repositoryDirectory);

        $commandLine = sprintf(
            '%s/vendor/phpunit/phpunit/phpunit %s', # safest path
            $repositoryDirectory,
            $repositoryDirectory,
            $phpunitConfig ? ' -c ' . $phpunitConfig : ''
        );

        $process = new Process($commandLine);
        $this->symfonyStyle->writeln('Running: ' . $commandLine);

        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function resolvePhpUnitConfig(string $repositoryDirectory): ?string
    {
        if (file_exists($repositoryDirectory . '/phpunit.xml')) {
            return $repositoryDirectory . '/phpunit.xml';
        }

        if (file_exists($repositoryDirectory . '/phpunit.xml.dist')) {
            return $repositoryDirectory . '/phpunit.xml.dist';
        }

        return null;
    }
}
