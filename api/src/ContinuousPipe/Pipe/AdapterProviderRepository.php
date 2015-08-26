<?php

namespace ContinuousPipe\Pipe;

use ContinuousPipe\Adapter\AdapterRegistry;
use ContinuousPipe\Adapter\Provider;

class AdapterProviderRepository
{
    /**
     * @var AdapterRegistry
     */
    private $adapterRegistry;

    /**
     * @param AdapterRegistry $adapterRegistry
     */
    public function __construct(AdapterRegistry $adapterRegistry)
    {
        $this->adapterRegistry = $adapterRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        $providers = [];

        foreach ($this->adapterRegistry->getAdapters() as $adapter) {
            foreach ($adapter->getRepository()->findAll() as $provider) {
                $providers[] = $provider;
            }
        }

        return $providers;
    }

    /**
     * {@inheritdoc}
     */
    public function findByTypeAndIdentifier($type, $identifier)
    {
        return $this->adapterRegistry->getByType($type)->getRepository()->find($identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function create(Provider $provider)
    {
        return $this->adapterRegistry->getByType($provider->getAdapterType())->getRepository()->create($provider);
    }
}
