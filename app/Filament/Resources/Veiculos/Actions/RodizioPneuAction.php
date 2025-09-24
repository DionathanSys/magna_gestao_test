<?php

namespace App\Filament\Resources\Veiculos\Actions;

use App\Filament\Resources\Pneus\PneuResource;
use App\Services;
use App\Models;
use App\Enum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Icon;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class RodizioPneuAction
{

    public static function make(): BulkAction
    {
        return BulkAction::make('desvincular-pneu')
            ->label('Rodízio')
            ->icon('heroicon-o-arrows-right-left')
            ->requiresConfirmation()
            ->modalWidth(Width::Large)
            ->schema(fn(Schema $schema) => $schema
                ->columns(8)
                ->schema([
                    TextInput::make('motivo')
                        ->columnSpan(5)
                        ->default(Enum\Pneu\MotivoMovimentoPneuEnum::RODIZIO)
                        ->disabled()
                        ->required(),
                    TextInput::make('sulco')
                        ->label('Sulco Removido (mm)')
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
                        ->afterStateUpdated(function (Collection $records, Field $component, $state) {
                            $limites = Services\Veiculo\VeiculoService::getQuilometragemLimiteMovimentacao($records->first()->veiculo_id);
                            if ($state < $limites['km_minimo'] || $state > $limites['km_maximo']) {
                                $component->belowContent([
                                    Icon::make(Heroicon::InformationCircle),
                                    'Verifique a quilometragem.',
                                ]);
                            }
                        }),
                    Textarea::make('observacao')
                        ->label('Observação')
                        ->columnSpanFull()
                        ->maxLength(255),
                    FileUpload::make('anexos')
                        ->image()
                        ->openable()
                        ->downloadable()
                        ->multiple()
                        ->panelLayout('grid')
                        ->disk('local')
                        ->directory('pneus/movimentacoes')
                        ->visibility('private')
                        ->columnSpanFull()
                ]))
            ->action(fn(array $data, Collection $records) => (new Services\Pneus\MovimentarPneuService())->rodizioPneu($records, $data))
                        ->deselectRecordsAfterCompletion();
    }
}
