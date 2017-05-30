<?php

namespace ContinuousPipe\HttpLabs;

use ContinuousPipe\HttpLabs\Client\HttpLabsClient;
use ContinuousPipe\HttpLabs\Client\Stack;

class TraceableClient implements HttpLabsClient
{
    /**
     * @var array
     */
    private $createdStacks = [];
    /**
     * @var array
     */
    private $updatedStacks = [];

    /**
     * @var HttpLabsClient
     */
    private $decoratedClient;
    private $deletedStacks = [];

    public function __construct(HttpLabsClient $decoratedClient)
    {
        $this->decoratedClient = $decoratedClient;
    }

    /**
     * {@inheritdoc}
     */
    public function createStack(
        string $apiKey,
        string $projectIdentifier,
        string $name,
        string $backendUrl,
        array $middlewares
    ): Stack
    {
        $stack = $this->decoratedClient->createStack($apiKey, $projectIdentifier, $name, $backendUrl, $middlewares);

        $this->createdStacks[] = [
            'project_identifier' => $projectIdentifier,
            'backend_url' => $backendUrl,
            'stack' => $stack,
            'middlewares' => $middlewares
        ];

        return $stack;
    }

    /**
     * {@inheritdoc}
     */
    public function updateStack(string $apiKey, string $stackIdentifier, string $backendUrl, array $middlewares)
    {
        $this->decoratedClient->updateStack($apiKey, $stackIdentifier, $backendUrl, $middlewares);

        $this->updatedStacks[] = [
            'stack_identifier' => $stackIdentifier,
            'backend_url' => $backendUrl,
            'middlewares' => $middlewares
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function deleteStack(string $apiKey, string $stackIdentifier)
    {
        $this->decoratedClient->deleteStack($apiKey, $stackIdentifier);

        $this->deletedStacks[] = [
            'stack_identifier' => $stackIdentifier,
        ];
    }

    /**
     * @return array
     */
    public function getCreatedStacks(): array
    {
        return $this->createdStacks;
    }

    /**
     * @return array
     */
    public function getUpdatedStacks(): array
    {
        return $this->updatedStacks;
    }

    public function getDeletedStacks(): array
    {
        return $this->deletedStacks;
    }

}
