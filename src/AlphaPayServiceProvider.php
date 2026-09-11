<?php

declare(strict_types=1);

namespace AlphaPay\Laravel;

use AlphaPay\AlphaPayClient;
use Illuminate\Support\ServiceProvider;

class AlphaPayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/alphapay.php', 'alphapay');

        $this->app->singleton(AlphaPayClient::class, function ($app) {
            $config = $app['config']['alphapay'];

            $apiKey = $config['secret_key'] ?? '';
            if ($apiKey === '') {
                // On ne lève pas ici -- une commande artisan (route:list, config:cache,
                // migrate...) ne doit pas planter faute de clé. AlphaPayClient lève
                // lui-même InvalidArgumentException au premier usage réel si la clé
                // est toujours vide à ce moment-là.
                $apiKey = 'sk_test_not_configured';
            }

            return new AlphaPayClient(
                $apiKey,
                $config['base_url'] ?? null,
                $config['timeout'] ?? 30,
                $config['max_retries'] ?? 2
            );
        });

        $this->app->singleton(AlphaPayManager::class, function ($app) {
            return new AlphaPayManager($app->make(AlphaPayClient::class));
        });

        $this->app->alias(AlphaPayManager::class, 'alphapay');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\InstallCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/alphapay.php' => config_path('alphapay.php'),
        ], 'alphapay-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'alphapay-migrations');

        $this->loadRoutesFrom(__DIR__ . '/../routes/alphapay.php');

        // Volontairement PAS de loadMigrationsFrom() ici : `store_transactions`
        // est optionnel (cf. config/alphapay.php), donc la migration n'est
        // fournie qu'en publish -- l'auto-charger en plus créerait un conflit
        // (table créée deux fois) pour qui publie et migre comme demandé.
    }

    public function provides(): array
    {
        return [AlphaPayClient::class, AlphaPayManager::class, 'alphapay'];
    }
}
