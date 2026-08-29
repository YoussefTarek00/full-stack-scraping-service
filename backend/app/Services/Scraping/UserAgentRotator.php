<?php

namespace App\Services\Scraping;

use InvalidArgumentException;

class UserAgentRotator
{
    private int $index = 0;

    public function __construct(private readonly array $userAgents)
    {
        if ($this->userAgents === []) {
            throw new InvalidArgumentException('UserAgentRotator requires at least one user agent.');
        }
    }

    public function next(): string
    {
        $userAgent = $this->userAgents[$this->index];
        $this->index = ($this->index + 1) % count($this->userAgents);

        return $userAgent;
    }
}
