<?php

namespace App\Filament\Resources\Veiculos\Actions;

use App\Filament\Resources\Pneus\PneuResource;
use App\Services;
use App\Enum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class DesvincularPneuAction
{
    protected Services\Pneus\MovimentarPneuService $movimentarPneuService;

    public function __construct()
    {
    }

    public static function make(): Action
    {
        return Action::make('desvincular-pneu')
            ->icon('heroicon-o-arrow-down-on-square')
            ->color('danger')
            ->iconButton()
            ->tooltip('Desvincular Pneu')
            ->visible(fn($record) => ! $record->pneu_id == null)
            ->modalWidth(Width::Large)
            ->schema(fn(Schema $schema) => $schema
                ->columns(8)
                ->schema([
                    Select::make('motivo')
                        ->columnSpanFull()
                        ->native(false)
                        ->options(Enum\Pneu\MotivoMovimentoPneuEnum::toSelectArray())
                        ->required(),
                    TextInput::make('sulco')
                        ->columnSpan(2)
                        ->required()
                        ->numeric()
                        ->maxValue(30)
                        ->minValue(0),
                    DatePicker::make('data_final')
                        ->label('Dt. Final')
                        ->columnSpan(3)
                        ->date('d/m/Y')
                        ->default(now())
                        ->maxDate(now())
                        ->required(),
                    TextInput::make('km_final')
                        ->label('Km Final')
                        ->columnSpan(3)
                        ->numeric()
                        ->required(),
                    Textarea::make('observacao')
                        ->label('Observação')
                        ->columnSpanFull()
                        ->maxLength(255),
                    FileUpload::make('anexos')
                        ->visibility('private')
                        ->directory('pneus/movimentacoes')
                        ->columnSpanFull()
                ]))
            ->action(fn($record, array $data) => (new Services\Pneus\MovimentarPneuService())->removerPneu($record, $data));
    }
}
