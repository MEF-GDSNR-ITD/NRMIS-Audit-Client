<?php

namespace Nrmis\AuditClient;

use Illuminate\Support\ServiceProvider;

class AuditClientServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/audit-client.php', 'audit-client'
        );

        $this->app->singleton(AuditClient::class, function ($app) {
            return new AuditClient($app['config']['audit-client']);
        });

        $this->app->alias(AuditClient::class, 'audit-client');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/audit-client.php' => config_path('audit-client.php'),
            ], 'audit-client-config');

            $this->publishes([
                __DIR__.'/../config/audit-client.php' => config_path('audit-client.php'),
            ], 'audit-client');
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            AuditClient::class,
            'audit-client',
        ];
    }
}
