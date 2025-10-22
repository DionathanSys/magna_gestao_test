<?php

namespace App\Filament\Resources\Abastecimentos\Widgets;

use App\Filament\Resources\Abastecimentos\Pages\ListAbastecimentos;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ConsumoMedioDiesel extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return ListAbastecimentos::class;
    }
    
    protected function getStats(): array
    {
        // Busca os registros filtrados para calcular os valores dos Accessors
        $abastecimentos = $this->getPageTableQuery()->get();
        
        // Filtra apenas registros que têm valores válidos para os cálculos
        $abastecimentosValidos = $abastecimentos->filter(function ($abastecimento) {
            return $abastecimento->consumo_medio !== null && 
                   $abastecimento->quilometragem_percorrida !== null;
        });

        // Calcular consumo médio geral
        $consumoMedio = $abastecimentosValidos->isNotEmpty() 
            ? round($abastecimentosValidos->avg('consumo_medio'), 2)
            : 0;

        // Calcular total de KM percorrido
        $totalKmPercorrido = $abastecimentos->sum(function ($abastecimento) {
            return $abastecimento->quilometragem_percorrida ?? 0;
        });

        // Calcular custo médio por KM
        $custoPorKm = $abastecimentosValidos->isNotEmpty()
            ? round($abastecimentosValidos->avg('custo_por_km'), 4)
            : 0;

        // Calcular totais para descrições adicionais
        $totalLitros = $abastecimentos->sum('quantidade');
        $totalAbastecimentos = $abastecimentos->count();
        $totalValor = $abastecimentos->sum(function ($abastecimento) {
            return $abastecimento->valor_total ?? $abastecimento->preco_total ?? 0;
        });

        return [
            Stat::make('Consumo Médio', number_format($consumoMedio, 2, ',', '.'))
                ->description("Baseado em {$abastecimentosValidos->count()} abastecimentos válidos")
                ->descriptionIcon(Heroicon::ChartBar)
                ->color('success'),

            Stat::make('KM Total Percorrido', number_format($totalKmPercorrido, 0, ',', '.'))
                ->description("Em {$totalAbastecimentos} abastecimentos")
                ->descriptionIcon(Heroicon::ChartBarSquare)
                ->color('info'),

            Stat::make('Custo por KM', 'R$ ' . number_format($custoPorKm, 4, ',', '.'))
                ->description("Valor total: R$ " . number_format($totalValor, 2, ',', '.'))
                ->descriptionIcon(Heroicon::CurrencyDollar)
                ->color('warning'),
        ];
    }
}
