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
                    ->options(fn () => Models\Integrado::query()
                        ->orderBy('nome')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Models\Integrado $record) => [
                            $record->id => "{$record->codigo} {$record->nome}",
                        ])
                        ->all())
                    ->getSearchResultsUsing(fn (string $search): array => Models\Integrado::query()
                        ->where(function ($query) use ($search) {
                            $query
                                ->where('codigo', 'like', "%{$search}%")
                                ->orWhere('nome', 'like', "%{$search}%");
                        })
                        ->orderBy('nome')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Models\Integrado $record) => [
                            $record->id => "{$record->codigo} {$record->nome}",
                        ])
                        ->all())
                    ->getOptionLabelUsing(fn ($value): ?string => Models\Integrado::query()
                        ->whereKey($value)
                        ->get()
                        ->map(fn (Models\Integrado $record) => "{$record->codigo} {$record->nome}")
                        ->first())
                    ->searchable()
                    ->createOptionForm([
                        TextInput::make('codigo')
                            ->label('Código')
                            ->required()
                            ->columnSpan(4),
                        TextInput::make('nome')
                            ->label('Nome')
                            ->required()
                            ->columnSpan(8),
                        TextInput::make('municipio')
                            ->label('Município')
                            ->columnSpan(9),
                        TextInput::make('estado')
                            ->label('Estado')
                            ->default('SC')
                            ->columnSpan(3),
                        TextInput::make('km_rota')
                            ->label('KM Rota')
                            ->required()
                            ->numeric()
                            ->columnSpan(4),
                        TextInput::make('latitude')
                            ->label('Latitude')
                            ->default('0.00000000')
                            ->columnSpan(4),
                        TextInput::make('longitude')
                            ->label('Longitude')
                            ->default('0.00000000')
                            ->columnSpan(4),
                        Select::make('cliente')
                            ->label('Cliente')
                            ->required()
                            ->native(false)
                            ->options(ClienteEnum::toSelectArray())
                            ->columnSpanFull(),
                        Toggle::make('alerta_viagem')
                            ->label('Alerta Viagem')
                            ->default(false)
                            ->columnSpanFull(),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        return Models\Integrado::query()->create($data)->getKey();
                    })
                    ->required(),
            ])
            ->action(function (Models\Viagem $record, array $data) {
                $integrado = Models\Integrado::findOrFail($data['integrado_id']);
                $carga = (new CargaService())->gerarOuComplementar($integrado, $record, true);

                if (! $carga) {
                    Notification::make()
                        ->warning()
                        ->title('Nenhuma carga criada')
                        ->body('Nao foi possivel adicionar a carga para a viagem.')
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
