<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Enum\ClienteEnum;
use App\Models;
use App\Services\Carga\CargaService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;

class NovaCargaAction
{
    public static function make(): Action
    {
        return Action::make('nova-carga')
            ->label('Carga')
            ->icon('heroicon-o-plus')
            ->modalSubmitAction(fn(Action $action) => $action->label('Adicionar Carga'))
            ->schema([
                Select::make('integrado_id')
                    ->label('Integrado')
                    ->relationship('integrados', 'nome')
                    ->searchable(['codigo', 'nome'])
                    ->getOptionLabelFromRecordUsing(fn(Models\Integrado $record) => "{$record->codigo} {$record->nome}")
                    ->createOptionForm([
                        TextInput::make('codigo')
                            ->label('Código')
                            ->required(),
                        TextInput::make('nome')
                            ->label('Nome')
                            ->required(),
                        TextInput::make('municipio')
                            ->label('Município'),
                        TextInput::make('estado')
                            ->label('Estado')
                            ->default('SC'),
                        TextInput::make('km_rota')
                            ->label('KM Rota')
                            ->required()
                            ->numeric(),
                        TextInput::make('latitude')
                            ->label('Latitude')
                            ->default('0.00000000'),
                        TextInput::make('longitude')
                            ->label('Longitude')
                            ->default('0.00000000'),
                        Toggle::make('alerta_viagem')
                            ->label('Alerta Viagem')
                            ->default(false),
                        Select::make('cliente')
                            ->label('Cliente')
                            ->required()
                            ->native(false)
                            ->options(ClienteEnum::toSelectArray()),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        return Models\Integrado::query()->create($data)->getKey();
                    })
                    ->required(),
            ])
            ->action(function (Models\Viagem $record, array $data) {
                $integrado = Models\Integrado::findOrFail($data['integrado_id']);
                $carga = (new CargaService())->gerarOuComplementar($integrado, $record);

                if (! $carga) {
                    Notification::make()
                        ->warning()
                        ->title('Nenhuma carga criada')
                        ->body('A viagem já possui cargas pendentes de complemento e nenhuma nova carga foi criada automaticamente.')
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('Carga incluída com sucesso!')
                    ->body('A carga foi adicionada ou complementada sem duplicação.')
                    ->send();
            });
    }
}
