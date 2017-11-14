<?php declare(strict_types=1);

namespace Rector\NAI\Git;

use GitWrapper\GitWorkingCopy;
use GitWrapper\GitWrapper;
use Nette\Utils\Strings;

final class GitRepository
{
    /**
     * @var GitWrapper
     */
    private $gitWrapper;
    /**
     * @var string
     */
    private $gitName;
    /**
     * @var string
     */
    private $gitEmail;
    /**
     * @var string
     */
    private $branchName;

    public function __construct(string $gitName, string $gitEmail, string $branchName, GitWrapper $gitWrapper)
    {
        $this->gitName = $gitName;
        $this->gitEmail = $gitEmail;
        $this->branchName = $branchName;
        $this->gitWrapper = $gitWrapper;
    }

    /**
     * @param mixed[] $repository
     */
    public function getGitWorkingCopy(string $repositoryDirectory, array $repository): GitWorkingCopy
    {
        if (file_exists($repositoryDirectory)) {
            return $this->gitWrapper->workingCopy($repositoryDirectory);
        }

        $gitWorkingCopy = $this->gitWrapper->cloneRepository($repository['ssh_url'], $repositoryDirectory, [
            'depth' => 1
        ]);

        $gitWorkingCopy->config('user.name', $this->gitName);
        $gitWorkingCopy->config('user.email', $this->gitEmail);

        $this->refreshFork($gitWorkingCopy, $repository);

        return $gitWorkingCopy;
    }

    public function prepareRectorBranch(GitWorkingCopy $gitWorkingCopy): void
    {
        $branchOutput = $gitWorkingCopy->branch()
            ->getOutput();

        if (Strings::contains($branchOutput, '* ' . $this->branchName)) {
            // already on branch
            return;
        }

        if (Strings::contains($branchOutput, $this->branchName)) {
            // branch is there, go there
            $gitWorkingCopy->checkout($this->branchName);
            return;
        }

        // branch is not here, create it
        $gitWorkingCopy->checkoutNewBranch($this->branchName);
    }

    /**
     * @param mixed[] $repository
     */
    private function refreshFork(GitWorkingCopy $gitWorkingCopy, array $repository): void
    {
        if (! $gitWorkingCopy->hasRemote('upstream')) {
            $gitWorkingCopy->addRemote('upstream', $repository['source']['clone_url']);
        }

        $gitWorkingCopy->fetch('upstream');
        $gitWorkingCopy->merge('upstream/master');
    }
}
