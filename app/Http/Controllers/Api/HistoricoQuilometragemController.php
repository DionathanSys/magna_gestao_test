<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\RegistrarQuilometragem;
use App\Models\HistoricoQuilometragem;
use App\Models\Veiculo;
use App\Services\MailInbound\Support\DocumentIdentity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class HistoricoQuilometragemController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $requestId = (string) ($request->header('X-Request-Id') ?: Str::uuid());
        $payload = $request->only(['lote_id', 'registros']);
        $validator = Validator::make($payload, [
            'lote_id' => 'nullable|string|max:120',
            'registros' => 'required|array|min:1|max:500',
            'registros.*.placa' => 'required|string|max:20',
            'registros.*.data_referencia' => 'required|date',
            'registros.*.quilometragem' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Payload invalido.',
                'errors' => $validator->errors(),
                'request_id' => $requestId,
            ], 422);
        }

        $erros = [];
        $registros = collect($payload['registros'])
            ->map(function (array $registro, int $index) use (&$erros): array {
                $placa = DocumentIdentity::normalizePlate($registro['placa']);
                $veiculo = $placa === null ? null : Veiculo::query()
                    ->where('placa', trim($registro['placa']))
                    ->orWhere('placa', $placa)
                    ->first();

                if ($placa === null) {
                    $erros["registros.$index.placa"][] = 'A placa deve ser valida.';
                } elseif ($veiculo === null) {
                    $erros["registros.$index.placa"][] = 'O veiculo informado nao existe.';
                }

                return [
                    'index' => $index,
                    'veiculo_id' => $veiculo?->id,
                    'placa' => $veiculo?->placa,
                    'data_referencia' => $registro['data_referencia'],
                    'quilometragem' => $registro['quilometragem'],
                ];
            })
            ->sortBy(['data_referencia', 'index'])
            ->values();

        $ultimosKms = [];
        foreach ($registros as $registro) {
            if ($registro['veiculo_id'] === null) {
                continue;
            }

            $veiculoId = $registro['veiculo_id'];
            $ultimoKm = $ultimosKms[$veiculoId] ?? HistoricoQuilometragem::query()
                ->where('veiculo_id', $veiculoId)
                ->whereDate('data_referencia', '<=', $registro['data_referencia'])
                ->orderByDesc('data_referencia')
                ->orderByDesc('id')
                ->value('quilometragem');

            if ($ultimoKm !== null && $registro['quilometragem'] < $ultimoKm) {
                $erros["registros.{$registro['index']}.quilometragem"][] = 'A quilometragem nao pode ser menor que a ultima registrada ate a data de referencia.';
            }

            $ultimosKms[$veiculoId] = $registro['quilometragem'];
        }

        if ($erros !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Payload invalido.',
                'errors' => $erros,
                'request_id' => $requestId,
            ], 422);
        }

        $loteId = (string) ($payload['lote_id'] ?? 'historico-quilometragem-'.$requestId);
        Bus::chain(
            $registros
                ->map(fn (array $registro) => new RegistrarQuilometragem($registro, $loteId, $requestId))
                ->all()
        )->onQueue('integracoes')->dispatch();

        return response()->json([
            'success' => true,
            'message' => 'Lote recebido e enfileirado para processamento.',
            'request_id' => $requestId,
            'lote_id' => $loteId,
            'total_registros' => $registros->count(),
        ]);
    }
}
