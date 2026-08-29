<?php

namespace App\Providers;

use App\Services\Scraping\ProductScraper;
use App\Services\Scraping\ProxyGateway;
use App\Services\Scraping\UserAgentRotator;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->when(ProxyGateway::class)
            ->needs(ClientInterface::class)
            ->give(fn () => new Client([
                'base_uri' => config('scraping.proxy_service.base_uri'),
                'timeout' => config('scraping.proxy_service.timeout'),
            ]));

        $this->app->when(ProxyGateway::class)
            ->needs('$allowDirectConnection')
            ->give(fn () => config('scraping.allow_direct_connection'));

        $this->app->when(ProductScraper::class)
            ->needs(ClientInterface::class)
            ->give(fn () => new Client([
                'timeout' => config('scraping.request_timeout'),
            ]));

        $this->app->singleton(UserAgentRotator::class, fn () => new UserAgentRotator(config('scraping.user_agents')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
