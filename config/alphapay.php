<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Clé API
    |--------------------------------------------------------------------------
    |
    | Votre clé API AlphaPay -- sk_live_... en production, sk_test_... en
    | sandbox. L'environnement (AlphaPay\AlphaPayClient::$environment) est
    | déduit automatiquement du préfixe par le SDK, pas besoin de le déclarer
    | séparément ici.
    |
    */
    'secret_key' => env('ALPHAPAY_SECRET_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | URL de base de l'API
    |--------------------------------------------------------------------------
    |
    | Laissez à null pour utiliser la valeur par défaut du SDK
    | (https://api.alphapay.me/api/v1). À ne changer que pour pointer vers un
    | environnement de test interne.
    |
    */
    'base_url' => env('ALPHAPAY_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Timeout et retries
    |--------------------------------------------------------------------------
    |
    | Timeout des requêtes API en secondes, et nombre de tentatives
    | supplémentaires sur 429/5xx/erreur réseau (backoff exponentiel + gigue,
    | géré par AlphaPay\Http -- rien à implémenter ici).
    |
    */
    'timeout' => (int) env('ALPHAPAY_TIMEOUT', 30),
    'max_retries' => (int) env('ALPHAPAY_MAX_RETRIES', 2),

    /*
    |--------------------------------------------------------------------------
    | Secret webhook
    |--------------------------------------------------------------------------
    |
    | Secret de signature du webhook -- généré CÔTÉ ALPHAPAY (dashboard, à la
    | création ou à la rotation d'un endpoint webhook), pas localement.
    | Copiez-le ici depuis le dashboard, il n'est visible qu'une seule fois.
    |
    */
    'webhook_secret' => env('ALPHAPAY_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Chemin du webhook
    |--------------------------------------------------------------------------
    |
    | Chemin (relatif à la racine du site) où ce package écoute les webhooks
    | AlphaPay. Déclarez cette URL complète (ex. https://votresite.com/webhooks/alphapay)
    | dans le dashboard AlphaPay lors de la création de l'endpoint.
    |
    */
    'webhook_path' => env('ALPHAPAY_WEBHOOK_PATH', 'webhooks/alphapay'),

    /*
    |--------------------------------------------------------------------------
    | Middleware webhook
    |--------------------------------------------------------------------------
    |
    | Middleware appliqué à la route webhook. `api` suffit dans la plupart des
    | cas -- surtout ne PAS ajouter `VerifyCsrfToken`/`web`, un webhook n'a pas
    | de session/cookie CSRF côté AlphaPay.
    |
    */
    'webhook_middleware' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | Fenêtre anti-rejeu du webhook
    |--------------------------------------------------------------------------
    |
    | Tolérance en secondes entre l'horodatage du webhook et l'instant de
    | réception, avant de le rejeter comme rejeu potentiel -- doit rester
    | cohérente avec ce que AlphaPayBack applique de son côté (300s).
    |
    */
    'webhook_tolerance_seconds' => (int) env('ALPHAPAY_WEBHOOK_TOLERANCE', 300),

    /*
    |--------------------------------------------------------------------------
    | Stocker les transactions
    |--------------------------------------------------------------------------
    |
    | Si activé, chaque webhook `transaction.*` reçu et vérifié met à jour une
    | ligne dans la table `alphapay_transactions` (upsert par `reference`).
    | Désactivez si vous préférez gérer votre propre stockage depuis les
    | events (AlphaPay\Laravel\Events\TransactionEvent).
    |
    */
    'store_transactions' => (bool) env('ALPHAPAY_STORE_TRANSACTIONS', true),

    /*
    |--------------------------------------------------------------------------
    | Table des transactions
    |--------------------------------------------------------------------------
    */
    'transactions_table' => env('ALPHAPAY_TRANSACTIONS_TABLE', 'alphapay_transactions'),
];
