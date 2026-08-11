<?php

namespace Tests\Feature;

use App\Services\WebScraper\SascarMovimentoDiarioCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SascarMovimentoDiarioApiTest extends TestCase
{
    public function test_aceita_movimento_diario_valido_e_registra_em_cache(): void
    {
        config(['cache.default' => 'array']);
        Cache::flush();
        config(['services.webscraper.secret' => 'test-secret']);

        $body = json_encode($this->payloadMovimento(), JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $signature = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, 'test-secret');

        $response = $this->call('POST', '/api/integracoes/movimento-diario', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_WEBHOOK_TIMESTAMP' => $timestamp,
            'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
            'HTTP_X_REQUEST_ID' => 'request-movimento-diario-001',
        ], $body);

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('lote_id', 'sascar-movimento-diario-20260811-001')
            ->assertJsonPath('veiculo_key', 'placa:RLE7C85');

        $data = app(SascarMovimentoDiarioCache::class)->get('placa:RLE7C85', '2026-08-11');

        $this->assertSame('sascar-movimento-diario-20260811-001', $data['lote_id']);
        $this->assertSame('RLE7C85', $data['placa_normalizada']);
        $this->assertSame(213.7, $data['km']);
        $this->assertSame('10:06:59', $data['tempo_movimento']);
        $this->assertCount(24, $data['horas']);
    }

    public function test_rejeita_movimento_diario_com_horas_incompletas(): void
    {
        config(['services.webscraper.secret' => 'test-secret']);

        $payload = $this->payloadMovimento();
        $payload['horas'] = array_slice($payload['horas'], 0, 23);

        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $signature = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, 'test-secret');

        $response = $this->call('POST', '/api/integracoes/movimento-diario', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_WEBHOOK_TIMESTAMP' => $timestamp,
            'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
        ], $body);

        $response->assertStatus(422);
    }

    private function payloadMovimento(): array
    {
        return [
            'lote_id' => 'sascar-movimento-diario-20260811-001',
            'veiculo' => 'RLE7C85',
            'filial' => 'MAGNABOSCO - CHAPECO',
            'dia' => '2026-08-11',
            'km' => 213.7,
            'tempo_movimento' => '10:06:59',
            'horas' => collect(range(0, 23))
                ->map(fn (int $hora): array => [
                    'hora' => $hora,
                    'minutos' => ['0', '0', '1', '1', '2', '2'],
                ])
                ->all(),
        ];
    }
}
