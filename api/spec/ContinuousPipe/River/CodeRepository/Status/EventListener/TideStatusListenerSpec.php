<?php

namespace spec\ContinuousPipe\River\CodeRepository\Status\EventListener;

use ContinuousPipe\River\Event\TideCreated;
use ContinuousPipe\River\Event\TideFailed;
use ContinuousPipe\River\Event\TideSuccessful;
use ContinuousPipe\River\Tide;
use ContinuousPipe\River\Repository\TideRepository;
use ContinuousPipe\River\CodeRepository\CodeStatusUpdater;
use LogStream\LoggerFactory;
use PhpSpec\ObjectBehavior;
use Rhumsaa\Uuid\Uuid;

class TideStatusListenerSpec extends ObjectBehavior
{
    public function let(TideRepository $tideRepository, CodeStatusUpdater $codeStatusUpdater, LoggerFactory $loggerFactory)
    {
        $this->beConstructedWith($tideRepository, $codeStatusUpdater, $loggerFactory);
    }

    public function it_updates_the_code_repository_status_when_tide_is_created(TideRepository $tideRepository, Tide $tide, CodeStatusUpdater $codeStatusUpdater, TideCreated $tideCreatedEvent)
    {
        $tideUuid = Uuid::uuid1();

        $tideCreatedEvent->getTideUuid()->willReturn($tideUuid);
        $tideRepository->find($tideUuid)->willReturn($tide);
        $codeStatusUpdater->pending($tide)->shouldBeCalled();
        $this->notify($tideCreatedEvent->getWrappedObject());
    }

    public function it_updates_the_code_repository_status_when_tide_is_successful(TideRepository $tideRepository, Tide $tide, CodeStatusUpdater $codeStatusUpdater)
    {
        $tideUuid = Uuid::uuid1();

        $tideRepository->find($tideUuid)->willReturn($tide);
        $codeStatusUpdater->success($tide)->shouldBeCalled();
        $this->notify(new TideSuccessful($tideUuid));
    }

    public function it_updates_the_code_repository_status_when_tide_is_failed(TideRepository $tideRepository, Tide $tide, CodeStatusUpdater $codeStatusUpdater)
    {
        $tideUuid = Uuid::uuid1();

        $tideRepository->find($tideUuid)->willReturn($tide);
        $codeStatusUpdater->failure($tide)->shouldBeCalled();
        $this->notify(new TideFailed($tideUuid));
    }
}
