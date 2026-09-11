<?php

declare(strict_types=1);

namespace AlphaPay\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \AlphaPay\AlphaPayClient client()
 * @method static \AlphaPay\Resources\TransactionsResource transactions()
 * @method static \AlphaPay\Resources\PaymentLinksResource paymentLinks()
 * @method static \AlphaPay\Resources\CheckoutSessionsResource checkoutSessions()
 * @method static \AlphaPay\Resources\CustomersResource customers()
 * @method static \AlphaPay\Resources\SettlementsResource settlements()
 * @method static \AlphaPay\Resources\WalletTransfersResource walletTransfers()
 * @method static \AlphaPay\Resources\BalancesResource balances()
 * @method static \AlphaPay\Resources\ApiKeysResource apiKeys()
 * @method static \AlphaPay\Resources\WebhookEndpointsResource webhookEndpoints()
 * @method static string environment()
 * @method static bool isLive()
 * @method static bool isSandbox()
 *
 * @see \AlphaPay\Laravel\AlphaPayManager
 */
class AlphaPay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'alphapay';
    }
}
