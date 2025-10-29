<?php

namespace App\Filament\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KmVeiculoDesatualizado extends StatsOverviewWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

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

            if(!$veiculosDesatualizados){
                return [];
            }

        return [
            Stat::make('KM desatualizado', $veiculosDesatualizados)
                ->description('Veículos sem atualização há > 2 dias')
                ->descriptionIcon(Heroicon::Clock)
                ->color($veiculosDesatualizados > 0 ? 'danger' : 'success'),
        ];
    }
}
