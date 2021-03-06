<?php

namespace ContinuousPipe\River;

use ContinuousPipe\Events\Aggregate;
use ContinuousPipe\River\Event\TideCreated;
use ContinuousPipe\River\EventBased\ApplyEventCapability;
use ContinuousPipe\River\EventBased\RaiseEventCapability;
use ContinuousPipe\River\Flex\FlexConfiguration;
use ContinuousPipe\River\Flow\Event\BranchPinned;
use ContinuousPipe\River\Flow\Event\BranchUnpinned;
use ContinuousPipe\River\Flow\Event\FlowConfigurationUpdated;
use ContinuousPipe\River\Flow\Event\FlowCreated;
use ContinuousPipe\River\Flow\Event\FlowFlexed;
use ContinuousPipe\River\Flow\Event\FlowRecovered;
use ContinuousPipe\River\Flow\Event\FlowUnflexed;
use ContinuousPipe\River\Flow\Event\PipelineCreated;
use ContinuousPipe\River\Flow\Event\PipelineDeleted;
use ContinuousPipe\River\Flow\Projections\FlatPipeline;
use ContinuousPipe\River\Pipeline\PipelineNotFound;
use ContinuousPipe\Security\Team\Team;
use ContinuousPipe\Security\User\User;
use Ramsey\Uuid\UuidInterface;

final class Flow implements Aggregate
{
    use RaiseEventCapability,
        ApplyEventCapability;

    /**
     * @var UuidInterface
     */
    private $uuid;

    /**
     * @var Team
     */
    private $team;

    /**
     * @var User
     */
    private $user;

    /**
     * @var CodeRepository
     */
    private $codeRepository;

    /**
     * @var array
     */
    private $configuration = [];

    /**
     * @var FlatPipeline[]
     */
    private $pipelines = [];
    private $pinnedBranches = [];

    /**
     * @var FlexConfiguration
     */
    private $flexConfiguration;

    private function __construct()
    {
    }

    /**
     * @deprecated Should directly created the aggregate from the events
     *
     * @param FlowContext $context
     *
     * @return static
     */
    public static function fromContext(FlowContext $context)
    {
        return self::fromEvents([
            new Flow\Event\FlowCreated(
                $context->getFlowUuid(),
                $context->getTeam(),
                $context->getUser(),
                $context->getCodeRepository()
            ),
            new Flow\Event\FlowConfigurationUpdated(
                $context->getFlowUuid(),
                $context->getConfiguration()
            ),
        ]);
    }

    /**
     * @param UuidInterface  $uuid
     * @param Team           $team
     * @param User           $user
     * @param CodeRepository $codeRepository
     *
     * @return Flow
     */
    public static function create(UuidInterface $uuid, Team $team, User $user, CodeRepository $codeRepository)
    {
        $event = new FlowCreated($uuid, $team, $user, $codeRepository);

        $flow = self::fromEvents([$event]);
        $flow->raise($event);

        return $flow;
    }

    /**
     * @param array $configuration
     */
    public function update(array $configuration)
    {
        $this->raise(new FlowConfigurationUpdated(
            $this->uuid,
            $configuration
        ));
    }

    public function pinBranch(string $branch)
    {
        $this->raise(new BranchPinned(
            $this->uuid,
            $branch
        ));
    }

    public function unpinBranch(string $branch)
    {
        $this->raise(new BranchUnpinned(
            $this->uuid,
            $branch
        ));
    }

    public function activateFlex()
    {
        $this->raise(new FlowFlexed(
            $this->uuid,
            new FlexConfiguration(
                random_str(6)
            )
        ));
    }

    public function deactivateFlex()
    {
        $this->raise(new FlowUnflexed($this->uuid));
    }

    /**
     * @param TideCreated $event
     */
    public function tideWasCreated(TideCreated $event)
    {
        if ($event->getFlatPipeline() === null) {
            return;
        }

        foreach ($this->pipelines as $pipeline) {
            if ($event->getFlatPipeline()->getUuid()->equals($pipeline->getUuid())) {
                return;
            }
        }

        $this->raise(
            new PipelineCreated(
                $this->uuid,
                $event->getFlatPipeline()
            )
        );
    }

    /**
     * @param PipelineCreated $event
     */
    public function applyPipelineCreated(PipelineCreated $event)
    {
        $this->pipelines[] = $event->getFlatPipeline();
    }

    /**
     * @param FlowCreated $event
     */
    public function applyFlowCreated(FlowCreated $event)
    {
        $this->uuid = $event->getFlowUuid();
        $this->team = $event->getTeam();
        $this->user = $event->getUser();
        $this->codeRepository = $event->getCodeRepository();
    }

    /**
     * @param FlowConfigurationUpdated $event
     */
    public function applyFlowConfigurationUpdated(FlowConfigurationUpdated $event)
    {
        $this->configuration = $event->getConfiguration();
    }

    public function applyFlowRecovered(FlowRecovered $event)
    {
    }

    public function applyBranchPinned(BranchPinned $event)
    {
        $this->pinnedBranches[] = $event->getBranch();
        $this->pinnedBranches = array_unique($this->pinnedBranches);
    }

    public function applyBranchUnpinned(BranchUnpinned $event)
    {
        $this->pinnedBranches = array_diff($this->pinnedBranches, [$event->getBranch()]);
    }

    public function applyFlowFlexed(FlowFlexed $event)
    {
        $this->flexConfiguration = $event->getFlexConfiguration();
    }

    public function applyFlowUnflexed()
    {
        $this->flexConfiguration = null;
    }

    public function getUuid() : UuidInterface
    {
        return $this->uuid;
    }

    public function getTeam() : Team
    {
        return $this->team;
    }

    public function getConfiguration() : array
    {
        return $this->configuration;
    }

    public function getCodeRepository() : CodeRepository
    {
        return $this->codeRepository;
    }

    public function getUser() : User
    {
        return $this->user;
    }

    /**
     * @return FlatPipeline[]
     */
    public function getPipelines(): array
    {
        return $this->pipelines;
    }

    public function deletePipelineByUuid(UuidInterface $uuid)
    {
        foreach ($this->pipelines as $pipeline) {
            if ($uuid->equals($pipeline->getUuid())) {
                $this->raise(new PipelineDeleted(
                    $this->getUuid(),
                    $uuid
                ));

                return;
            }
        }

        throw new PipelineNotFound(
            sprintf('Pipeline with UUID "%s" does not exist in flow "%s".', $uuid->toString(), $this->getUuid())
        );
    }

    public function applyPipelineDeleted(PipelineDeleted $event)
    {
        foreach ($this->pipelines as $key => $pipeline) {
            if ($event->getPipelineUuid() == $pipeline->getUuid()) {
                unset($this->pipelines[$key]);
            }
        }
    }

    public function getPinnedBranches(): array
    {
        return $this->pinnedBranches;
    }

    /**
     * @return FlexConfiguration|null
     */
    public function getFlexConfiguration()
    {
        return $this->flexConfiguration;
    }
}

function random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyz')
{
    $str = '';
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $str .= $keyspace[random_int(0, $max)];
    }
    return $str;
}
