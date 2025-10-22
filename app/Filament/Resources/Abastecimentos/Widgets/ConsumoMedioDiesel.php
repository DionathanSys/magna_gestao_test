<?php

namespace App\Filament\Resources\Abastecimentos\Widgets;

use App\Models;
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

        // Calcular frete total do período
        $freteTotal = $this->calcularFreteTotalPeriodo();
        $percentualDieselFrete = $freteTotal['valor'] > 0 
            ? ($totalValor / $freteTotal['valor']) * 100 
            : 0;

        return [
            Stat::make('Consumo Médio', number_format($consumoMedio, 2, ',', '.') . 'Km/L')
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

            Stat::make('%/Faturamento', number_format($percentualDieselFrete, 2, ',', '.') . ' %')
                ->description("Vlr. Frete: R$ " . number_format($freteTotal['valor'], 2, ',', '.') . " | {$freteTotal['documentos']} documentos")
                ->descriptionIcon(Heroicon::Truck)
                ->color('primary'),
        ];
    }

    private function calcularFreteTotalPeriodo(): array
    {
        try {

            $queryBuilder = $this->getPageTableQuery();
            
            // Extrai período das datas dos abastecimentos filtrados
            $abastecimentosFiltrados = $queryBuilder->get();
            
            if ($abastecimentosFiltrados->isEmpty()) {
                return [
                    'valor' => 0,
                    'periodo' => 'Sem dados',
                    'documentos' => 0,
                ];
            }

            // Obtém o período baseado nos abastecimentos filtrados
            $dataInicio = $abastecimentosFiltrados->min('data_abastecimento');
            $dataFim = $abastecimentosFiltrados->max('data_abastecimento');
            
            // Obtém os IDs dos veículos dos abastecimentos filtrados
            $veiculosIds = $abastecimentosFiltrados->pluck('veiculo_id')->unique()->toArray();

            // Busca documentos de frete no mesmo período e veículos
            $documentosFreteQuery = Models\DocumentoFrete::query()
                ->whereBetween('data_emissao', [
                    $dataInicio->format('Y-m-d'),
                    $dataFim->format('Y-m-d')
                ]);

            if (!empty($veiculosIds)) {
                $documentosFreteQuery->whereIn('veiculo_id', $veiculosIds);
            }

            $documentosFrete = $documentosFreteQuery->get();
            $valorTotal = $documentosFrete->sum('valor_total'); // Ajuste o nome do campo se necessário
            $totalDocumentos = $documentosFrete->count();

            // Formatar período para exibição
            $periodoFormatado = $dataInicio->format('d/m/Y') . ' a ' . $dataFim->format('d/m/Y');

            return [
                'valor' => $valorTotal,
                'periodo' => $periodoFormatado,
                'documentos' => $totalDocumentos,
            ];

        } catch (\Exception $e) {
            // Debug do erro
            \Illuminate\Support\Facades\Log::error('Erro ao calcular frete total', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return [
                'valor' => 0,
                'periodo' => 'Erro ao calcular',
                'documentos' => 0,
            ];
        }
    }
}
