<?php

declare(strict_types=1);

namespace AlphaPay\Laravel\Tests;

use AlphaPay\Laravel\AlphaPayServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [AlphaPayServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('alphapay.secret_key', 'sk_test_dummy_key_for_tests');
        $app['config']->set('alphapay.webhook_secret', 'whsec_test_secret');

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'pgsql',
            'host' => env('TEST_DB_HOST', '127.0.0.1'),
            'port' => env('TEST_DB_PORT', 5433),
            'database' => env('TEST_DB_NAME', 'alphapay_laravel_test'),
            'username' => env('TEST_DB_USER', 'testuser'),
            'password' => '',
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
        ]);
    }
}
