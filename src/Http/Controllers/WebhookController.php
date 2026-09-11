<?php

declare(strict_types=1);

namespace AlphaPay\Laravel\Http\Controllers;

use AlphaPay\Exceptions\AlphaPayWebhookSignatureException;
use AlphaPay\Laravel\Events\SettlementEvent;
use AlphaPay\Laravel\Events\TransactionEvent;
use AlphaPay\Laravel\Events\WalletTransferEvent;
use AlphaPay\Laravel\Events\WebhookReceived;
use AlphaPay\Laravel\Models\AlphaPayTransaction;
use AlphaPay\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        // Corps BRUT, jamais déjà décodé -- la signature porte sur les octets
        // exacts envoyés (cf. AlphaPay\Webhook::verifySignature()).
        $rawBody = $request->getContent();
        $signature = $request->header('X-Webhook-Signature', '');
        $timestamp = $request->header('X-Webhook-Timestamp', '');
        $secret = (string) config('alphapay.webhook_secret', '');

        if ($secret === '') {
            Log::warning('AlphaPay webhook reçu mais alphapay.webhook_secret non configuré -- rejeté.');
            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }

        try {
            $event = Webhook::verifySignature(
                payload: $rawBody,
                signature: $signature,
                timestamp: $timestamp,
                secret: $secret,
                toleranceSeconds: (int) config('alphapay.webhook_tolerance_seconds', 300)
            );
        } catch (AlphaPayWebhookSignatureException $e) {
            Log::warning('AlphaPay webhook: signature invalide ou rejeu.', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $eventType = $event['event'] ?? '';
        $data = $event['data'] ?? [];

        Log::info('AlphaPay webhook reçu et vérifié.', ['event' => $eventType]);
        WebhookReceived::dispatch($eventType, $event);

        try {
            if (str_starts_with($eventType, 'transaction.')) {
                $this->handleTransaction($eventType, $data);
            } elseif (str_starts_with($eventType, 'settlement.')) {
                SettlementEvent::dispatch($eventType, $data);
            } elseif (str_starts_with($eventType, 'wallet_transfer.')) {
                WalletTransferEvent::dispatch($eventType, $data);
            } else {
                Log::info('AlphaPay webhook: type d\'événement non géré.', ['event' => $eventType]);
            }
        } catch (\Throwable $e) {
            // La signature était valide -- l'erreur vient du traitement applicatif,
            // pas du webhook lui-même. On répond 500 pour qu'AlphaPay retente
            // (RETRY_SCHEDULE_MINUTES côté API), plutôt que de faire disparaître
            // silencieusement un event mal géré.
            Log::error('AlphaPay webhook: erreur de traitement.', [
                'event' => $eventType,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Processing failed'], 500);
        }

        return response()->json(['success' => true]);
    }

    /** @param array<string, mixed> $data */
    protected function handleTransaction(string $eventType, array $data): void
    {
        if (config('alphapay.store_transactions', true)) {
            $this->storeTransaction($data);
        }

        TransactionEvent::dispatch($eventType, $data);
    }

    /** @param array<string, mixed> $data */
    protected function storeTransaction(array $data): void
    {
        $reference = $data['reference'] ?? null;
        if ($reference === null) {
            return;
        }

        AlphaPayTransaction::updateOrCreate(
            ['reference' => $reference],
            [
                'alphapay_id' => $data['id'] ?? null,
                'type' => $data['type'] ?? null,
                'status' => $data['status'] ?? null,
                'amount' => $data['amount'] ?? '0',
                'fee' => $data['fee'] ?? '0',
                'charged' => $data['charged'] ?? '0',
                'net_amount' => $data['net_amount'] ?? '0',
                'fee_charge_mode' => $data['fee_charge_mode'] ?? null,
                'currency' => $data['currency'] ?? null,
                'network' => $data['network'] ?? null,
                'country' => $data['country'] ?? null,
                'msisdn' => $data['msisdn'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ]
        );
    }
}
