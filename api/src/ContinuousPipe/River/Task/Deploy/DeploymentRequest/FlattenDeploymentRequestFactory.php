<?php

namespace ContinuousPipe\River\Task\Deploy\DeploymentRequest;

use ContinuousPipe\Pipe\Client\DeploymentRequest;
use ContinuousPipe\River\Task\Deploy\DeployContext;
use ContinuousPipe\River\Task\Deploy\DeploymentRequestFactory;
use ContinuousPipe\River\Task\Deploy\DeployTaskConfiguration;
use ContinuousPipe\River\Task\Deploy\Naming\EnvironmentNamingStrategy;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FlattenDeploymentRequestFactory implements DeploymentRequestFactory
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var EnvironmentNamingStrategy
     */
    private $environmentNamingStrategy;

    /**
     * @param UrlGeneratorInterface     $urlGenerator
     * @param EnvironmentNamingStrategy $environmentNamingStrategy
     */
    public function __construct(UrlGeneratorInterface $urlGenerator, EnvironmentNamingStrategy $environmentNamingStrategy)
    {
        $this->urlGenerator = $urlGenerator;
        $this->environmentNamingStrategy = $environmentNamingStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function create(DeployContext $context, DeployTaskConfiguration $configuration)
    {
        $callbackUrl = $this->urlGenerator->generate('pipe_notification_post', [
            'tideUuid' => $context->getTideUuid(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $bucketUuid = $context->getTeam()->getBucketUuid();

        return new DeploymentRequest(
            new DeploymentRequest\Target(
                $this->environmentNamingStrategy->getName(
                    $context->getTideUuid(),
                    $configuration->getEnvironmentName()
                ),
                $configuration->getClusterIdentifier()
            ),
            new DeploymentRequest\Specification(
                $configuration->getComponents()
            ),
            new DeploymentRequest\Notification(
                $callbackUrl,
                $context->getTaskLog()->getId()
            ),
            $bucketUuid
        );
    }
}
