<?php

namespace App\Filament\Resources\Abastecimentos\Schemas;

use App\Enum\Abastecimento\TipoCombustivelEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AbastecimentoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id_abastecimento')
                    ->required()
                    ->numeric(),
                Select::make('veiculo_id')
                    ->relationship('veiculo', 'placa')
                    ->searchable()
                    ->required(),
                TextInput::make('quilometragem')
                    ->required(),
                TextInput::make('posto_combustivel')
                    ->required(),
                Select::make('tipo_combustivel')
                    ->options(TipoCombustivelEnum::class)
                    ->required(),
                TextInput::make('quantidade')
                    ->required()
                    ->numeric(),
                TextInput::make('preco_por_litro')
                    ->required()
                    ->numeric(),
                DateTimePicker::make('data_abastecimento')
                    ->required(),
                Toggle::make('considerar_fechamento')
                    ->required(),
                Toggle::make('considerar_calculo_medio')
                    ->required(),
            ]);
    }
}
