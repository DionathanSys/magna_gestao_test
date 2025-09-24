<?php

namespace App\Filament\Resources\Veiculos\Actions;

use App\Models\PneuPosicaoVeiculo;
use App\Services;
use App\Models;
use App\Enum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Icon;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class InverterPneuAction
{

    public static function make(): Action
    {
        return Action::make('inverter-pneu')
            ->icon('heroicon-o-arrow-path')
            ->iconButton()
            ->tooltip('Inverter Pneu na Mesma Posição')
            ->visible(fn($record) => ! $record->pneu_id == null)
            ->modalWidth(Width::ExtraLarge)
            ->schema(fn(Schema $schema) => $schema
                ->columns(8)
                ->schema([
                    TextInput::make('motivo')
                        ->columnSpan(5)
                        ->default(Enum\Pneu\MotivoMovimentoPneuEnum::INVERSAO)
                        ->disabled()
                        ->required(),
                    TextInput::make('sulco')
                        ->label('Sulco (mm)')
                        ->columnSpan(3)
                        ->numeric()
                        ->maxValue(30)
                        ->minValue(0),
                    DatePicker::make('data_movimento')
                        ->label('Dt. Movimento')
                        ->columnSpan(4)
                        ->date('d/m/Y')
                        ->default(now())
                        ->maxDate(now())
                        ->required(),
                    TextInput::make('km_movimento')
                        ->label('Km Movimento')
                        ->columnSpan(4)
                        ->numeric()
                        ->required()
                        ->live(debounce: 700)
                        ->afterStateUpdated(function (PneuPosicaoVeiculo $record, Field $component, $state) {
                            $limites = Services\Veiculo\VeiculoService::getQuilometragemLimiteMovimentacao($record->veiculo_id);
                            if ($state < $limites['km_minimo'] || $state > $limites['km_maximo']) {
                                $component->belowContent([
                                    Icon::make(Heroicon::InformationCircle)->color(Color::Indigo),
                                    Text::make('Verifique a quilometragem.')->weight(FontWeight::Bold)->color(Color::Amber),
                                ]);
                            }
                        }),
                    Textarea::make('observacao')
                        ->label('Observação')
                        ->columnSpanFull()
                        ->maxLength(255),
                    FileUpload::make('anexos')
                        ->image()
                        ->multiple()
                        ->panelLayout('grid')
                        ->disk('local')
                        ->directory('pneus/movimentacoes')
                        ->visibility('private')
                        ->columnSpanFull()
                ]))
            ->action(fn(array $data, PneuPosicaoVeiculo $record) => (new Services\Pneus\MovimentarPneuService())->inverterPneu($record, $data));
    }
}
