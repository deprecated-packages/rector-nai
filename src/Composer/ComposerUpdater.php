<?php declare(strict_types=1);

namespace Rector\NAI\Composer;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class ComposerUpdater
{
    public function installInDirectory(string $directory): void
    {
        if (file_exists($directory . '/vendor')) {
            // already installed
            return;
        }

        $process = new Process(sprintf(
            'composer install --working-dir=%s',
            $directory
        ));

        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
