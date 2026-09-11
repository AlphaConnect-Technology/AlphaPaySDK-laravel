<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('alphapay.transactions_table', 'alphapay_transactions'), function (Blueprint $table) {
            $table->id();
            // `alphapay_id` : UUID de la Transaction côté AlphaPayBack.
            // `reference` : identifiant lisible utilisé pour le rapprochement, unique.
            $table->uuid('alphapay_id')->nullable()->index();
            $table->string('reference')->unique()->index();
            // "PAIEMENT" (payin) ou "TRANSFERT" (payout) -- cf. TransactionType côté API.
            $table->string('type')->nullable();
            $table->string('status')->nullable()->index();
            // Décimales en string côté payload webhook (précision garantie) --
            // stockées ici en decimal Eloquent, casté proprement (cf. Model).
            $table->decimal('amount', 18, 2)->default(0);
            $table->decimal('fee', 18, 2)->default(0);
            $table->decimal('charged', 18, 2)->default(0);
            $table->decimal('net_amount', 18, 2)->default(0);
            $table->string('fee_charge_mode')->nullable();
            $table->string('currency', 3)->nullable();
            // Code réseau (ex. "mtn_bj") -- cf. alphapay/alphapay-php.
            $table->string('network')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('msisdn')->nullable();
            $table->json('metadata')->nullable();

            // Relation polymorphe optionnelle (trait HasAlphaPayPayments) --
            // lie une transaction à n'importe quel modèle (User, Order, ...).
            $table->nullableMorphs('payable');

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('alphapay.transactions_table', 'alphapay_transactions'));
    }
};
