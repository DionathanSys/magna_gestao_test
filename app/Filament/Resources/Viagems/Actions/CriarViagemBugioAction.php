<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Enum\ClienteEnum;
use App\Models\Integrado;
use App\Models\Veiculo;
use App\Services\MailInbound\ShipmentTripService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class CriarViagemBugioAction
{
    public static function make(): Action
    {
        return Action::make('criar_viagem_bugio')
            ->label('+ Viagem Bugio')
            ->icon('heroicon-o-plus-circle')
            ->modalHeading('Criar Viagem Bugio')
            ->modalDescription('Cliente, numero da viagem, documento de transporte e demais padroes do fluxo automatico serao preenchidos pelo sistema.')
            ->modalSubmitActionLabel('Criar viagem')
            ->schema([
                Select::make('integrado_id')
                    ->label('Integrado')
                    ->options(fn (): array => Integrado::query()
                        ->where('cliente', ClienteEnum::BUGIO->value)
                        ->orderBy('nome')
                        ->pluck('nome', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('veiculo_id')
                    ->label('Veiculo')
                    ->options(fn (): array => Veiculo::query()
                        ->where('is_active', true)
                        ->orderBy('placa')
                        ->pluck('placa', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('data_competencia')
                    ->label('Data da viagem')
                    ->default(now()->toDateString())
                    ->required(),
                TextInput::make('km_rodado')
                    ->label('KM Rodado')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
            ])
            ->action(function (array $data, ShipmentTripService $shipmentTripService): void {
                $viagem = $shipmentTripService->createManualBugioTrip($data);

                Notification::make()
                    ->success()
                    ->title('Viagem Bugio criada')
                    ->body("Viagem {$viagem->numero_viagem} criada com carga inicial vinculada.")
                    ->send();
            });
    }
}
