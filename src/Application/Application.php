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

    /**
     * @var bool
     */
    private $isReadOnly;

    public function __construct(
        bool $readOnly,
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
        $this->isReadOnly = $readOnly;
    }

    public function addRunner(RunnerInterface $runner): void
    {
        $this->runners[] = $runner;
    }

    public function run(): void
    {
        $this->symfonyStyle->title('Narrow Artificial Intelligence in DA Place!');

        [$organizationName, $repositoryName] = $this->getOrganizationAndPackageName();

        $repository = $this->repositoryApi->forks()
            ->create($organizationName, $repositoryName);

        $this->symfonyStyle->note(sprintf('Fork of "%s/%s" created', $organizationName, $repositoryName));

        $repositoryDirectory = $this->workroomDirectory . '/' . $repository['name'];

        // get working directory for git
        $gitWorkingCopy = $this->gitRepository->getGitWorkingCopy($repositoryDirectory, $repository);
        $this->symfonyStyle->note('Fork synced');

        // update dependencies
        $this->composerUpdater->installInDirectory($repositoryDirectory);
        $this->symfonyStyle->note('Composer dependencies installed');

        // prepare new branch
        $this->gitRepository->prepareRectorBranch($gitWorkingCopy);
        $this->symfonyStyle->note(sprintf('Switched to "%s" branch',  $this->branchName));

        // use runners here!!
        $this->runRunners($repositoryDirectory);

        if (! $gitWorkingCopy->hasChanges()) {
            $this->symfonyStyle->warning('No changes were made to the code.');
            return;
        }

        if ($this->isReadOnly) {
            $this->symfonyStyle->warning('This is run is read-only. No commit, push and PR.');
            return;
        }

        $this->pushAndSendPullRequest($gitWorkingCopy, $organizationName, $repositoryName);
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
            if (! $runner->isActive($repositoryDirectory)) {
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
