<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Enum\ClienteEnum;
use App\Services\Relatorios\ExportarViagensXlsxService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;

class ExportarViagensPorPeriodoAction
{
    public static function make(): Action
    {
        return Action::make('exportar-viagens-periodo')
            ->label('Exportar modelo de importação')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->modalHeading('Exportar viagens')
            ->modalDescription('Será gerada uma linha para cada viagem do cliente e período selecionados.')
            ->modalSubmitActionLabel('Exportar')
            ->schema([
                Select::make('cliente')
                    ->label('Cliente')
                    ->options(ClienteEnum::toSelectArray())
                    ->required()
                    ->searchable()
                    ->native(false),
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
                $response = app(ExportarViagensXlsxService::class)->exportByPeriod(
                    $data['cliente'],
                    $data['data_inicio'],
                    $data['data_fim'],
                );

                if ($response) {
                    return $response;
                }

                Notification::make()
                    ->warning()
                    ->title('Sem viagens no período')
                    ->body('Nenhuma viagem foi encontrada para o cliente e as datas informadas.')
                    ->send();

                return null;
            });
    }
}
