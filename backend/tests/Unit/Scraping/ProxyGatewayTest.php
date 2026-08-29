<?php

namespace Tests\Unit\Scraping;

use App\Services\Scraping\ProxyGateway;
use App\Services\Scraping\ProxyUnavailableException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ProxyGatewayTest extends TestCase
{
    private function clientWithResponses(array $responses): Client
    {
        $mock = new MockHandler($responses);

        return new Client(['handler' => HandlerStack::create($mock)]);
    }

    public function test_next_returns_the_proxy_from_a_successful_response(): void
    {
        $client = $this->clientWithResponses([
            new Response(200, [], json_encode(['proxy' => 'http://10.0.0.1:8000'])),
        ]);
        $gateway = new ProxyGateway($client, new NullLogger, allowDirectConnection: false);

        $this->assertSame('http://10.0.0.1:8000', $gateway->next());
    }

    public function test_next_throws_when_the_service_is_unreachable_and_direct_connection_is_not_allowed(): void
    {
        $client = $this->clientWithResponses([
            new ConnectException('connection refused', new Request('GET', '/proxy/next')),
        ]);
        $gateway = new ProxyGateway($client, new NullLogger, allowDirectConnection: false);

        $this->expectException(ProxyUnavailableException::class);

        $gateway->next();
    }

    public function test_next_returns_null_when_the_service_is_unreachable_and_direct_connection_is_explicitly_allowed(): void
    {
        $client = $this->clientWithResponses([
            new ConnectException('connection refused', new Request('GET', '/proxy/next')),
        ]);
        $gateway = new ProxyGateway($client, new NullLogger, allowDirectConnection: true);

        $this->assertNull($gateway->next());
    }

    public function test_next_throws_when_the_service_reports_no_proxies_available(): void
    {
        $client = $this->clientWithResponses([
            new Response(503, [], json_encode(['error' => 'no healthy proxies available'])),
        ]);
        $gateway = new ProxyGateway($client, new NullLogger, allowDirectConnection: false);

        $this->expectException(ProxyUnavailableException::class);

        $gateway->next();
    }

    public function test_report_does_not_throw_when_the_service_is_unreachable(): void
    {
        $client = $this->clientWithResponses([
            new ConnectException('connection refused', new Request('POST', '/proxy/report')),
        ]);
        $gateway = new ProxyGateway($client, new NullLogger, allowDirectConnection: false);

        $gateway->report('http://10.0.0.1:8000', success: false);

        $this->addToAssertionCount(1);
    }
}
