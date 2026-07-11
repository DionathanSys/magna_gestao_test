<?php

namespace App\Filament\Widgets;

use App\Models\Agendamento;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AgendamentoStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $abertos = Agendamento::query()->abertos()->count();
        $atrasados = Agendamento::query()->pendentes()->atrasados()->count();
        $hoje = Agendamento::query()->agendadosPara(now()->toDateString())->count();
        $checklist = Agendamento::query()->checklist()->abertos()->count();
        $semVinculo = Agendamento::query()->abertos()->semOrdemServico()->count();

        return [
            Stat::make('Abertos', (string) $abertos)
                ->description('Pendentes e em execução')
                ->descriptionIcon(Heroicon::Clock, IconPosition::Before)
                ->color('gray'),
            Stat::make('Atrasados', (string) $atrasados)
                ->description('Pendências fora da data')
                ->descriptionIcon(Heroicon::ExclamationTriangle, IconPosition::Before)
                ->color($atrasados > 0 ? 'danger' : 'success'),
            Stat::make('Hoje', (string) $hoje)
                ->description('Fila programada para hoje')
                ->descriptionIcon(Heroicon::CalendarDays, IconPosition::Before)
                ->color('info'),
            Stat::make('Checklist', (string) $checklist)
                ->description('Pendências originadas no checklist')
                ->descriptionIcon(Heroicon::ClipboardDocumentList, IconPosition::Before)
                ->color('warning'),
            Stat::make('Sem OS', (string) $semVinculo)
                ->description('Abertos ainda sem vínculo')
                ->descriptionIcon(Heroicon::LinkSlash, IconPosition::Before)
                ->color('primary'),
        ];
    }
}
