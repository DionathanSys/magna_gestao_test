<?php

namespace App\Filament\Resources\Inspecaos\Schemas;

use App\Filament\Resources\OrdemServicos;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class InspecaoForm
{
    public static function configure(Schema $schema): Schema
    {

        return $schema
            ->columns(12)
            ->components([
                // Select::make('veiculo_id')
                //     ->relationship('veiculo', 'placa', modifyQueryUsing: fn($query) => $query->orderBy('placa')->where('is_active', true))
                //     ->searchable()
                //     ->required(),
                OrdemServicos\Schemas\Components\OrdemServicoVeiculoInput::make('veiculo_id')
                    ->columnSpan(2),
                DatePicker::make('data_inspecao')
                    ->label('Dt. Inspeção')
                    ->columnSpan(2)
                    ->required()
                    ->default(now()),
                TextInput::make('quilometragem')
                    ->required()
                    ->columnSpan(2)
                    ->numeric(),
                Textarea::make('observacoes')
                    ->label('Observações')
                    ->columnSpan(6),
            ]);
    }
}
