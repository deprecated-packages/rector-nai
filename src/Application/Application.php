<?php declare(strict_types=1);

namespace Rector\NAI\Application;

use Github\Api\PullRequest;
use Github\Api\Repo;
use GitWrapper\GitWorkingCopy;
use Nette\Utils\Strings;
use Rector\NAI\Composer\ComposerUpdater;
use Rector\NAI\Contract\Runner\RunnerInterface;
use Rector\NAI\Git\GitRepository;
use Symfony\Component\Console\Style\SymfonyStyle;
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

    /**
     * @var string
     */
    private $branchName;

    /**
     * @var RunnerInterface[]
     */
    private $runners = [];

    public function __construct(
        string $workroomDirectory,
        string $branchName,
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
        $this->branchName = $branchName;
    }

    public function addRunner(RunnerInterface $runner): void
    {
        $this->runners[] = $runner;
    }

    public function run(): void
    {
        $this->symfonyStyle->title('Narrow Artificial Intelligence in DA Place!');

        [$organizationName, $subName] = $this->getOrganizationAndPackageName();

        $repository = $this->repositoryApi->forks()
            ->create($organizationName, $subName);

        $this->symfonyStyle->success('Fork created');

        $repositoryDirectory = $this->workroomDirectory . '/' . $repository['name'];

        // get working directory for git
        $gitWorkingCopy = $this->gitRepository->getGitWorkingCopy($repositoryDirectory, $repository);
        $this->symfonyStyle->success('Fork synced');

        // update dependencies
        $this->composerUpdater->installInDirectory($repositoryDirectory);
        $this->symfonyStyle->success('Composer dependencies installed');

        // prepare new branch
        $this->gitRepository->prepareRectorBranch($gitWorkingCopy);
        $this->symfonyStyle->success(sprintf('Switched to %s branch',  $this->branchName));

        // use runners here!!
        $this->runRunners($repositoryDirectory);

        if (! $gitWorkingCopy->hasChanges()) {
            return;
        }
        $this->pushAndSendPullRequest($gitWorkingCopy, $organizationName, $subName);

        $this->symfonyStyle->success('Work is done!');
    }

    private function getOrganizationAndPackageName(): array
    {
        $repository = $this->parameterProvider->provideParameter('repository');

        // remove https://github.com prefix
        if (Strings::startsWith($repository, 'https://github.com/')) {
            $repository = substr($repository, strlen('https://github.com/') );
        }

        return explode('/', $repository);
    }

    private function runRunners(string $repositoryDirectory): void
    {
        foreach ($this->runners as $runner) {
            if (! $runner->isActive()) {
                continue;
            }

            $runner->run($repositoryDirectory);
        }
    }

    private function pushAndSendPullRequest(GitWorkingCopy $gitWorkingCopy, string $organizationName, string $subName): void
    {
        $message = $this->parameterProvider->provideParameter('commit_message');
        $gitWorkingCopy->add('*');
        $gitWorkingCopy->commit($message);
        $gitWorkingCopy->push('origin', $this->branchName);

        $this->pullRequestApi->create($organizationName, $subName, [
            'base' => 'master',
            'head' => $this->parameterProvider->provideParameter('github_name') . ':' . $this->branchName,
            'title' => ucfirst($message),
            'body' => ''
        ]);
    }
}
