<?php

namespace SynergiTech\Postal;

use Postal\Client;
use Illuminate\Mail\TransportManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use SynergiTech\Postal\Controllers\WebhookController;

class PostalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->afterResolving(TransportManager::class, function (TransportManager $manager) {
            $this->extendTransportManager($manager);
        });
    }

    public function extendTransportManager(TransportManager $manager)
    {
        $manager->extend('postal', function () {
            $config = config('postal', []);
            return new PostalTransport(new Client($config['domain'] ?? null, $config['key'] ?? null));
        });
    }

    public function boot(): void
    {
        $basePath = __DIR__ . '/../';
        $configPath = $basePath . 'config/postal.php';

        // publish config
        $this->publishes([
            $configPath => config_path('postal.php'),
        ], 'config');

        $this->loadMigrationsFrom($basePath . 'migrations');

        // include the config file from the package if it isn't published
        $this->mergeConfigFrom($configPath, 'postal');

        $webhookRoute = config('postal.webhook.route');
        if (config('postal.enable.webhookreceiving') === true and is_string($webhookRoute)) {
            Route::post($webhookRoute, [WebhookController::class, 'process']);
        }
    }
}
