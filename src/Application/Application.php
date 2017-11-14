<?php declare(strict_types=1);

namespace Rector\NAI\Application;

use Github\Api\Repo;
use Github\Client;
use GitWrapper\GitWrapper;
use Rector\NAI\Composer\ComposerUpdater;
use Rector\NAI\Git\GitRepository;
use Rector\NAI\Github\GithubApi;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symplify\PackageBuilder\Adapter\Symfony\Parameter\ParameterProvider;

final class Application
{
    /**
     * @var ParameterProvider
     */
    private $parameterProvider;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $workroomDirectory;

    /**
     * @var GitWrapper
     */
    private $gitWrapper;

    /**
     * @var ComposerUpdater
     */
    private $composerUpdater;

    /**
     * @var GitRepository
     */
    private $gitRepository;

    public function __construct(
        string $workroomDirectory,
        ParameterProvider $parameterProvider,
        Client $client,
        GitWrapper $gitWrapper,
        ComposerUpdater $composerUpdater,
        GitRepository $gitRepository
    ) {
        $this->parameterProvider = $parameterProvider;
        $this->client = $client;
        $this->workroomDirectory = $workroomDirectory;
        $this->gitWrapper = $gitWrapper;
        $this->composerUpdater = $composerUpdater;
        $this->gitRepository = $gitRepository;
    }

    public function run(): void
    {
        // consider moving to factory
        $this->gitWrapper->setPrivateKey($this->parameterProvider->provide()['git_key_path']);

        $packageName = $this->parameterProvider->provide()['repository'];
        [$vendorName, $subName] = explode('/', $packageName);

        // fork it
        // consider using directly Repo service - nicer api, right away :)
        /** @var Repo $repositoryApi */
        $repositoryApi = $this->client->api(GithubApi::REPOSITORY);
        $repository = $repositoryApi->forks()
            ->create($vendorName, $subName);

        $repositoryDirectory = $this->workroomDirectory . '/' . $repository['name'];

        // get working directory for git
        $gitWorkingCopy = $this->gitRepository->getGitWorkingCopy($repositoryDirectory, $repository);

        // prepare new branch
        $this->gitRepository->prepareRectorBranch($gitWorkingCopy);

        // update dependencies
        $this->composerUpdater->installInDirectory($repositoryDirectory);

//        $this->runEasyCodingStandard($repositoryDirectory);

        // run rector?

        // run tests
//        $this->runTests($repositoryDirectory);

        // push!
        // always push to current branch - same as on local
//        $this->gitWrapper->git('config --global push.default upstream');

        if ($gitWorkingCopy->hasChanges()) {
            $gitWorkingCopy->add('*');
            $gitWorkingCopy->commit($this->parameterProvider->provide()['commit_message'] ?? 'code rectored');
            $gitWorkingCopy->push('origin', GitRepository::RECTOR_BRANCH_NAME);
        }

        // send PR
    }

    private function runEasyCodingStandard(string $repositoryDirectory): void
    {
        $source = [];

        $level = $this->parameterProvider->provide()['ecs_level'] ?? null;
        if ($level === null) {
            return;
        }

        if (file_exists($repositoryDirectory . '/src')) {
            $source[] = $repositoryDirectory . '/src';
        }
        if (file_exists($repositoryDirectory . '/tests')) {
            $source[] = $repositoryDirectory  . '/tests';
        }

        $process = new Process(sprintf(
            'vendor/bin/ecs check %s --config vendor/symplify/easy-coding-standard/config/%s.neon --fix',
            implode(' ', $source),
            $level
        ));

        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function runTests(string $repositoryDirectory): void
    {
        $process = new Process(sprintf(
            '%s/vendor/phpunit/phpunit/phpunit %s', # safest path
            $repositoryDirectory,
            $repositoryDirectory . '/tests' // improve
        ));

        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
