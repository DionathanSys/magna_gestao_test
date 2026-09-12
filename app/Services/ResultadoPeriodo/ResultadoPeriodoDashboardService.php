<?php

namespace App\Services\ResultadoPeriodo;

use App\Models\ResultadoPeriodo;
use App\Models\ResultadoPeriodoCompartilhamento;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ResultadoPeriodoDashboardService
{
    public function recordsFor(array $ids): Collection
    {
        $ids = collect($ids)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return $this->recordsQuery()
            ->whereIn('id', $ids)
            ->orderBy('data_inicio')
            ->orderBy('veiculo_id')
            ->get();
    }

    public function recordsForPeriod(CarbonInterface|string|null $dataInicio, CarbonInterface|string|null $dataFim): Collection
    {
        if (! $dataInicio || ! $dataFim) {
            return collect();
        }

        $inicio = Carbon::parse($dataInicio)->toDateString();
        $fim = Carbon::parse($dataFim)->toDateString();

        return $this->recordsQuery()
            ->whereDate('data_inicio', '>=', $inicio)
            ->whereDate('data_inicio', '<=', $fim)
            ->orderBy('data_inicio')
            ->orderBy('veiculo_id')
            ->get();
    }

    public function recordsForShare(ResultadoPeriodoCompartilhamento $share): Collection
    {
        $ids = collect($share->resultado_periodo_ids ?? [])
            ->filter()
            ->values();

        return $ids->isNotEmpty()
            ? $this->recordsFor($ids->all())
            : $this->recordsForPeriod($share->data_inicio, $share->data_fim);
    }

    public function summarize(Collection $records, ?array $periodo = null): array
    {
        $lines = $records->map(fn (ResultadoPeriodo $record): array => $this->line($record));
        $faturamento = $lines->sum('faturamento');
        $combustivel = $lines->sum('combustivel');
        $manutencao = $lines->sum('manutencao');
        $folhaPagamento = $lines->sum('folha_pagamento');
        $litros = $lines->sum('litros');
        $kmPago = $lines->sum('km_pago');
        $kmRodadoViagens = $lines->sum('km_rodado_viagens');
        $linhasComDesperdicio = $lines->filter(fn (array $line): bool => $line['desperdicio_litros'] !== null);
        $linhasComValorDesperdicio = $lines->filter(fn (array $line): bool => $line['desperdicio_valor'] !== null);
        $litrosEstimadosMeta = $linhasComDesperdicio->sum('litros_estimados_meta');
        $desperdicioLitros = $linhasComDesperdicio->isEmpty()
            ? null
            : $linhasComDesperdicio->sum('desperdicio_litros');
        $desperdicioValor = $linhasComValorDesperdicio->isEmpty()
            ? null
            : $linhasComValorDesperdicio->sum('desperdicio_valor');
        $linhasComKmAbastecimento = $lines->filter(fn (array $line): bool => $line['km_rodado_abastecimento'] !== null);
        $kmRodadoAbastecimento = $linhasComKmAbastecimento->isEmpty()
            ? null
            : $linhasComKmAbastecimento->sum('km_rodado_abastecimento');
        $kmPagoBaseAbastecimento = $linhasComKmAbastecimento->sum('km_pago');
        $veiculos = $lines->pluck('veiculo_id')->filter()->unique()->count();
        $custoTotal = $combustivel + $manutencao + $folhaPagamento;

        return [
            'identificacao' => $this->identificacao($records, $lines, $periodo),
            'metricas' => [
                'faturamento' => $faturamento,
                'veiculos' => $veiculos,
                'faturamento_medio' => $veiculos > 0 ? $faturamento / $veiculos : null,
                'km_rodado_abastecimento' => $kmRodadoAbastecimento,
                'km_medio_abastecimento' => $linhasComKmAbastecimento->isNotEmpty()
                    ? $kmRodadoAbastecimento / $linhasComKmAbastecimento->pluck('veiculo_id')->unique()->count()
                    : null,
                'km_rodado_viagens' => $kmRodadoViagens,
                'km_medio_viagens' => $veiculos > 0 ? $kmRodadoViagens / $veiculos : null,
                'km_pago' => $kmPago,
                'dispersao_km_abastecimento' => $kmRodadoAbastecimento !== null
                    ? $kmRodadoAbastecimento - $kmPagoBaseAbastecimento
                    : null,
                'percentual_dispersao_km_abastecimento' => $kmPagoBaseAbastecimento > 0 && $kmRodadoAbastecimento !== null
                    ? (($kmRodadoAbastecimento - $kmPagoBaseAbastecimento) / $kmPagoBaseAbastecimento) * 100
                    : null,
                'dispersao_km_viagens' => $kmRodadoViagens - $kmPago,
                'percentual_dispersao_km_viagens' => $kmPago > 0
                    ? (($kmRodadoViagens - $kmPago) / $kmPago) * 100
                    : null,
                'diferenca_km_abastecimento_viagens' => $kmRodadoAbastecimento !== null
                    ? $kmRodadoAbastecimento - $kmRodadoViagens
                    : null,
                'percentual_km_abastecimento_viagens' => $kmRodadoViagens > 0 && $kmRodadoAbastecimento !== null
                    ? (($kmRodadoAbastecimento - $kmRodadoViagens) / $kmRodadoViagens) * 100
                    : null,
                'combustivel' => $combustivel,
                'percentual_combustivel_faturamento' => $this->percentual($combustivel, $faturamento),
                'custo_medio_diesel' => $litros > 0 ? $combustivel / $litros : null,
                'custo_diesel_por_km' => $kmRodadoAbastecimento > 0 ? $combustivel / $kmRodadoAbastecimento : null,
                'litros_estimados_meta' => $linhasComDesperdicio->isEmpty() ? null : $litrosEstimadosMeta,
                'desperdicio_litros' => $desperdicioLitros,
                'percentual_desperdicio_litros' => $desperdicioLitros !== null && $litrosEstimadosMeta > 0
                    ? ($desperdicioLitros / $litrosEstimadosMeta) * 100
                    : null,
                'desperdicio_valor' => $desperdicioValor,
                'manutencao' => $manutencao,
                'percentual_manutencao_faturamento' => $this->percentual($manutencao, $faturamento),
                'custo_medio_veiculo' => $veiculos > 0 ? $custoTotal / $veiculos : null,
                'salario' => $folhaPagamento,
                'percentual_salario_faturamento' => $this->percentual($folhaPagamento, $faturamento),
                'litros' => $litros,
                'custo_total' => $custoTotal,
            ],
            'linhas' => $lines,
            'agrupado_por_tipo' => $this->agrupadoPorTipo($lines),
            'observacoes' => [
                'total_resultados' => $records->count(),
                'veiculos_com_km_abastecimento' => $linhasComKmAbastecimento->pluck('veiculo_id')->unique()->count(),
                'veiculos_com_calculo_desperdicio' => $linhasComDesperdicio->pluck('veiculo_id')->unique()->count(),
                'viagens' => $lines->sum('viagens_count'),
                'abastecimentos' => $lines->sum('abastecimentos_count'),
                'documentos' => $lines->sum('documentos_count'),
                'status' => $records->pluck('status')->filter()->unique()->values(),
            ],
        ];
    }

    private function recordsQuery(): Builder
    {
        return ResultadoPeriodo::query()
            ->with([
                'veiculo:id,placa,tipo_veiculo_id',
                'veiculo.tipoVeiculo:id,descricao,meta_media',
                'abastecimentoInicial',
                'abastecimentoFinal',
            ])
            ->withCount(['viagens', 'documentos', 'abastecimentos'])
            ->withSum('documentos', 'valor_liquido')
            ->withSum('abastecimentos', 'preco_total')
            ->withSum('abastecimentos', 'quantidade')
            ->withSum('viagens', 'km_pago')
            ->withSum('viagens', 'km_rodado')
            ->withSum('manutencaoLancamentos', 'valor_total_centavos');
    }

    private function agrupadoPorTipo(Collection $lines): Collection
    {
        return $lines
            ->groupBy(fn (array $line): string => $line['tipo_veiculo'] ?: 'Tipo não informado')
            ->map(function (Collection $tipoLines, string $tipo): array {
                $veiculos = $tipoLines->pluck('veiculo_id')->filter()->unique()->count();
                $kmRodado = $tipoLines->sum('km_rodado_viagens');
                $kmPago = $tipoLines->sum('km_pago');
                $faturamento = $tipoLines->sum('faturamento');

                return [
                    'tipo' => $tipo,
                    'veiculos' => $veiculos,
                    'km_rodado' => $kmRodado,
                    'km_medio' => $veiculos > 0 ? $kmRodado / $veiculos : null,
                    'km_pago' => $kmPago,
                    'faturamento' => $faturamento,
                    'faturamento_medio' => $veiculos > 0 ? $faturamento / $veiculos : null,
                    'faturamento_por_km' => $kmRodado > 0 ? $faturamento / $kmRodado : null,
                ];
            })
            ->sortByDesc('faturamento')
            ->values();
    }

    private function line(ResultadoPeriodo $record): array
    {
        $kmPago = (float) ($record->viagens_sum_km_pago ?? 0);
        $kmRodadoViagens = (float) ($record->viagens_sum_km_rodado ?? 0);
        $kmRodadoAbastecimento = $record->km_rodado_abastecimento === null
            ? null
            : (float) $record->km_rodado_abastecimento;
        $litros = (float) ($record->abastecimentos_sum_quantidade ?? 0);
        $litrosEstimadosMeta = $record->litros_estimados_meta;
        $desperdicioLitros = $record->desperdicio_litros;

        return [
            'veiculo_id' => (int) $record->veiculo_id,
            'placa' => $record->veiculo?->placa ?? 'N/D',
            'tipo_veiculo' => $record->veiculo?->tipoVeiculo?->descricao,
            'faturamento' => $this->reais($record->documentos_sum_valor_liquido),
            'combustivel' => $this->reais($record->abastecimentos_sum_preco_total),
            'manutencao' => $this->reais($record->manutencao_lancamentos_sum_valor_total_centavos),
            'folha_pagamento' => $this->reais($record->getRawOriginal('folha_pagamento_centavos')),
            'litros' => $litros,
            'preco_medio_combustivel' => (float) $record->preco_medio_combustivel,
            'meta_consumo' => $record->meta_consumo,
            'litros_estimados_meta' => $litrosEstimadosMeta,
            'desperdicio_litros' => $desperdicioLitros,
            'desperdicio_valor' => $record->desperdicio_valor,
            'km_pago' => $kmPago,
            'km_rodado_viagens' => $kmRodadoViagens,
            'km_rodado_abastecimento' => $kmRodadoAbastecimento,
            'dispersao_km_abastecimento' => $kmRodadoAbastecimento === null ? null : $kmRodadoAbastecimento - $kmPago,
            'percentual_dispersao_km_abastecimento' => $kmPago > 0 && $kmRodadoAbastecimento !== null
                ? (($kmRodadoAbastecimento - $kmPago) / $kmPago) * 100
                : null,
            'dispersao_km_viagens' => $kmRodadoViagens - $kmPago,
            'percentual_dispersao_km_viagens' => $kmPago > 0
                ? (($kmRodadoViagens - $kmPago) / $kmPago) * 100
                : null,
            'viagens_count' => (int) ($record->viagens_count ?? 0),
            'abastecimentos_count' => (int) ($record->abastecimentos_count ?? 0),
            'documentos_count' => (int) ($record->documentos_count ?? 0),
            'data_inicio' => $record->data_inicio,
            'data_fim' => $record->data_fim,
        ];
    }

    private function identificacao(Collection $records, Collection $lines, ?array $periodo = null): array
    {
        $inicio = $periodo && ($periodo['inicio'] ?? null)
            ? Carbon::parse($periodo['inicio'])
            : $records
                ->pluck('data_inicio')
                ->filter()
                ->map(fn ($date): Carbon => Carbon::parse($date))
                ->sortBy(fn (Carbon $date): int => $date->timestamp)
                ->first();
        $fim = $periodo && ($periodo['fim'] ?? null)
            ? Carbon::parse($periodo['fim'])
            : $records
                ->pluck('data_fim')
                ->filter()
                ->map(fn ($date): Carbon => Carbon::parse($date))
                ->sortByDesc(fn (Carbon $date): int => $date->timestamp)
                ->first();

        return [
            'inicio' => $inicio,
            'fim' => $fim,
            'periodo' => $inicio && $fim
                ? $inicio->format('d/m/Y').' a '.$fim->format('d/m/Y')
                : 'Período não informado',
            'veiculos' => $lines->pluck('placa')->unique()->sort()->values(),
        ];
    }

    private function reais(mixed $centavos): float
    {
        return (float) ($centavos ?? 0) / 100;
    }

    private function percentual(float $valor, float $base): ?float
    {
        return $base > 0 ? ($valor / $base) * 100 : null;
    }
}
