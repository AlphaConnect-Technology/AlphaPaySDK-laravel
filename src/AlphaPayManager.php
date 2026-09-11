<?php

declare(strict_types=1);

namespace AlphaPay\Laravel;

use AlphaPay\AlphaPayClient;
use AlphaPay\Resources\ApiKeysResource;
use AlphaPay\Resources\BalancesResource;
use AlphaPay\Resources\CheckoutSessionsResource;
use AlphaPay\Resources\CustomersResource;
use AlphaPay\Resources\PaymentLinksResource;
use AlphaPay\Resources\SettlementsResource;
use AlphaPay\Resources\TransactionsResource;
use AlphaPay\Resources\WalletTransfersResource;
use AlphaPay\Resources\WebhookEndpointsResource;

/**
 * Enveloppe fine autour de \AlphaPay\AlphaPayClient (SDK core alphapay/alphapay-php),
 * exposée par la façade AlphaPay::. AlphaPayClient expose ses ressources comme des
 * PROPRIÉTÉS publiques (`$client->transactions`), ce qui ne se proxy pas nativement
 * via une Facade Laravel (qui ne fait que du __callStatic sur des méthodes) -- ce
 * Manager n'ajoute donc qu'une méthode par ressource, sans dupliquer la moindre
 * logique HTTP/retry/pagination : tout reste dans le SDK core.
 */
class AlphaPayManager
{
    public function __construct(private AlphaPayClient $client)
    {
    }

    /** L'instance \AlphaPay\AlphaPayClient sous-jacente (accès direct si besoin). */
    public function client(): AlphaPayClient
    {
        return $this->client;
    }

    public function transactions(): TransactionsResource
    {
        return $this->client->transactions;
    }

    public function paymentLinks(): PaymentLinksResource
    {
        return $this->client->paymentLinks;
    }

    public function checkoutSessions(): CheckoutSessionsResource
    {
        return $this->client->checkoutSessions;
    }

    public function customers(): CustomersResource
    {
        return $this->client->customers;
    }

    public function settlements(): SettlementsResource
    {
        return $this->client->settlements;
    }

    public function walletTransfers(): WalletTransfersResource
    {
        return $this->client->walletTransfers;
    }

    public function balances(): BalancesResource
    {
        return $this->client->balances;
    }

    public function apiKeys(): ApiKeysResource
    {
        return $this->client->apiKeys;
    }

    public function webhookEndpoints(): WebhookEndpointsResource
    {
        return $this->client->webhookEndpoints;
    }

    /** 'live' ou 'sandbox', déduit du préfixe de la clé (sk_live_.../sk_test_...). */
    public function environment(): string
    {
        return $this->client->environment;
    }

    public function isLive(): bool
    {
        return $this->client->environment === 'live';
    }

    public function isSandbox(): bool
    {
        return $this->client->environment === 'sandbox';
    }
}
