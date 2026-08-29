<?php

namespace Tests\Unit\Scraping;

use App\Services\Scraping\UserAgentRotator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UserAgentRotatorTest extends TestCase
{
    public function test_it_cycles_through_all_configured_user_agents(): void
    {
        $rotator = new UserAgentRotator(['ua-a', 'ua-b', 'ua-c']);

        $this->assertSame(
            ['ua-a', 'ua-b', 'ua-c', 'ua-a', 'ua-b', 'ua-c'],
            [
                $rotator->next(),
                $rotator->next(),
                $rotator->next(),
                $rotator->next(),
                $rotator->next(),
                $rotator->next(),
            ],
        );
    }

    public function test_it_rejects_an_empty_list(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new UserAgentRotator([]);
    }
}
