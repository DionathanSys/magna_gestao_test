<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KmVeiculoDesatualizado extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $veiculosDesatualizados = \App\Models\Veiculo::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query
                    // Não tem km cadastrado
                    ->whereDoesntHave('kmAtual')
                    // OU km atual é mais antigo que 2 dias
                    ->orWhereHas('kmAtual', function ($subQuery) {
                        $subQuery->where('data_referencia', '<', now()->subDays(2));
                    });
            })
            ->count();

        return [
            Stat::make('KM desatualizado', $veiculosDesatualizados)
                ->description('Veículos sem atualização há > 2 dias')
                ->descriptionIcon('heroicon-o-clock')
                ->color($veiculosDesatualizados > 0 ? 'danger' : 'success'),
        ];
    }
}
