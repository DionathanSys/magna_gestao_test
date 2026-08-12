<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MailInbound\Support\DocumentIdentity;
use App\Services\WebScraper\SascarMovimentoDiarioCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SascarMovimentoDiarioController extends Controller
{
    public function __invoke(Request $request, SascarMovimentoDiarioCache $cache): JsonResponse
    {
        $payload = $request->all();
        $requestId = (string) ($request->header('X-Request-Id') ?: Str::uuid());

        $validator = Validator::make($payload, [
            'lote_id' => 'required|string|max:120',
            'veiculo' => 'required_without:placa|string|max:20',
            'placa' => 'required_without:veiculo|string|max:20',
            'veiculo_id' => 'nullable|string|max:255',
            'filial' => 'required|string|max:255',
            'dia' => 'required_without:data|date_format:Y-m-d',
            'data' => 'required_without:dia|date_format:Y-m-d',
            'km' => 'required|numeric|min:0',
            'tempo_movimento' => 'required_without:tempo|date_format:H:i:s',
            'tempo' => 'required_without:tempo_movimento|date_format:H:i:s',
            'horas' => 'required|array|size:24',
            'horas.*.hora' => 'required|integer|min:0|max:23',
            'horas.*.minutos' => 'required|array|size:6',
            'horas.*.minutos.*' => 'required|string|in:0,1,2',
        ]);

        $validator->after(function ($validator) use ($payload): void {
            $horas = collect($payload['horas'] ?? [])->pluck('hora')->sort()->values()->all();

            if ($horas !== range(0, 23)) {
                $validator->errors()->add('horas', 'As horas devem conter exatamente uma entrada para cada hora de 0 a 23.');
            }

            if (DocumentIdentity::normalizePlate($payload['veiculo'] ?? $payload['placa'] ?? null) === null) {
                $validator->errors()->add('veiculo', 'A placa do veiculo e invalida.');
            }
        });

        if ($validator->fails()) {
            Log::warning('Payload de movimento diario Sascar rejeitado', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'request_id' => $requestId,
                'lote_id' => $payload['lote_id'] ?? null,
                'errors' => $validator->errors()->toArray(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payload invalido.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $loteId = (string) $payload['lote_id'];
        $veiculoKey = 'placa:'.DocumentIdentity::normalizePlate($payload['veiculo'] ?? $payload['placa']);
        $dia = (string) ($payload['dia'] ?? $payload['data']);

        $cache->put($veiculoKey, $dia, [
            'request_id' => $requestId,
            'lote_id' => $loteId,
            'veiculo_key' => $veiculoKey,
            'veiculo' => (string) ($payload['veiculo'] ?? $payload['placa']),
            'placa_normalizada' => DocumentIdentity::normalizePlate($payload['veiculo'] ?? $payload['placa']),
            'veiculo_id' => $payload['veiculo_id'] ?? null,
            'filial' => (string) $payload['filial'],
            'dia' => $dia,
            'km' => (float) $payload['km'],
            'tempo_movimento' => (string) ($payload['tempo_movimento'] ?? $payload['tempo']),
            'horas' => array_values($payload['horas']),
            'recebido_em' => now()->toDateTimeString(),
        ]);

        $horas = collect($payload['horas'])->sortBy('hora')->values();
        $statusResumo = $horas
            ->flatMap(fn (array $hora): array => $hora['minutos'] ?? [])
            ->countBy(fn (mixed $status): string => (string) $status)
            ->sortKeys()
            ->toArray();

        Log::info('Movimento diario Sascar registrado em cache', [
            'metodo' => __METHOD__.'@'.__LINE__,
            'request_id' => $requestId,
            'lote_id' => $loteId,
            'veiculo_key' => $veiculoKey,
            'dia' => $dia,
            'km' => (float) $payload['km'],
            'tempo_movimento' => (string) ($payload['tempo_movimento'] ?? $payload['tempo']),
            'status_resumo' => $statusResumo,
            'total_blocos' => array_sum($statusResumo),
            'primeira_hora' => $horas->first(),
            'ultima_hora' => $horas->last(),
            'horas' => $horas->toArray(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Movimento diario recebido e registrado em cache.',
            'request_id' => $requestId,
            'lote_id' => $loteId,
            'veiculo_key' => $veiculoKey,
        ]);
    }
}
