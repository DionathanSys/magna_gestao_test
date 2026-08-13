<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Veiculo;
use App\Services\MailInbound\Support\DocumentIdentity;
use App\Services\WebScraper\WebScraperViagemAtualCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WebScraperViagemAtualController extends Controller
{
    public function __invoke(Request $request, WebScraperViagemAtualCache $cache): JsonResponse
    {
        $payload = $request->all();
        $requestId = (string) ($request->header('X-Request-Id') ?: Str::uuid());

        $validator = Validator::make($payload, [
            'veiculo' => 'required_without_all:placa,veiculo_id|string|max:20',
            'placa' => 'required_without_all:veiculo,veiculo_id|string|max:20',
            'veiculo_id' => 'required_without_all:veiculo,placa|integer',
            'numero_viagem' => 'required_without:nro_viagem|string|max:255',
            'nro_viagem' => 'required_without:numero_viagem|string|max:255',
            'destino' => 'required|string|max:255',
            'local_atual' => 'nullable|string|max:255',
            'peso' => 'nullable|numeric|min:0',
            'km_pago' => 'required|numeric|min:0',
            'inicio' => 'required|date',
            'status' => 'required|string|max:80',
        ]);

        if ($validator->fails()) {
            Log::warning('Payload de viagem atual WebScraper rejeitado', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'request_id' => $requestId,
                'errors' => $validator->errors()->toArray(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payload invalido.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $veiculo = $this->resolverVeiculo($payload);
        $veiculoKey = $this->resolverVeiculoKey($payload, $veiculo);

        $data = [
            'request_id' => $requestId,
            'veiculo_key' => $veiculoKey,
            'veiculo_id' => $veiculo?->id ?? ($payload['veiculo_id'] ?? null),
            'veiculo' => $veiculo?->placa ?? ($payload['placa'] ?? $payload['veiculo'] ?? null),
            'placa_normalizada' => DocumentIdentity::normalizePlate($payload['placa'] ?? $payload['veiculo'] ?? $veiculo?->placa),
            'numero_viagem' => (string) ($payload['numero_viagem'] ?? $payload['nro_viagem']),
            'destino' => (string) $payload['destino'],
            'local_atual' => filled($payload['local_atual'] ?? null) ? trim((string) $payload['local_atual']) : null,
            'peso' => isset($payload['peso']) ? (float) $payload['peso'] : null,
            'km_pago' => (float) $payload['km_pago'],
            'inicio' => (string) $payload['inicio'],
            'status' => trim((string) $payload['status']),
            'recebido_em' => now()->toDateTimeString(),
        ];

        $cache->put($veiculoKey, $data);

        Log::info('Viagem atual WebScraper registrada em cache', [
            'metodo' => __METHOD__.'@'.__LINE__,
            'request_id' => $requestId,
            'veiculo_key' => $veiculoKey,
            'veiculo_id' => $data['veiculo_id'],
            'veiculo' => $data['veiculo'],
            'numero_viagem' => $data['numero_viagem'],
            'destino' => $data['destino'],
            'local_atual' => $data['local_atual'],
            'peso' => $data['peso'],
            'status' => $data['status'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Viagem atual registrada.',
            'request_id' => $requestId,
            'veiculo_key' => $veiculoKey,
        ]);
    }

    private function resolverVeiculo(array $payload): ?Veiculo
    {
        try {
            if (! empty($payload['veiculo_id'])) {
                return Veiculo::query()
                    ->select('id', 'placa')
                    ->where('id', $payload['veiculo_id'])
                    ->first();
            }

            $placa = DocumentIdentity::normalizePlate($payload['placa'] ?? $payload['veiculo'] ?? null);

            if ($placa === null) {
                return null;
            }

            $veiculo = Veiculo::query()
                ->select('id', 'placa')
                ->where(function ($query) use ($payload, $placa): void {
                    $query->where('placa', trim((string) ($payload['placa'] ?? $payload['veiculo'] ?? '')))
                        ->orWhere('placa', $placa);
                })
                ->first();

            if (! $veiculo) {
                Log::warning('Viagem atual recebida para veiculo nao cadastrado', [
                    'metodo' => __METHOD__.'@'.__LINE__,
                    'placa' => $placa,
                ]);
            }

            return $veiculo;
        } catch (\Throwable $exception) {
            Log::warning('Nao foi possivel resolver veiculo da viagem atual; registro seguira por identificador externo', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'veiculo' => $payload['veiculo'] ?? null,
                'placa' => $payload['placa'] ?? null,
                'veiculo_id' => $payload['veiculo_id'] ?? null,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function resolverVeiculoKey(array $payload, ?Veiculo $veiculo): string
    {
        if ($veiculo) {
            return 'id:'.$veiculo->id;
        }

        if (! empty($payload['veiculo_id'])) {
            return 'id:'.$payload['veiculo_id'];
        }

        return 'placa:'.DocumentIdentity::normalizePlate($payload['placa'] ?? $payload['veiculo'] ?? '');
    }
}
