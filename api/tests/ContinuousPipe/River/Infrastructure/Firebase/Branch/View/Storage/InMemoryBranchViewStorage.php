<?php

namespace ContinuousPipe\River\Infrastructure\Firebase\Branch\View\Storage;

use ContinuousPipe\River\CodeRepository\Branch;
use ContinuousPipe\River\CodeRepository\BranchQuery;
use ContinuousPipe\River\View\Storage\BranchViewStorage;
use ContinuousPipe\River\View\Tide;
use Ramsey\Uuid\UuidInterface;

/**
 * This in-memory implementation is for testing purposes only.
 */
class InMemoryBranchViewStorage implements BranchViewStorage
{
    private $savedBranches = [];

    /**
     * @var BranchQuery
     */
    private $branchQuery;

    public function __construct(BranchQuery $branchQuery)
    {
        $this->branchQuery = $branchQuery;
    }

    public function save(UuidInterface $flowUuid)
    {
        $branches = $this->branchQuery->findBranches($flowUuid);

        $this->savedBranches[(string) $flowUuid] = array_combine(
            array_map(
                function (Branch $branch) {
                    return (string) $branch;
                },
                $branches
            ),
            $branches
        );
    }

    public function updateTide(Tide $tide)
    {
        $flowUuid = $tide->getFlowUuid();
        $branchName = $tide->getCodeReference()->getBranch();
        if (!isset($this->savedBranches[(string) $flowUuid])) {
            $this->save($flowUuid);
        }

        if (!isset($this->savedBranches[(string) $flowUuid][$branchName])) {
            $this->savedBranches[(string) $flowUuid][$branchName] = new Branch($branchName, [$tide]);

            return;
        }

        $this->savedBranches[(string) $flowUuid][$branchName] = $this->savedBranches[(string) $flowUuid][$branchName]->withTide(
            $tide
        );
    }

    public function branchPinned(UuidInterface $flowUuid, string $branch)
    {
        $this->updateBranchPinning($flowUuid, $branch,
            function(Branch $branch) {
                return $branch->pinned();
            }
        );
    }

    public function branchUnpinned(UuidInterface $flowUuid, string $branch)
    {
        $this->updateBranchPinning($flowUuid, $branch,
            function(Branch $branch) {
                return $branch->unpinned();
            }
        );
    }

    public function wasBranchSaved(UuidInterface $flowUuid, Branch $branch)
    {
        if (!isset($this->savedBranches[(string) $flowUuid])) {
            return false;
        }

        if (!isset($this->savedBranches[(string) $flowUuid][(string) $branch])) {
            return false;
        }

        if ($branch->isPinned() !== $this->savedBranches[(string) $flowUuid][(string) $branch]->isPinned()) {
            return false;
        }

        return array_intersect(
            $branch->getTideUuids(),
            $this->savedBranches[(string) $flowUuid][(string) $branch]->getTideUuids()
        ) == $branch->getTideUuids();
    }

    /**
     * @param UuidInterface $flowUuid
     * @param string $branch
     * @param $updateBranch
     */
    private function updateBranchPinning(UuidInterface $flowUuid, string $branch, $updateBranch)
    {
        if (!isset($this->savedBranches[(string) $flowUuid]) || !isset($this->savedBranches[(string) $flowUuid][$branch])) {
            $this->save($flowUuid);
        }

        if (!isset($this->savedBranches[(string) $flowUuid][$branch])) {
            return;
        }

        $this->savedBranches[(string) $flowUuid][$branch] = $updateBranch(
            $this->savedBranches[(string) $flowUuid][$branch]
        );
    }
}