<?php

namespace ContinuousPipe\River\CodeRepository;

class PullRequest
{
    /**
     * @var string
     */
    private $identifier;
    /**
     * @var string
     */
    private $title;

    public function __construct(string $identifier, string $title = null)
    {
        $this->identifier = $identifier;
        $this->title = $title;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }
}
