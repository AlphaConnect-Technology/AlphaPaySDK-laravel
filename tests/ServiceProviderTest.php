<?php

declare(strict_types=1);

namespace AlphaPay\Laravel\Tests;

use AlphaPay\AlphaPayClient;
use AlphaPay\Laravel\AlphaPayManager;
use AlphaPay\Laravel\Facades\AlphaPay;

class ServiceProviderTest extends TestCase
{
    public function test_alphapay_client_is_bound_as_singleton(): void
    {
        $a = $this->app->make(AlphaPayClient::class);
        $b = $this->app->make(AlphaPayClient::class);

        $this->assertInstanceOf(AlphaPayClient::class, $a);
        $this->assertSame($a, $b);
    }

    public function test_alphapay_client_reads_config(): void
    {
        $client = $this->app->make(AlphaPayClient::class);

        // sk_test_... -> environnement sandbox, déduit par le SDK core lui-même.
        $this->assertSame('sandbox', $client->environment);
    }

    public function test_alphapay_alias_resolves_to_manager(): void
    {
        $manager = $this->app->make('alphapay');

        $this->assertInstanceOf(AlphaPayManager::class, $manager);
    }

    public function test_facade_exposes_the_underlying_client(): void
    {
        $this->assertSame('sandbox', AlphaPay::environment());
        $this->assertTrue(AlphaPay::isSandbox());
        $this->assertFalse(AlphaPay::isLive());
        $this->assertInstanceOf(AlphaPayClient::class, AlphaPay::client());
    }

    public function test_facade_exposes_every_resource(): void
    {
        $this->assertNotNull(AlphaPay::transactions());
        $this->assertNotNull(AlphaPay::paymentLinks());
        $this->assertNotNull(AlphaPay::checkoutSessions());
        $this->assertNotNull(AlphaPay::customers());
        $this->assertNotNull(AlphaPay::settlements());
        $this->assertNotNull(AlphaPay::walletTransfers());
        $this->assertNotNull(AlphaPay::balances());
        $this->assertNotNull(AlphaPay::apiKeys());
        $this->assertNotNull(AlphaPay::webhookEndpoints());
    }

}
