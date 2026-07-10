<?php

namespace App\Services\Manutencao;

use App\Models\ManutencaoCusto;
use App\Models\ManutencaoLancamento;
use App\Models\ResultadoPeriodo;
use App\Traits\ServiceResponseTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ManutencaoImportSyncService
{
    use ServiceResponseTrait;

    public function __construct(
        private readonly ManutencaoLancamentoVinculoService $vinculoService,
    ) {}

    public function upsert(array $data, int $importLogId): ?ManutencaoLancamento
    {
        try {
            $lancamento = ManutencaoLancamento::withTrashed()->updateOrCreate(
                ['sync_key' => $data['sync_key']],
                array_merge($data, [
                    'import_log_id' => $importLogId,
                    'deleted_at' => null,
                ])
            );

            $this->vinculoService->conciliarAutomaticamente($lancamento);

            $this->setSuccess('Lançamento de manutenção sincronizado com sucesso.');

            return $lancamento;
        } catch (\Throwable $e) {
            Log::error(__METHOD__, [
                'error' => $e->getMessage(),
                'data' => $data,
                'import_log_id' => $importLogId,
            ]);

            $this->setError('Falha ao sincronizar lançamento de manutenção.', [$e->getMessage()]);

            return null;
        }
    }

    public function finalizeImport(int $importLogId): void
    {
        $scopeQuery = ManutencaoLancamento::query()->where('import_log_id', $importLogId);

        $minDate = $scopeQuery->min('data_negociacao');
        $maxDate = $scopeQuery->max('data_negociacao');

        if (! $minDate || ! $maxDate) {
            return;
        }

        $currentSyncKeys = ManutencaoLancamento::query()
            ->where('import_log_id', $importLogId)
            ->pluck('sync_key');

        ManutencaoLancamento::query()
            ->whereBetween('data_negociacao', [$minDate, $maxDate])
            ->whereNotIn('sync_key', $currentSyncKeys)
            ->delete();

        $this->recalculateAggregates(Carbon::parse($minDate), Carbon::parse($maxDate));
    }

    public function recalculateAggregates(Carbon $periodStart, Carbon $periodEnd): void
    {
        $resultadoPeriodos = ResultadoPeriodo::query()
            ->whereDate('data_inicio', '<=', $periodEnd->toDateString())
            ->whereDate('data_fim', '>=', $periodStart->toDateString())
            ->get();

        foreach ($resultadoPeriodos as $resultadoPeriodo) {
            $totalCentavos = ManutencaoLancamento::query()
                ->where('veiculo_id', $resultadoPeriodo->veiculo_id)
                ->whereBetween('data_negociacao', [$resultadoPeriodo->data_inicio, $resultadoPeriodo->data_fim])
                ->sum('valor_total_centavos');

            if ($totalCentavos <= 0) {
                ManutencaoCusto::query()
                    ->where('resultado_periodo_id', $resultadoPeriodo->id)
                    ->delete();

                continue;
            }

            ManutencaoCusto::query()->updateOrCreate(
                ['resultado_periodo_id' => $resultadoPeriodo->id],
                [
                    'veiculo_id' => $resultadoPeriodo->veiculo_id,
                    'data_inicio' => $resultadoPeriodo->data_inicio,
                    'data_fim' => $resultadoPeriodo->data_fim,
                    'custo_total' => round($totalCentavos / 100, 2),
                ]
            );
        }
    }
}
