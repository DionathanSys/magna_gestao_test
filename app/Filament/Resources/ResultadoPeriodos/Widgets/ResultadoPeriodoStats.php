<?php

namespace App\Filament\Resources\ResultadoPeriodos\Widgets;

use App\Filament\Resources\ResultadoPeriodos\Pages\ListResultadoPeriodos;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResultadoPeriodoStats extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected ?string $pollingInterval = null;

    public function getColumns(): int|array
    {
        return 4;
    }

    protected function getTablePage(): string
    {
        return ListResultadoPeriodos::class;
    }

    protected function getStats(): array
    {
        $records = $this->getPageTableQuery()->get();

        $registrosCount = $records->count();

        $faturamento = $records->sum('documentos_sum_valor_liquido') / 100;
        $manutencao = $records->sum('manutencao_lancamentos_sum_valor_total_centavos') / 100;
        $percentualManutencaoFaturamento = $faturamento > 0 ? ($manutencao / $faturamento) * 100 : 0;
        $combustivel = $records->sum('abastecimentos_sum_preco_total') / 100;
        $percentualCombustivelFaturamento = $faturamento > 0 ? ($combustivel / $faturamento) * 100 : 0;
        $folhaPagamento = $records->sum('folha_pagamento_centavos');
        $resultadoLiquido = $faturamento - $combustivel - $manutencao - $folhaPagamento;
        $margemLiquida = $faturamento > 0 ? ($resultadoLiquido / $faturamento) * 100 : null;
        $custosOperacionais = $combustivel + $manutencao + $folhaPagamento;
        $percentualCustosOperacionais = $faturamento > 0 ? ($custosOperacionais / $faturamento) * 100 : 0;
        $resultadosNegativos = $records->filter(fn ($record): bool => $record->resultado_liquido < 0)->count();
        $dadosIncompletos = $records->filter(fn ($record): bool => $record->km_rodado_abastecimento === null)->count();

        return [
            Stat::make('Faturamento', 'R$ '.number_format($faturamento, 2, ',', '.'))
                ->description($registrosCount.' resultado(s) no filtro atual')
                ->descriptionIcon(Heroicon::ChartBar, IconPosition::Before)
                ->color('success'),
            Stat::make('Resultado líquido', 'R$ '.number_format($resultadoLiquido, 2, ',', '.'))
                ->description($margemLiquida === null ? 'Margem indisponível' : 'Margem '.number_format($margemLiquida, 1, ',', '.').'%')
                ->descriptionIcon(Heroicon::ChartBar, IconPosition::Before)
                ->color($resultadoLiquido < 0 ? 'danger' : 'success'),
            Stat::make('Custos operacionais', 'R$ '.number_format($custosOperacionais, 2, ',', '.'))
                ->description(number_format($percentualCustosOperacionais, 1, ',', '.').'% do faturamento | Comb. '.number_format($percentualCombustivelFaturamento, 1, ',', '.').'% | Manut. '.number_format($percentualManutencaoFaturamento, 1, ',', '.').'%')
                ->descriptionIcon(Heroicon::ChartBar, IconPosition::Before)
                ->color($percentualCustosOperacionais > 100 ? 'danger' : 'warning'),
            Stat::make('Pontos de atenção', $resultadosNegativos + $dadosIncompletos)
                ->description($resultadosNegativos.' resultado(s) negativo(s) | '.$dadosIncompletos.' com dados de KM incompletos')
                ->descriptionIcon(Heroicon::ExclamationTriangle, IconPosition::Before)
                ->color($resultadosNegativos > 0 ? 'danger' : ($dadosIncompletos > 0 ? 'warning' : 'success')),
        ];
    }
}
