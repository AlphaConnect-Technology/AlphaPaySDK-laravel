# Avant publication Packagist

Wrapper Laravel autour de [`alphapay/alphapay-php`](https://github.com/AlphaConnect-Technology/AlphaPaySDK-php)
(déjà publié, v0.1.1). Ce package n'ajoute que l'intégration framework --
toute la mécanique HTTP/retry/pagination/signature reste dans le SDK core.

## Bloquant

- [x] Tester `composer test` après un vrai `composer install` -- fait :
      Laravel 12.69.2 résolu, 116 packages installés, `composer run lint`
      propre sur tout `src/`, **9/9 tests verts (29 assertions)** contre un
      vrai PostgreSQL (pas sqlite -- `pdo_sqlite` indisponible dans
      l'environnement de build ; un cluster Postgres jetable a été utilisé
      pour la validation, sans toucher à une base existante). Couvre :
      bindings du Service Provider (singleton, config, environnement),
      Facade (les 9 ressources), et le webhook de bout en bout (signature
      valide → event + upsert DB, signature invalide → 400, timestamp hors
      fenêtre → 400 rejeté comme rejeu, `settlement.*` qui ne touche pas la
      table transactions).
- [ ] Vérifier qu'un vrai webhook AlphaPay (pas seulement le HMAC reconstruit
      en test) est bien accepté par `WebhookController` -- créer un endpoint
      de test depuis le dashboard pointant vers une URL exposée (ex. ngrok)
      et déclencher un paiement sandbox réel.
- [x] Choisir le nom de package Packagist définitif — confirmé `alphapay/alphapay-laravel`,
      vérifié libre (404 sur `packagist.org/packages/alphapay/alphapay-laravel.json`).
- [x] Versions Laravel réellement supportées — la plage déclarée
      (`illuminate/* : ^9.0|^10.0|^11.0|^12.0`) est désormais **vérifiée aux
      deux bornes et au milieu**, pas juste supposée : les 9 mêmes tests
      (29 assertions) tournent verts contre Laravel 9/testbench 7
      (PHPUnit 9.6), Laravel 10/testbench 8 (PHPUnit 10.5) et Laravel
      12/testbench 10 (PHPUnit 11.5, config par défaut). Corrigé au passage :
      `phpunit.xml` utilisait l'élément `<source>`, invalide avant PHPUnit
      10 (avertissement de schéma sous Laravel 9) -- retiré, portée de
      couverture par défaut suffisante.

## Souhaitable avant v1.0.0

- [ ] CI (GitHub Actions) : matrice PHP × Laravel (au moins la plus basse et
      la plus haute version déclarée), `composer test` sur chaque PR.
- [ ] Vues Blade / composant de bouton de paiement prêt à l'emploi (le SDK
      core ne fournit qu'un `checkout_url` à rediriger -- pas de vue
      fournie ici pour l'instant).
- [ ] Commande `alphapay:webhook-status` qui interroge
      `AlphaPay::webhookEndpoints()->list()` pour afficher les endpoints
      configurés côté dashboard (lecture seule, accessible via clé API).
- [ ] Si le plugin WooCommerce ou d'autres intégrations PHP existantes sont
      un jour portés vers Laravel : vérifier qu'il n'y a pas de logique de
      paiement dupliquée/divergente à remplacer plutôt qu'à faire coexister.

## Fait

- [x] Architecture en wrapper fin : `AlphaPayManager` n'expose qu'une
      méthode par ressource (`transactions()`, `paymentLinks()`, ...), sans
      dupliquer la logique HTTP -- tout délégué à `AlphaPay\AlphaPayClient`
      du SDK core.
- [x] Exceptions : aucune classe Laravel-spécifique, réutilise directement
      `AlphaPay\Exceptions\*` du SDK core (évite la dérive entre deux
      hiérarchies d'exceptions pour la même erreur).
- [x] Webhook : signature vérifiée via `AlphaPay\Webhook::verifySignature()`
      du SDK core (HMAC-SHA256 + anti-rejeu 300s), pas de HMAC maison.
      Événements/payloads vérifiés contre `WebhookEventType` et
      `build_event_payload()` côté AlphaPayBack (pas inventés) :
      `transaction.{created,success,failed,cancelled}`,
      `settlement.{requested,approved,success,failed,cancelled}`,
      `wallet_transfer.{requested,completed,rejected}`.
- [x] Table `alphapay_transactions` : champs alignés sur le payload webhook
      réel (`reference`, `type`, `status`, `amount`, `fee`, `charged`,
      `net_amount`, `fee_charge_mode`, `currency`, `network`, `country`,
      `msisdn`, `metadata`) -- pas de champs inventés (pas de `gateway`,
      `customer_email`, etc. qui n'existent pas dans le payload AlphaPay).
      Statuts confirmés (`PENDING`/`SUCCESS`/`FAILED`/`CANCELLED`) contre
      `TransactionStatus` côté AlphaPayBack.
- [x] Pas de génération de secret webhook côté client (contrairement à des
      packages similaires) : le secret est émis par AlphaPay au dashboard,
      la commande d'installation se contente de le rappeler.
- [x] Migration fournie en publish uniquement (pas d'auto-`loadMigrationsFrom`)
      pour éviter un conflit de double création de table si le consommateur
      publie ET migre comme documenté.
- [x] Tests écrits : bindings du Service Provider (singleton, config,
      environnement), Facade (toutes les ressources), webhook (signature
      valide → event + stockage DB, signature invalide → 400, timestamp
      hors fenêtre → 400 rejeté, événement `settlement.*` ne touche pas la
      table transactions).
