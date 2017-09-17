<?php

namespace ContinuousPipe\Builder;

use ContinuousPipe\Builder\Request\BuildRequest;
use ContinuousPipe\Builder\Request\BuildRequestStep;
use ContinuousPipe\River\CodeReference;
use ContinuousPipe\River\Task\Build\BuildTaskConfiguration;
use ContinuousPipe\River\Task\Build\BuildTaskFactory;
use ContinuousPipe\River\Task\Build\Configuration\ServiceConfiguration;
use ContinuousPipe\Security\Credentials\BucketNotFound;
use ContinuousPipe\Security\Credentials\BucketRepository;
use ContinuousPipe\Security\Credentials\DockerRegistry;
use LogStream\Log;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;

class GuessUserRegistryAndUsername implements BuildRequestCreator
{
    /**
     * @var BuildRequestCreator
     */
    private $decoratedCreator;
    /**
     * @var BucketRepository
     */
    private $bucketRepository;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        BuildRequestCreator $decoratedCreator,
        BucketRepository $bucketRepository,
        LoggerInterface $logger
    ) {
        $this->decoratedCreator = $decoratedCreator;
        $this->bucketRepository = $bucketRepository;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function createBuildRequests(
        UuidInterface $flowUuid,
        UuidInterface $tideUuid,
        CodeReference $codeReference,
        BuildTaskConfiguration $configuration,
        UuidInterface $credentialsBucketUuid,
        Log $parentLog
    ): array {
        return $this->decoratedCreator->createBuildRequests(
            $flowUuid,
            $tideUuid,
            $codeReference,
            new BuildTaskConfiguration(array_map(function (ServiceConfiguration $serviceConfiguration) use ($tideUuid, $flowUuid, $credentialsBucketUuid) {
                return new ServiceConfiguration(array_map(function (BuildRequestStep $buildRequestStep) use ($tideUuid, $flowUuid, $credentialsBucketUuid) {
                    if ($buildRequestStep->getImage() !== null) {
                        $buildRequestStep = $buildRequestStep->withImage(
                            $this->guessImageNameIfNeeded($buildRequestStep, $credentialsBucketUuid, $tideUuid, $flowUuid)
                        );
                    }

                    return $buildRequestStep;
                }, $serviceConfiguration->getBuilderSteps()));
            }, $configuration->getServices())),
            $credentialsBucketUuid,
            $parentLog
        );
    }

    private function guessImageNameIfNeeded(BuildRequestStep $step, UuidInterface $bucketUuid, UuidInterface $tideUuid, UuidInterface $flowUuid) : Image
    {
        $image = $step->getImage();
        $parts = explode('/', $image->getName());

        // Everything sounds good
        if (count($parts) == 3) {
            return $step->getImage();
        }

        // Get registry of reference
        if (null === ($registry = $this->getReferenceRegistry($bucketUuid, $flowUuid))) {
            if (empty($image->getName())) {
                throw new BuilderException(sprintf(
                    'Docker image name to build "%s" is invalid.',
                    $image->getName()
                ));
            }

            $this->logger->warning('Can\'t find any reference registry for this tide', [
                'tide_uuid' => $tideUuid->toString(),
            ]);

            return $image;
        }

        if (empty($image->getName()) && null !== ($fullAddress = $registry->getFullAddress())) {
            $imageName = $registry->getFullAddress();
        } else {
            if (count($parts) == 1) {
                array_unshift($parts, $registry->getUsername());
            }

            if (count($parts) == 2) {
                array_unshift($parts, $registry->getServerAddress());
            }

            $imageName = implode('/', $parts);
        }

        return new Image(
            $imageName,
            $image->getTag(),
            $image->getReuse()
        );
    }

    /**
     * @param UuidInterface $bucketUuid
     * @param UuidInterface $flowUuid
     *
     * @return DockerRegistry|null
     */
    private function getReferenceRegistry(UuidInterface $bucketUuid, UuidInterface $flowUuid)
    {
        try {
            $registries = $this->bucketRepository->find($bucketUuid)->getDockerRegistries();
        } catch (BucketNotFound $e) {
            return null;
        }

        // Find a registry matching the flow
        foreach ($registries as $registry) {
            if (isset($registry->getAttributes()['flow']) && $registry->getAttributes()['flow'] == $flowUuid->toString()) {
                return $registry;
            }
        }

        if ($registries->count() > 0) {
            return $registries->first();
        }

        return null;
    }
}
