<?php

declare(strict_types=1);

namespace AlphaPay\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Émis pour tout webhook `wallet_transfer.*` (requested/completed/rejected)
 * vérifié. `$data` = {id, reference, status, from_country, from_amount,
 * from_currency, to_country, to_amount, to_currency} -- cf.
 * build_event_payload() côté AlphaPayBack. Les transferts créés via
 * AlphaPay::walletTransfers()->create() (clé de scope PAYOUT/BOTH) comme
 * depuis le dashboard émettent ces événements -- c'est le moyen fiable de
 * savoir quand un transfert PENDING est approuvé (completed) ou refusé
 * (rejected) par AlphaPay. Le payload ne contient pas les frais : `to_amount`
 * est déjà net, le détail (fee_amount...) reste disponible via
 * AlphaPay::walletTransfers()->get($event->getId()).
 */
class WalletTransferEvent
{
    use Dispatchable, SerializesModels;

    /**
     * @param string $eventType "wallet_transfer.requested"|"wallet_transfer.completed"|"wallet_transfer.rejected"
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

    public function getStatus(): ?string
    {
        return $this->data['status'] ?? null;
    }

    public function getFromCountry(): ?string
    {
        return $this->data['from_country'] ?? null;
    }

    public function getFromAmount(): ?string
    {
        return $this->data['from_amount'] ?? null;
    }

    public function getFromCurrency(): ?string
    {
        return $this->data['from_currency'] ?? null;
    }

    public function getToCountry(): ?string
    {
        return $this->data['to_country'] ?? null;
    }

    public function getToAmount(): ?string
    {
        return $this->data['to_amount'] ?? null;
    }

    public function getToCurrency(): ?string
    {
        return $this->data['to_currency'] ?? null;
    }

    public function isRequested(): bool
    {
        return $this->eventType === 'wallet_transfer.requested';
    }

    public function isCompleted(): bool
    {
        return $this->eventType === 'wallet_transfer.completed';
    }

    public function isRejected(): bool
    {
        return $this->eventType === 'wallet_transfer.rejected';
    }
}
