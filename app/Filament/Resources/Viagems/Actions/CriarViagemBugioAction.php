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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CriarViagemBugioAction
{
    public static function make(): Action
    {
        return Action::make('criar_viagem_bugio')
            ->label('+ Viagem Bugio')
            ->icon('heroicon-o-plus-circle')
            ->modalHeading('Criar Viagem Bugio')
            ->modalDescription('Cliente, numero da viagem e demais padroes do fluxo automatico serao preenchidos pelo sistema.')
            ->modalSubmitActionLabel('Criar viagem')
            ->extraModalFooterActions(fn (Action $action): array => [
                $action->makeModalSubmitAction('criarOutro', arguments: ['another' => true])
                    ->label('Criar outro'),
            ])
            ->preserveFormDataWhenCreatingAnother(['veiculo_id', 'documento_transporte', 'data_competencia'])
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
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        $kmRota = 0;

                        if ($state) {
                            $kmRota = (float) (Integrado::query()->whereKey($state)->value('km_rota') ?? 0);
                        }

                        $set('km_pago', number_format($kmRota, 2, '.', ''));
                    })
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
                TextInput::make('documento_transporte')
                    ->label('Documento Transporte')
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
                TextInput::make('km_pago')
                    ->label('KM Pago')
                    ->numeric()
                    ->minValue(0)
                    ->default(fn (Get $get): float => (float) (Integrado::query()->whereKey($get('integrado_id'))->value('km_rota') ?? 0))
                    ->required(),
            ])
            ->action(function (Action $action, Schema $schema, array $data, array $arguments, ShipmentTripService $shipmentTripService): void {
                $viagem = $shipmentTripService->createManualBugioTrip($data);

                Notification::make()
                    ->success()
                    ->title('Viagem Bugio criada')
                    ->body("Viagem {$viagem->numero_viagem} criada com carga inicial vinculada.")
                    ->send();

                if ($arguments['another'] ?? false) {
                    $schema->fill([
                        'veiculo_id' => $data['veiculo_id'] ?? null,
                        'documento_transporte' => $data['documento_transporte'] ?? null,
                        'data_competencia' => $data['data_competencia'] ?? null,
                    ]);

                    $action->halt();
                }
            });
    }
}
