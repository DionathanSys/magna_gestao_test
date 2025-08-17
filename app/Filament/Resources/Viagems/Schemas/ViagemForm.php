<?php

namespace App\Filament\Resources\Viagems\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ViagemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('veiculo_id')
                    ->required()
                    ->numeric(),
                TextInput::make('numero_viagem')
                    ->required(),
                TextInput::make('numero_custo_frete'),
                TextInput::make('documento_transporte'),
                TextInput::make('tipo_viagem'),
                TextInput::make('valor_frete')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('valor_cte')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('valor_nfs')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('valor_icms')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('km_rodado')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('km_pago')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('km_divergencia')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('km_cadastro')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('km_rota_corrigido')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('km_pago_excedente')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('km_rodado_excedente')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('km_cobrar')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('motivo_divergencia'),
                TextInput::make('peso')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('entregas')
                    ->required()
                    ->numeric()
                    ->default(1.0),
                DatePicker::make('data_competencia')
                    ->required(),
                DateTimePicker::make('data_inicio')
                    ->required(),
                DateTimePicker::make('data_fim')
                    ->required(),
                Toggle::make('conferido')
                    ->required(),
                TextInput::make('divergencias'),
                TextInput::make('created_by')
                    ->numeric(),
                TextInput::make('updated_by')
                    ->numeric(),
                TextInput::make('checked_by')
                    ->numeric(),
                TextInput::make('km_dispersao')
                    ->numeric(),
                TextInput::make('dispersao_percentual')
                    ->numeric(),
            ]);
    }
}
