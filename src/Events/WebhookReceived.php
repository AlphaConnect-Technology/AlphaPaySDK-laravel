<?php

declare(strict_types=1);

namespace AlphaPay\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Émis pour CHAQUE webhook AlphaPay vérifié (signature valide, hors fenêtre
 * de rejeu), quel que soit le type -- avant le routage vers TransactionEvent/
 * SettlementEvent/WalletTransferEvent. Utile pour du logging générique.
 */
class WebhookReceived
{
    use Dispatchable, SerializesModels;

    /**
     * @param string $eventType Ex. "transaction.success", "settlement.approved".
     * @param array<string, mixed> $data Enveloppe complète décodée (event + data).
     */
    public function __construct(
        public string $eventType,
        public array $data
    ) {
    }
}
