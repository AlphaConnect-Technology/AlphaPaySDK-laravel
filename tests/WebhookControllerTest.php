<?php

declare(strict_types=1);

namespace AlphaPay\Laravel\Tests;

use AlphaPay\Laravel\Events\TransactionEvent;
use AlphaPay\Laravel\Events\WebhookReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_test_secret';

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /** Reproduit exactement apps.webhooks.services.sign_payload côté AlphaPayBack. */
    private function sign(string $body, int $timestamp): string
    {
        return hash_hmac('sha256', "{$timestamp}.{$body}", self::SECRET);
    }

    private function postWebhook(string $body, string $signature, string $timestamp)
    {
        return $this->call(
            'POST',
            '/' . config('alphapay.webhook_path'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_Webhook-Signature' => $signature,
                'HTTP_X_Webhook-Timestamp' => $timestamp,
            ],
            $body
        );
    }

    public function test_valid_transaction_webhook_is_accepted_stored_and_dispatched(): void
    {
        Event::fake([TransactionEvent::class, WebhookReceived::class]);

        $body = json_encode([
            'event' => 'transaction.success',
            'data' => [
                'id' => 'a1b2c3d4-0000-0000-0000-000000000000',
                'reference' => 'TX-TEST-001',
                'type' => 'PAIEMENT',
                'status' => 'SUCCESS',
                'amount' => '5000.00',
                'fee' => '50.00',
                'charged' => '5000.00',
                'net_amount' => '4950.00',
                'fee_charge_mode' => 'MERCHANT',
                'currency' => 'XOF',
                'network' => 'mtn_bj',
                'country' => 'BJ',
                'msisdn' => '+22900000000',
                'metadata' => ['order_id' => 42],
            ],
        ], JSON_THROW_ON_ERROR);
        $timestamp = time();

        $response = $this->postWebhook($body, $this->sign($body, $timestamp), (string) $timestamp);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        Event::assertDispatched(WebhookReceived::class);
        Event::assertDispatched(TransactionEvent::class, function (TransactionEvent $event) {
            return $event->isSuccess() && $event->getReference() === 'TX-TEST-001';
        });

        $this->assertDatabaseHas(config('alphapay.transactions_table'), [
            'reference' => 'TX-TEST-001',
            'status' => 'SUCCESS',
            'network' => 'mtn_bj',
        ]);
    }

    public function test_webhook_with_invalid_signature_is_rejected(): void
    {
        Event::fake();

        $body = json_encode(['event' => 'transaction.success', 'data' => ['reference' => 'TX-BAD']]);
        $timestamp = time();

        $response = $this->postWebhook($body, 'signature-invalide', (string) $timestamp);

        $response->assertStatus(400);
        Event::assertNotDispatched(TransactionEvent::class);
        $this->assertDatabaseMissing(config('alphapay.transactions_table'), ['reference' => 'TX-BAD']);
    }

    public function test_webhook_with_stale_timestamp_is_rejected_as_replay(): void
    {
        Event::fake();

        $body = json_encode(['event' => 'transaction.success', 'data' => ['reference' => 'TX-OLD']]);
        $staleTimestamp = time() - 3600; // hors fenêtre de tolérance (300s par défaut)

        $response = $this->postWebhook($body, $this->sign($body, $staleTimestamp), (string) $staleTimestamp);

        $response->assertStatus(400);
        Event::assertNotDispatched(TransactionEvent::class);
    }

    public function test_settlement_webhook_does_not_touch_the_transactions_table(): void
    {
        $body = json_encode([
            'event' => 'settlement.success',
            'data' => ['id' => 'x', 'reference' => 'STL-1', 'status' => 'SUCCESS', 'amount' => '1000.00', 'currency' => 'XOF'],
        ]);
        $timestamp = time();

        $response = $this->postWebhook($body, $this->sign($body, $timestamp), (string) $timestamp);

        $response->assertOk();
        $this->assertDatabaseMissing(config('alphapay.transactions_table'), ['reference' => 'STL-1']);
    }
}
