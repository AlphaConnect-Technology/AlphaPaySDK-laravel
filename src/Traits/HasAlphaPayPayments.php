<?php

declare(strict_types=1);

namespace AlphaPay\Laravel\Traits;

use AlphaPay\Laravel\Facades\AlphaPay;
use AlphaPay\Laravel\Models\AlphaPayTransaction;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * À ajouter sur un modèle (User, Order, ...) pour le lier aux transactions
 * AlphaPay stockées localement (cf. config('alphapay.store_transactions')).
 * Nécessite la migration publiée (`alphapay:install` ou
 * `vendor:publish --tag=alphapay-migrations`).
 */
trait HasAlphaPayPayments
{
    public function alphaPayTransactions(): MorphMany
    {
        return $this->morphMany(AlphaPayTransaction::class, 'payable');
    }

    /**
     * Initie un encaissement direct (payinInitialize) et rattache la
     * metadata `payable_type`/`payable_id` pour le rapprochement -- la
     * ligne locale n'apparaîtra qu'au webhook `transaction.*` suivant
     * (ce stockage est tenu par le webhook, pas par cet appel).
     *
     * @param array<string, mixed> $params Mêmes clés que TransactionsResource::payinInitialize().
     * @param string|bool|null $idempotencyKey
     * @return array<string, mixed>
     */
    public function payAlphaPay(array $params, $idempotencyKey = null): array
    {
        $params['metadata'] = array_merge($params['metadata'] ?? [], [
            'payable_type' => get_class($this),
            'payable_id' => $this->getKey(),
        ]);

        return AlphaPay::transactions()->payinInitialize($params, $idempotencyKey);
    }

    public function successfulAlphaPayPayments(): MorphMany
    {
        return $this->alphaPayTransactions()->successful();
    }

    public function pendingAlphaPayPayments(): MorphMany
    {
        return $this->alphaPayTransactions()->pending();
    }

    public function hasSuccessfulAlphaPayPayments(): bool
    {
        return $this->successfulAlphaPayPayments()->exists();
    }

    public function totalAlphaPayPaid(): float
    {
        return (float) $this->successfulAlphaPayPayments()->sum('amount');
    }
}
