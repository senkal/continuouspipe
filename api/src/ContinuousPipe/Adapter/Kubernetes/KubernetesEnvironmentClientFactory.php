<?php

namespace ContinuousPipe\Adapter\Kubernetes;

use ContinuousPipe\Adapter\ClusterNotSupported;
use ContinuousPipe\Adapter\DispatchEventClientDecorator;
use ContinuousPipe\Adapter\EnvironmentClientFactory;
use ContinuousPipe\Adapter\Kubernetes\Client\KubernetesClientFactory;
use ContinuousPipe\Adapter\Kubernetes\Inspector\NamespaceInspector;
use ContinuousPipe\Security\Credentials\Cluster;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class KubernetesEnvironmentClientFactory implements EnvironmentClientFactory
{
    /**
     * @var KubernetesClientFactory
     */
    private $clientFactory;

    /**
     * @var NamespaceInspector
     */
    private $namespaceInspector;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        KubernetesClientFactory $clientFactory,
        NamespaceInspector $namespaceInspector,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger
    ) {
        $this->clientFactory = $clientFactory;
        $this->namespaceInspector = $namespaceInspector;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getByCluster(Cluster $cluster)
    {
        if (!$cluster instanceof Cluster\Kubernetes) {
            throw new ClusterNotSupported('Only Kubernetes clusters supported');
        }

        return new KubernetesEnvironmentClient(
            $this->clientFactory->getByCluster($cluster),
            $this->namespaceInspector,
            $this->eventDispatcher,
            $this->logger
        );
    }
}
