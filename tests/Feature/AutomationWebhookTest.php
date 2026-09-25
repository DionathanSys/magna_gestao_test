<?php

namespace Tests\Feature;

use App\Jobs\Automation\ProcessAutomationEvent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AutomationWebhookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'automation.webhook.client_id' => 'automation_prod',
            'automation.webhook.secret' => 'webhook-secret',
            'automation.webhook.previous_secret' => null,
            'automation.signature.nonce_cache_store' => 'array',
        ]);

        Queue::fake();

        Schema::dropIfExists('automation_events');
        Schema::create('automation_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('provider_job_id')->index();
            $table->string('client_id');
            $table->string('event_type');
            $table->json('payload');
            $table->timestamp('occurred_at');
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->string('processing_status')->default('RECEIVED');
            $table->text('processing_error')->nullable();
            $table->string('request_id')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('automation_events');
        Cache::store('array')->flush();

        parent::tearDown();
    }

    public function test_accepts_a_signed_event_and_dispatches_processing(): void
    {
        $response = $this->sendWebhook('event-001', 'nonce-001');

        $response->assertAccepted()->assertJson([
            'received' => true,
            'event_id' => 'event-001',
        ]);

        Queue::assertPushed(ProcessAutomationEvent::class);
        $this->assertDatabaseHas('automation_events', [
            'event_id' => 'event-001',
            'provider_job_id' => 'provider-job-001',
        ]);
    }

    public function test_rejects_reusing_a_nonce(): void
    {
        $this->sendWebhook('event-001', 'nonce-001')->assertAccepted();

        $this->sendWebhook('event-002', 'nonce-001')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'REPLAY_DETECTED');
    }

    public function test_returns_success_for_a_duplicate_event_id_with_a_new_nonce(): void
    {
        $this->sendWebhook('event-001', 'nonce-001')->assertAccepted();

        $this->sendWebhook('event-001', 'nonce-002')
            ->assertOk()
            ->assertJson([
                'received' => true,
                'duplicate' => true,
                'event_id' => 'event-001',
            ]);

        Queue::assertPushed(ProcessAutomationEvent::class, 1);
    }

    private function sendWebhook(string $eventId, string $nonce)
    {
        $body = json_encode([
            'event_id' => $eventId,
            'event' => 'job.completed',
            'occurred_at' => '2026-09-20T12:00:00Z',
            'job' => [
                'id' => 'provider-job-001',
                'status' => 'COMPLETED',
            ],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $timestamp = (string) time();
        $path = '/api/integrations/automation/v1/webhooks';
        $canonical = implode("\n", [
            'POST',
            $path,
            $timestamp,
            $nonce,
            hash('sha256', $body),
        ]);

        return $this->call('POST', $path, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CLIENT_ID' => 'automation_prod',
            'HTTP_X_TIMESTAMP' => $timestamp,
            'HTTP_X_NONCE' => $nonce,
            'HTTP_X_SIGNATURE' => hash_hmac('sha256', $canonical, 'webhook-secret'),
            'HTTP_X_SIGNATURE_VERSION' => 'v1',
            'HTTP_X_REQUEST_ID' => 'request-'.$eventId,
        ], $body);
    }
}
