<?php

namespace App\Filament\Resources\Veiculos\Actions;

use App\Models\PneuPosicaoVeiculo;
use App\Services;
use App\Models;
use App\Enum;
use App\Services\Veiculo\VeiculoService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Schemas\Components\Icon;
use Filament\Support\Icons\Heroicon;

class VincularPneuAction
{
    public static function make(): Action
    {
        return Action::make('vincular-pneu')
            ->icon('heroicon-o-arrow-up-on-square')
            ->color('info')
            ->iconButton()
            ->tooltip('Vincular Pneu')
            ->visible(fn($record) => $record->pneu_id == null)
            ->modalWidth(Width::ExtraLarge)
            ->fillForm(fn(PneuPosicaoVeiculo $record): array => [
                'posicao' => $record->posicao,
            ])
            ->schema(fn(Schema $schema) => $schema
                ->columns(4)
                ->schema([
                    Select::make('pneu_id')
                        ->label('Pneu')
                        ->columnSpan(3)
                        ->native(false)
                        ->options(fn(): array => (new Services\Pneus\PneuService())->getPneusDisponiveis())
                        // ->getSearchResultsUsing(fn(string $search): array => (new Services\Pneus\PneuService())->getPneusDisponiveis($search))
                        // ->getOptionLabelUsing(fn($value): ?string => Models\Pneu::find($value)?->descricao)
                        ->searchable()
                        ->searchDebounce(700)
                        ->required(),
                    TextInput::make('posicao')
                        ->label('Posição')
                        ->columnSpan(1)
                        ->readOnly(),
                    DatePicker::make('data_inicial')
                        ->label('Dt. Inicial')
                        ->columnSpan(2)
                        ->date('d/m/Y')
                        ->default(now())
                        ->maxDate(now())
                        ->required(),
                    TextInput::make('km_inicial')
                        ->label('KM Inicial')
                        ->columnSpan(2)
                        ->numeric()
                        ->required()
                        ->live(debounce: 700)
                        ->afterStateUpdated(function (PneuPosicaoVeiculo $record, Field $component, $state) {
                            $limites = VeiculoService::getQuilometragemLimiteMovimentacao($record->veiculo_id);
                            if ($state < $limites['km_minimo'] || $state > $limites['km_maximo']) {
                                $component->belowContent([
                                    Icon::make(Heroicon::InformationCircle),
                                    'Verifique a quilometragem.',
                                ]);
                            }
                        })
                ]))
            ->action(fn($record, array $data) => (new Services\Pneus\MovimentarPneuService())->aplicarPneu($record, $data));
    }
}
