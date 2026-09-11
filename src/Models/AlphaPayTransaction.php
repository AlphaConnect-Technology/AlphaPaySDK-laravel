<?php

declare(strict_types=1);

namespace AlphaPay\Laravel\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Copie locale (optionnelle, cf. config('alphapay.store_transactions')) des
 * transactions AlphaPay, tenue à jour par les webhooks `transaction.*` --
 * PAS une source de vérité : en cas de doute, l'API AlphaPay
 * (TransactionsResource::get()) fait toujours foi.
 */
class AlphaPayTransaction extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'charged' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('alphapay.transactions_table', 'alphapay_transactions');
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'SUCCESS');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['FAILED', 'CANCELLED']);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'SUCCESS';
    }

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    public function isFailed(): bool
    {
        return in_array($this->status, ['FAILED', 'CANCELLED'], true);
    }
}
