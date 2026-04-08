<?php

namespace App\Providers;

use App\Integrations\Pgvector\PgvectorService;
use App\Integrations\Waha\WahaService;
use App\Services\Channels\WhatsappChannel;
use App\Socialite\OpenAIProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WahaService::class);
        $this->app->singleton(PgvectorService::class);
        $this->app->singleton(WhatsappChannel::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $socialite = $this->app->make(Factory::class);
        $socialite->extend('openai', function ($app) use ($socialite) {
            $config = $app['config']['services.openai'];

            return $socialite->buildProvider(
                OpenAIProvider::class,
                $config
            );
        });
    }
}
