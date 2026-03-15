<?php

namespace Back2ops\ElevenLabs;

use Illuminate\Support\ServiceProvider;
use Back2ops\ElevenLabs\Http\ElevenLabsClient;

class ElevenLabsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the package services.
     * Called after all service providers are registered — safe to use other services here.
     */
    public function boot(): void
    {
        // Publish the config file so users can customize settings.
        // Usage: php artisan vendor:publish --tag=elevenlabs-config
        $this->publishes([
            __DIR__ . '/../config/elevenlabs.php' => config_path('elevenlabs.php'),
        ], 'elevenlabs-config');
    }

    /**
     * Register package bindings into the service container.
     * Called first — don't use other services here; just bind things.
     */
    public function register(): void
    {
        // Merge defaults so unpublished config keys still work.
        $this->mergeConfigFrom(
            __DIR__ . '/../config/elevenlabs.php',
            'elevenlabs'
        );

        // Bind our main client as a singleton so one HTTP client instance is shared
        // across the entire request lifecycle.
        $this->app->singleton(ElevenLabsClient::class, function ($app) {
            return new ElevenLabsClient(
                apiKey: config('elevenlabs.api_key'),
                baseUrl: config('elevenlabs.base_url'),
                timeout: config('elevenlabs.timeout'),
            );
        });

        // Bind the main ElevenLabs service, injecting the HTTP client.
        $this->app->singleton(ElevenLabs::class, function ($app) {
            return new ElevenLabs($app->make(ElevenLabsClient::class));
        });

        // Allow resolution by the short interface name too (for Facades).
        $this->app->alias(ElevenLabs::class, 'elevenlabs');
    }
}
