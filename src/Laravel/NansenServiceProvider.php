<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Laravel;

use Illuminate\Support\ServiceProvider;
use Psr\Http\Client\ClientInterface;
use Tigusigalpa\Nansen\Config;
use Tigusigalpa\Nansen\NansenClient;

final class NansenServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/nansen.php',
            'nansen',
        );

        $this->app->singleton(NansenClient::class, function ($app): NansenClient {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('nansen', []);

            $httpClient = null;
            if (!empty($config['http_client']) && is_string($config['http_client'])) {
                $httpClient = $app->make($config['http_client']);
                if (!$httpClient instanceof ClientInterface) {
                    throw new \InvalidArgumentException(
                        'Configured Nansen HTTP client must implement ' . ClientInterface::class,
                    );
                }
            } elseif ($app->bound(ClientInterface::class)) {
                $httpClient = $app->make(ClientInterface::class);
                if (!$httpClient instanceof ClientInterface) {
                    throw new \InvalidArgumentException(
                        'Bound Nansen HTTP client must implement ' . ClientInterface::class,
                    );
                }
            }

            $nansenConfig = Config::fromArray($config);

            return new NansenClient($nansenConfig, $httpClient);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/nansen.php' => $this->app->configPath('nansen.php'),
            ], 'nansen-config');
        }
    }
}
