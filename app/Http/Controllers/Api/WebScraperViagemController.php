<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessarWebScraperViagensJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WebScraperViagemController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();
        $requestId = (string) ($request->header('X-Request-Id') ?: Str::uuid());

        $validator = Validator::make($payload, [
            'lote_id' => 'nullable|string|max:120',
            'viagem' => 'required_without:viagens|array',
            'viagens' => 'required_without:viagem|array|min:1|max:500',
        ]);

        if ($validator->fails()) {
            Log::warning('Payload WebScraper rejeitado na validacao inicial', [
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

        $viagens = array_key_exists('viagens', $payload)
            ? array_values($payload['viagens'])
            : [$payload['viagem']];

        $itemsValidator = Validator::make(['viagens' => $viagens], [
            'viagens.*.numero_viagem' => 'required|string|max:255',
            'viagens.*.placa' => 'required_without:viagens.*.veiculo_id|string|max:20',
            'viagens.*.veiculo_id' => 'required_without:viagens.*.placa|integer|exists:veiculos,id',
            'viagens.*.unidade_negocio' => 'required|string|max:255',
            'viagens.*.cliente' => 'nullable|string|max:255',
            'viagens.*.numero_interno' => 'nullable|string|max:255',
            'viagens.*.documento_transporte' => 'nullable|string|max:255',
            'viagens.*.km_rodado' => 'nullable|numeric|min:0',
            'viagens.*.km_pago' => 'nullable|numeric|min:0',
            'viagens.*.data_competencia' => 'required|date',
            'viagens.*.data_inicio' => 'required|date',
            'viagens.*.data_fim' => 'required|date',
            'viagens.*.total_destinos' => 'nullable|integer|min:0',
            'viagens.*.conferido' => 'nullable|boolean',
            'viagens.*.ignorar' => 'nullable|boolean',
            'viagens.*.possui_pendencia' => 'nullable|boolean',
            'viagens.*.pendencias' => 'nullable|array',
            'viagens.*.motorista1' => 'nullable|string|max:255',
            'viagens.*.motorista2' => 'nullable|string|max:255',
            'viagens.*.destino' => 'nullable|string|max:255',
        ]);

        if ($itemsValidator->fails()) {
            Log::warning('Payload WebScraper rejeitado na validacao dos itens', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'request_id' => $requestId,
                'lote_id' => $payload['lote_id'] ?? null,
                'errors' => $itemsValidator->errors()->toArray(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payload invalido.',
                'errors' => $itemsValidator->errors(),
            ], 422);
        }

        $loteId = (string) ($payload['lote_id'] ?? 'webscraper-'.$requestId);

        ProcessarWebScraperViagensJob::dispatch($loteId, $viagens, $requestId)
            ->onQueue('integracoes');

        Log::info('Payload WebScraper aceito e job enfileirado', [
            'metodo' => __METHOD__.'@'.__LINE__,
            'request_id' => $requestId,
            'lote_id' => $loteId,
            'total_viagens' => count($viagens),
            'queue' => 'integracoes',
            'numeros_viagem' => collect($viagens)
                ->pluck('numero_viagem')
                ->filter()
                ->take(20)
                ->values()
                ->all(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payload recebido e enfileirado para processamento.',
            'request_id' => $requestId,
            'lote_id' => $loteId,
            'total_viagens' => count($viagens),
        ]);
    }
}
