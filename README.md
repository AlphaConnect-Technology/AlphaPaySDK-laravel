# alphapay/alphapay-laravel

SDK Laravel officiel pour l'API AlphaPay (agrégateur de paiement multi-gateway).

> **Statut : v0.1.0, non publié.** Wrapper Laravel (Service Provider, Facade,
> webhooks, stockage optionnel des transactions) au-dessus du SDK PHP
> [`alphapay/alphapay-php`](https://github.com/AlphaConnect-Technology/AlphaPaySDK-php)
> déjà publié sur Packagist. Ce package ne réimplémente **aucune** logique
> HTTP/retry/pagination/signature -- tout vient du SDK core, ce package
> n'ajoute que l'intégration Laravel (config, DI, routes, events, Eloquent).
> Voir [CHECKLIST.md](./CHECKLIST.md) avant publication.

## Installation

```bash
composer require alphapay/alphapay-laravel
php artisan alphapay:install
```

L'installation automatique publie la config, propose de publier + exécuter
la migration des transactions, et affiche l'URL de webhook à déclarer dans
le dashboard AlphaPay.

### Installation manuelle

```bash
php artisan vendor:publish --tag=alphapay-config
php artisan vendor:publish --tag=alphapay-migrations
php artisan migrate
```

## Configuration

```env
ALPHAPAY_SECRET_KEY=sk_test_...        # sk_live_... en production
ALPHAPAY_WEBHOOK_SECRET=whsec_...      # copié depuis le dashboard AlphaPay, généré CÔTÉ ALPHAPAY
ALPHAPAY_WEBHOOK_PATH=webhooks/alphapay
```

L'environnement (`sandbox`/`live`) est déduit automatiquement du préfixe de
la clé par le SDK core -- rien à déclarer séparément. Voir
[config/alphapay.php](config/alphapay.php) pour toutes les options
(timeout, retries, tolérance anti-rejeu du webhook, stockage des
transactions...).

## Utilisation

### Avec la Facade

```php
use AlphaPay\Laravel\Facades\AlphaPay;

$payment = AlphaPay::transactions()->payinInitialize([
    'amount' => 5000,
    'currency' => 'XOF',
    'country' => 'BJ',
    'network' => 'mtn_bj', // format réel attendu par l'API -- voir la doc du SDK core
    'customer' => ['full_name' => 'Ayaba Client', 'phone' => '+22900000000'],
    'description' => 'Commande #1234',
], idempotencyKey: true);
```

Toutes les ressources du SDK core sont exposées en méthodes sur la Facade :
`transactions()`, `paymentLinks()`, `checkoutSessions()`, `customers()`,
`settlements()`, `walletTransfers()`, `balances()`, `apiKeys()`,
`webhookEndpoints()`. Chacune renvoie directement l'objet `Resource` du SDK
core (`AlphaPay\Resources\...`) -- même API, mêmes exceptions, mêmes
restrictions `dashboard_only` que documentées dans le SDK PHP.

### Avec l'injection de dépendances

```php
use AlphaPay\AlphaPayClient;

class CheckoutController
{
    public function store(AlphaPayClient $alphapay)
    {
        return $alphapay->paymentLinks->create([
            'name' => 'Facture #42',
            'currency' => 'XOF',
            'amount' => 15000,
        ]);
    }
}
```

`AlphaPayClient` (l'instance brute du SDK core, propriétés `->transactions`,
`->paymentLinks`, etc.) est bindée en singleton dans le conteneur -- vous
pouvez toujours l'injecter directement si vous préférez son API native
plutôt que la Facade.

## Gestion des erreurs

Les exceptions typées viennent directement du SDK core, aucune duplication
côté Laravel :

```php
use AlphaPay\Exceptions\AlphaPayValidationException;
use AlphaPay\Exceptions\AlphaPayRateLimitException;

try {
    AlphaPay::paymentLinks()->create(['name' => 'Facture']);
} catch (AlphaPayValidationException $e) {
    return back()->withErrors($e->getFieldErrors());
} catch (AlphaPayRateLimitException $e) {
    // ...
}
```

Voir le [README du SDK core](https://github.com/AlphaConnect-Technology/AlphaPaySDK-php#gestion-des-erreurs)
pour la liste complète.

## Webhooks

Ce package enregistre automatiquement une route `POST /{ALPHAPAY_WEBHOOK_PATH}`
(défaut `webhooks/alphapay`) qui :

1. Vérifie la signature via `AlphaPay\Webhook::verifySignature()` du SDK
   core (HMAC-SHA256, comparaison en temps constant, fenêtre anti-rejeu de
   300s) -- **jamais** de vérification maison.
2. Émet `AlphaPay\Laravel\Events\WebhookReceived` pour tout webhook vérifié.
3. Route ensuite vers un event spécifique selon le préfixe :

| Préfixe événement | Event Laravel | Exemples de valeurs `eventType` |
|---|---|---|
| `transaction.*` | `TransactionEvent` | `transaction.created`, `.success`, `.failed`, `.cancelled` |
| `settlement.*` | `SettlementEvent` | `settlement.requested`, `.approved`, `.success`, `.failed`, `.cancelled` |
| `wallet_transfer.*` | `WalletTransferEvent` | `wallet_transfer.requested`, `.completed`, `.rejected` |

```php
use AlphaPay\Laravel\Events\TransactionEvent;
use Illuminate\Support\Facades\Event;

Event::listen(function (TransactionEvent $event) {
    if ($event->isSuccess()) {
        Order::where('reference', $event->getReference())->update(['status' => 'paid']);
    }
});
```

Si `alphapay.store_transactions` est activé (par défaut), chaque webhook
`transaction.*` met aussi à jour une ligne dans la table
`alphapay_transactions` (upsert par `reference`) -- voir
[`AlphaPayTransaction`](src/Models/AlphaPayTransaction.php). Ce n'est
qu'une copie locale tenue par les webhooks, jamais la source de vérité :
en cas de doute, `AlphaPay::transactions()->get($id)` fait toujours foi.

**Important** : le secret webhook (`ALPHAPAY_WEBHOOK_SECRET`) est généré
**côté AlphaPay**, visible une seule fois à la création/rotation de
l'endpoint dans le dashboard -- ce package ne le génère jamais localement.

## Lier des paiements à un modèle Eloquent

```php
use AlphaPay\Laravel\Traits\HasAlphaPayPayments;

class Order extends Model
{
    use HasAlphaPayPayments;
}

$order->payAlphaPay([
    'amount' => 5000,
    'currency' => 'XOF',
    'country' => 'BJ',
    'network' => 'mtn_bj',
    'customer' => ['full_name' => $order->customer_name, 'phone' => $order->customer_phone],
]);

$order->alphaPayTransactions;          // relation morphMany
$order->hasSuccessfulAlphaPayPayments();
$order->totalAlphaPayPaid();
```

## Développement

```bash
composer install
composer run lint
composer test    # orchestra/testbench -- ServiceProvider + webhook (signature valide/invalide/rejeu)
```

`composer test` a besoin d'une base PostgreSQL accessible (les migrations
Laravel s'exécutent réellement pendant `WebhookControllerTest`) : par
défaut `127.0.0.1:5433`, base `alphapay_laravel_test`, utilisateur
`testuser`, sans mot de passe -- surchageable via les variables d'env
`TEST_DB_HOST`/`TEST_DB_PORT`/`TEST_DB_NAME`/`TEST_DB_USER` (voir
[tests/TestCase.php](tests/TestCase.php)).

## Licence

MIT
