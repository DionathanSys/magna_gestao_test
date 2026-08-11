<?php

namespace Tests\Feature;

use App\Services\WebScraper\WebScraperViagemAtualCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WebScraperViagemAtualApiTest extends TestCase
{
    public function test_registra_viagem_atual_em_cache_com_assinatura_valida(): void
    {
        config(['cache.default' => 'array']);
        Cache::flush();
        config(['services.webscraper.secret' => 'test-secret']);

        $body = json_encode([
            'veiculo' => 'ABC1D23',
            'nro_viagem' => 'EXT-12345',
            'destino' => 'Chapeco/SC',
            'km_pago' => 118,
            'inicio' => '2026-08-11 08:00:00',
            'status' => 'em_rota',
        ], JSON_THROW_ON_ERROR);

        $timestamp = (string) time();
        $signature = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, 'test-secret');

        $response = $this->call('POST', '/api/integracoes/viagem-atual', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_WEBHOOK_TIMESTAMP' => $timestamp,
            'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
            'HTTP_X_REQUEST_ID' => 'request-viagem-atual-001',
        ], $body);

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('veiculo_key', 'placa:ABC1D23');

        $data = app(WebScraperViagemAtualCache::class)->get('placa:ABC1D23');

        $this->assertSame('EXT-12345', $data['numero_viagem']);
        $this->assertSame('Chapeco/SC', $data['destino']);
        $this->assertSame(118.0, $data['km_pago']);
        $this->assertSame('em_rota', $data['status']);
    }

    public function test_rejeita_payload_de_viagem_atual_incompleto(): void
    {
        config(['services.webscraper.secret' => 'test-secret']);

        $body = json_encode([
            'veiculo' => 'ABC1D23',
        ], JSON_THROW_ON_ERROR);

        $timestamp = (string) time();
        $signature = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, 'test-secret');

        $response = $this->call('POST', '/api/integracoes/viagem-atual', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_WEBHOOK_TIMESTAMP' => $timestamp,
            'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
        ], $body);

        $response->assertStatus(422);
    }
}
