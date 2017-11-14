<?php declare(strict_types=1);

namespace Rector\NAI\Application;

use Github\Api\PullRequest;
use Github\Api\Repo;
use Rector\NAI\Composer\ComposerUpdater;
use Rector\NAI\Git\GitRepository;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symplify\PackageBuilder\Parameter\ParameterProvider;

final class Application
{
    /**
     * @var ParameterProvider
     */
    private $parameterProvider;

    /**
     * @var string
     */
    private $workroomDirectory;

    /**
     * @var ComposerUpdater
     */
    private $composerUpdater;

    /**
     * @var GitRepository
     */
    private $gitRepository;

    /**
     * @var Repo
     */
    private $repositoryApi;

    /**
     * @var PullRequest
     */
    private $pullRequestApi;

    public function __construct(
        string $workroomDirectory,
        ParameterProvider $parameterProvider,
        ComposerUpdater $composerUpdater,
        GitRepository $gitRepository,
        SymfonyStyle $symfonyStyle,
        Repo $repositoryApi,
        PullRequest $pullRequestApi
    ) {
        $this->parameterProvider = $parameterProvider;
        $this->workroomDirectory = $workroomDirectory;
        $this->composerUpdater = $composerUpdater;
        $this->gitRepository = $gitRepository;
        $this->symfonyStyle = $symfonyStyle;
        $this->repositoryApi = $repositoryApi;
        $this->pullRequestApi = $pullRequestApi;
    }

    public function run(): void
    {
        $this->symfonyStyle->title('Narrow Artificial Intelligence in DA Place!');

        $packageName = $this->parameterProvider->provideParameter('repository');
        [$vendorName, $subName] = explode('/', $packageName);

        $repository = $this->repositoryApi->forks()
            ->create($vendorName, $subName);

        $this->symfonyStyle->success('Fork created');

        $repositoryDirectory = $this->workroomDirectory . '/' . $repository['name'];

        // get working directory for git
        $gitWorkingCopy = $this->gitRepository->getGitWorkingCopy($repositoryDirectory, $repository);

        $gitWorkingCopy->config('user.name', $this->parameterProvider->provideParameter('git_name'));
        $gitWorkingCopy->config('user.email', $this->parameterProvider->provideParameter('git_email'));

        // refresh repo, to have most up-to-date version
        if (! $gitWorkingCopy->hasRemote('upstream')) {
            $gitWorkingCopy->addRemote('upstream', $repository['source']['clone_url']);
        }
        $gitWorkingCopy->fetch('upstream');
        $gitWorkingCopy->merge('upstream/master');

        $this->symfonyStyle->success('Fork synced');

        // update dependencies
        $this->composerUpdater->installInDirectory($repositoryDirectory);
        $this->symfonyStyle->success('Composer dependencies installed');

        // prepare new branch
        $this->gitRepository->prepareRectorBranch($gitWorkingCopy);
        $this->symfonyStyle->success(sprintf('Switched to %s branch', GitRepository::RECTOR_BRANCH_NAME));

        // run ecs
//        $this->runEasyCodingStandard($repositoryDirectory);

        // run rector
        $this->runRector($repositoryDirectory);

        // run tests
        $this->runTests($repositoryDirectory);

        die;

        // push!
        $message = $this->parameterProvider->provideParameter('commit_message');

        if ($gitWorkingCopy->hasChanges()) {
            $gitWorkingCopy->add('*');
            $gitWorkingCopy->commit($message);
            $gitWorkingCopy->push('origin', GitRepository::RECTOR_BRANCH_NAME);
        }

        // send PR

        $this->pullRequestApi->create($vendorName, $subName, [
            'base' => 'master',
            'head' => $this->parameterProvider->provideParameter('github_name') . ':' . GitRepository::RECTOR_BRANCH_NAME,
            'title' => ucfirst($message),
            'body' => ''
        ]);

        $this->symfonyStyle->success('Work is done!');
    }

    private function runEasyCodingStandard(string $repositoryDirectory): void
    {
        $level = $this->parameterProvider->provideParameter('ecs_level');
        if ($level === null) {
            return;
        }

        $commandLine = sprintf(
            'vendor/bin/ecs check %s --config vendor/symplify/easy-coding-standard/config/%s.neon --fix',
            implode(' ', $this->resolveSource($repositoryDirectory)),
            $level
        );

        $process = new Process($commandLine);
        $this->symfonyStyle->writeln('Running: ' . $commandLine);

        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function runTests(string $repositoryDirectory): void
    {
        $commandLine = sprintf(
            '%s/vendor/phpunit/phpunit/phpunit %s', # safest path
            $repositoryDirectory,
            $repositoryDirectory . '/tests' // improve
        );

        $process = new Process($commandLine);
        $this->symfonyStyle->writeln('Running: ' . $commandLine);

        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function runRector(string $repositoryDirectory): void
    {
        $level = $this->parameterProvider->provideParameter('rector_level');
        if ($level === null) {
            return;
        }

        $commandLine = sprintf(
            'vendor/bin/rector process %s --level %s',
            implode(' ', $this->resolveSource($repositoryDirectory)),
            $level
        );

        $process = new Process($commandLine);
        $this->symfonyStyle->writeln('Running: ' . $commandLine);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    /**
     * @return string[]
     */
    private function resolveSource(string $repositoryDirectory): array
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
