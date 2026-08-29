<?php

namespace App\Services\Scraping;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

class ProxyGateway
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly LoggerInterface $logger,
        private readonly bool $allowDirectConnection,
    ) {}

    public function next(): ?string
    {
        try {
            $response = $this->client->request('GET', '/proxy/next');
        } catch (GuzzleException $e) {
            return $this->handleUnavailable("proxy-service request failed: {$e->getMessage()}");
        }

        if ($response->getStatusCode() !== 200) {
            return $this->handleUnavailable("proxy-service returned status {$response->getStatusCode()}");
        }

        $payload = json_decode((string) $response->getBody(), true);
        $proxy = $payload['proxy'] ?? null;

        if (! is_string($proxy) || $proxy === '') {
            return $this->handleUnavailable('proxy-service returned an empty proxy');
        }

        return $proxy;
    }

    public function report(string $proxy, bool $success): void
    {
        try {
            $this->client->request('POST', '/proxy/report', [
                'json' => ['proxy' => $proxy, 'success' => $success],
            ]);
        } catch (GuzzleException $e) {
            $this->logger->warning("Failed to report proxy outcome to proxy-service: {$e->getMessage()}");
        }
    }

    private function handleUnavailable(string $reason): ?string
    {
        if ($this->allowDirectConnection) {
            $this->logger->warning("Proxy unavailable, falling back to a direct connection: {$reason}");

            return null;
        }

        throw new ProxyUnavailableException($reason);
    }
}
