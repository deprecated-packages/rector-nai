<?php declare(strict_types=1);

namespace Rector\NAI\Git;

use GitWrapper\GitWorkingCopy;
use GitWrapper\GitWrapper;
use Nette\Utils\Strings;

final class GitRepository
{
    /**
     * @var string
     */
    public const RECTOR_BRANCH_NAME = 'rector-nai';

    /**
     * @var GitWrapper
     */
    private $gitWrapper;

    public function __construct(GitWrapper $gitWrapper)
    {
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

        return $this->gitWrapper->cloneRepository($repository['ssh_url'], $repositoryDirectory, [
            'depth' => 1
        ]);
    }

    public function prepareRectorBranch(GitWorkingCopy $gitWorkingCopy): void
    {
        $branchOutput = $gitWorkingCopy->branch()
            ->getOutput();

        if (Strings::contains($branchOutput, '* ' . self::RECTOR_BRANCH_NAME)) {
            // already on branch
            return;
        }

        if (Strings::contains($branchOutput, self::RECTOR_BRANCH_NAME)) {
            // branch is there, go there
            $gitWorkingCopy->checkout(self::RECTOR_BRANCH_NAME);
            return;
        }

        // branch is not here, create it
        $gitWorkingCopy->checkoutNewBranch(self::RECTOR_BRANCH_NAME);
    }
}
