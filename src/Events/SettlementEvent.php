<?php

declare(strict_types=1);

namespace AlphaPay\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Émis pour tout webhook `settlement.*` (requested/approved/success/failed/
 * cancelled) vérifié. `$data` = {id, reference, status, amount, currency}
 * -- cf. build_event_payload() côté AlphaPayBack.
 */
class SettlementEvent
{
    use Dispatchable, SerializesModels;

    /**
     * @param string $eventType "settlement.requested"|"settlement.approved"|"settlement.success"|"settlement.failed"|"settlement.cancelled"
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

    public function getAmount(): ?string
    {
        return $this->data['amount'] ?? null;
    }

    public function getCurrency(): ?string
    {
        return $this->data['currency'] ?? null;
    }

    public function isRequested(): bool
    {
        return $this->eventType === 'settlement.requested';
    }

    public function isApproved(): bool
    {
        return $this->eventType === 'settlement.approved';
    }

    public function isSuccess(): bool
    {
        return $this->eventType === 'settlement.success';
    }

    public function isFailed(): bool
    {
        return $this->eventType === 'settlement.failed';
    }

    public function isCancelled(): bool
    {
        return $this->eventType === 'settlement.cancelled';
    }
}
