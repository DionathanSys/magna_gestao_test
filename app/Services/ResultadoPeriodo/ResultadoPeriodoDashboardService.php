<?php

namespace App\Services\ResultadoPeriodo;

use App\Models\ResultadoPeriodo;
use Carbon\Carbon;
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

        return ResultadoPeriodo::query()
            ->whereIn('id', $ids)
            ->with([
                'veiculo:id,placa,tipo_veiculo_id',
                'veiculo.tipoVeiculo:id,descricao',
                'abastecimentoInicial',
                'abastecimentoFinal',
            ])
            ->withCount(['viagens', 'documentos', 'abastecimentos'])
            ->withSum('documentos', 'valor_liquido')
            ->withSum('abastecimentos', 'preco_total')
            ->withSum('abastecimentos', 'quantidade')
            ->withSum('viagens', 'km_pago')
            ->withSum('viagens', 'km_rodado')
            ->withSum('manutencaoLancamentos', 'valor_total_centavos')
            ->orderBy('data_inicio')
            ->orderBy('veiculo_id')
            ->get();
    }

    public function summarize(Collection $records): array
    {
        $lines = $records->map(fn (ResultadoPeriodo $record): array => $this->line($record));
        $faturamento = $lines->sum('faturamento');
        $combustivel = $lines->sum('combustivel');
        $manutencao = $lines->sum('manutencao');
        $folhaPagamento = $lines->sum('folha_pagamento');
        $litros = $lines->sum('litros');
        $kmPago = $lines->sum('km_pago');
        $kmRodadoViagens = $lines->sum('km_rodado_viagens');
        $linhasComKmAbastecimento = $lines->filter(fn (array $line): bool => $line['km_rodado_abastecimento'] !== null);
        $kmRodadoAbastecimento = $linhasComKmAbastecimento->isEmpty()
            ? null
            : $linhasComKmAbastecimento->sum('km_rodado_abastecimento');
        $kmPagoBaseAbastecimento = $linhasComKmAbastecimento->sum('km_pago');
        $veiculos = $lines->pluck('veiculo_id')->filter()->unique()->count();
        $custoTotal = $combustivel + $manutencao + $folhaPagamento;

        return [
            'identificacao' => $this->identificacao($records, $lines),
            'metricas' => [
                'faturamento' => $faturamento,
                'veiculos' => $veiculos,
                'faturamento_medio' => $veiculos > 0 ? $faturamento / $veiculos : null,
                'km_rodado_abastecimento' => $kmRodadoAbastecimento,
                'km_rodado_viagens' => $kmRodadoViagens,
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
                'manutencao' => $manutencao,
                'percentual_manutencao_faturamento' => $this->percentual($manutencao, $faturamento),
                'custo_medio_veiculo' => $veiculos > 0 ? $custoTotal / $veiculos : null,
                'salario' => $folhaPagamento,
                'percentual_salario_faturamento' => $this->percentual($folhaPagamento, $faturamento),
                'litros' => $litros,
                'custo_total' => $custoTotal,
            ],
            'linhas' => $lines,
            'observacoes' => [
                'total_resultados' => $records->count(),
                'veiculos_com_km_abastecimento' => $linhasComKmAbastecimento->pluck('veiculo_id')->unique()->count(),
                'viagens' => $lines->sum('viagens_count'),
                'abastecimentos' => $lines->sum('abastecimentos_count'),
                'documentos' => $lines->sum('documentos_count'),
                'status' => $records->pluck('status')->filter()->unique()->values(),
            ],
        ];
    }

    private function line(ResultadoPeriodo $record): array
    {
        return [
            'veiculo_id' => (int) $record->veiculo_id,
            'placa' => $record->veiculo?->placa ?? 'N/D',
            'tipo_veiculo' => $record->veiculo?->tipoVeiculo?->descricao,
            'faturamento' => $this->reais($record->documentos_sum_valor_liquido),
            'combustivel' => $this->reais($record->abastecimentos_sum_preco_total),
            'manutencao' => $this->reais($record->manutencao_lancamentos_sum_valor_total_centavos),
            'folha_pagamento' => $this->reais($record->getRawOriginal('folha_pagamento_centavos')),
            'litros' => (float) ($record->abastecimentos_sum_quantidade ?? 0),
            'km_pago' => (float) ($record->viagens_sum_km_pago ?? 0),
            'km_rodado_viagens' => (float) ($record->viagens_sum_km_rodado ?? 0),
            'km_rodado_abastecimento' => $record->km_rodado_abastecimento === null
                ? null
                : (float) $record->km_rodado_abastecimento,
            'viagens_count' => (int) ($record->viagens_count ?? 0),
            'abastecimentos_count' => (int) ($record->abastecimentos_count ?? 0),
            'documentos_count' => (int) ($record->documentos_count ?? 0),
            'data_inicio' => $record->data_inicio,
            'data_fim' => $record->data_fim,
        ];
    }

    private function identificacao(Collection $records, Collection $lines): array
    {
        $inicio = $records
            ->pluck('data_inicio')
            ->filter()
            ->map(fn ($date): Carbon => Carbon::parse($date))
            ->sortBy(fn (Carbon $date): int => $date->timestamp)
            ->first();
        $fim = $records
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
