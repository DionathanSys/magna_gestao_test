<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Actions;

use App\Services\Relatorios\ExportarViagensBugioXlsxService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;

class ExportarViagensBugioPorPeriodoAction
{
    public static function make(): Action
    {
        return Action::make('exportar-viagens-periodo')
            ->label('Exportar por período')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->modalHeading('Exportar viagens Bugio')
            ->modalDescription('Será gerada uma linha para cada viagem no período de competência selecionado.')
            ->modalSubmitActionLabel('Exportar')
            ->schema([
                DatePicker::make('data_inicio')
                    ->label('Data de competência inicial')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->maxDate(fn (Get $get) => $get('data_fim') ?: now()),
                DatePicker::make('data_fim')
                    ->label('Data de competência final')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->maxDate(now())
                    ->afterOrEqual('data_inicio'),
            ])
            ->action(function (array $data) {
                $response = app(ExportarViagensBugioXlsxService::class)->exportByPeriod(
                    $data['data_inicio'],
                    $data['data_fim'],
                );

                if ($response) {
                    return $response;
                }

                Notification::make()
                    ->warning()
                    ->title('Sem viagens no período')
                    ->body('Nenhuma viagem Bugio foi encontrada para as datas informadas.')
                    ->send();

                return null;
            });
    }
}
