<?php

declare(strict_types=1);

namespace AlphaPay\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Émis pour tout webhook `transaction.*` (created/success/failed/cancelled)
 * vérifié -- cf. WebhookEventType côté AlphaPayBack. La forme de `$data`
 * reproduit exactement build_event_payload() côté API :
 * {id, reference, type, status, amount, fee, charged, net_amount,
 *  fee_charge_mode, currency, network, country, msisdn, metadata}.
 *
 * `amount`/`fee`/`charged`/`net_amount` sont des chaînes décimales côté API
 * (pas des float, pour éviter toute perte de précision) -- gardées telles
 * quelles ici, à caster vous-même selon vos besoins (bcmath, decimal cast
 * Eloquent, etc.).
 */
class TransactionEvent
{
    use Dispatchable, SerializesModels;

    /**
     * @param string $eventType "transaction.created"|"transaction.success"|"transaction.failed"|"transaction.cancelled"
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $eventType,
        public array $data
    ) {
    }

    public function getId(): ?string
    {
        return $this->data['id'] ?? null;
    }

    public function getReference(): ?string
    {
        return $this->data['reference'] ?? null;
    }

    /** "PAIEMENT" (payin) ou "TRANSFERT" (payout) -- cf. TransactionType côté API. */
    public function getType(): ?string
    {
        return $this->data['type'] ?? null;
    }

    public function getStatus(): ?string
    {
        return $this->data['status'] ?? null;
    }

    /** Montant demandé, en chaîne décimale (ex. "5000.00"). */
    public function getAmount(): ?string
    {
        return $this->data['amount'] ?? null;
    }

    public function getCurrency(): ?string
    {
        return $this->data['currency'] ?? null;
    }

    /** Code réseau (ex. "mtn_bj") -- cf. README de alphapay/alphapay-php sur le format attendu. */
    public function getNetwork(): ?string
    {
        return $this->data['network'] ?? null;
    }

    public function getCountry(): ?string
    {
        return $this->data['country'] ?? null;
    }

    /** Numéro réellement débité (PAIEMENT) ou crédité (TRANSFERT). Peut être vide sur d'anciennes transactions. */
    public function getMsisdn(): ?string
    {
        return $this->data['msisdn'] ?? null;
    }

    /** @return array<string, mixed> Metadata recopiée telle que fournie à l'initiation (softpay/checkout-session/payout). */
    public function getMetadata(): array
    {
        return $this->data['metadata'] ?? [];
    }

    public function isCreated(): bool
    {
        return $this->eventType === 'transaction.created';
    }

    public function isSuccess(): bool
    {
        return $this->eventType === 'transaction.success';
    }

    public function isFailed(): bool
    {
        return $this->eventType === 'transaction.failed';
    }

    public function isCancelled(): bool
    {
        return $this->eventType === 'transaction.cancelled';
    }
}
