<?php

namespace Tests\Feature;

use App\Jobs\ProcessarWebScraperViagensJob;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebScraperViagensApiTest extends TestCase
{
    public function test_rejeita_requisicao_sem_assinatura_valida(): void
    {
        config(['services.webscraper.secret' => 'test-secret']);

        $response = $this->postJson('/api/integracoes/viagens', [
            'viagem' => $this->payloadViagem(),
        ]);

        $response->assertStatus(401);
    }

    public function test_aceita_payload_valido_e_enfileira_job(): void
    {
        Queue::fake();
        config(['services.webscraper.secret' => 'test-secret']);

        $body = json_encode([
            'lote_id' => 'teste-lote-001',
            'viagem' => $this->payloadViagem(),
        ], JSON_THROW_ON_ERROR);

        $timestamp = (string) time();
        $signature = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, 'test-secret');

        $response = $this->call('POST', '/api/integracoes/viagens', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_WEBHOOK_TIMESTAMP' => $timestamp,
            'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
            'HTTP_X_REQUEST_ID' => 'request-teste-001',
        ], $body);

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('lote_id', 'teste-lote-001')
            ->assertJsonPath('total_viagens', 1);

        Queue::assertPushed(ProcessarWebScraperViagensJob::class);
    }

    private function payloadViagem(): array
    {
        return [
            'numero_viagem' => 'EXT-12345',
            'placa' => 'ABC1D23',
            'unidade_negocio' => 'Bugio',
            'data_competencia' => '2026-08-11',
            'data_inicio' => '2026-08-11 08:00:00',
            'data_fim' => '2026-08-11 12:30:00',
        ];
    }
}
