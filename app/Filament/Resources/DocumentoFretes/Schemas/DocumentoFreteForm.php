<?php

namespace App\Filament\Resources\DocumentoFretes\Schemas;

use App\Enum\Frete\TipoDocumentoEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class DocumentoFreteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('veiculo_id')
                    ->relationship('veiculo', 'placa')
                    ->searchable()
                    ->required(),
                Select::make('tipo_documento')
                    ->label('Tipo Documento')
                    ->options(TipoDocumentoEnum::toSelectArray())
                    ->default(TipoDocumentoEnum::CTE->value)
                    ->required(),
                TextInput::make('parceiro_origem')
                    ->label('Parceiro Origem')
                    ->autocomplete(false)
                    ->required(),
                TextInput::make('parceiro_destino')
                    ->label('Parceiro Destino')
                    ->autocomplete(false)
                    ->required(),
                TextInput::make('numero_documento')
                    ->label('Nro. Documento')
                    ->required(),
                TextInput::make('documento_transporte')
                    ->label('Nro. Doc. Transp.'),
                TextInput::make('municipio'),
                TextInput::make('estado'),
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        DatePicker::make('data_emissao')
                            ->label('Dt. Emissão')
                            ->prefix('R$')
                            ->required()
                            ->maxDate(now())
                            ->default(now()),
                        TextInput::make('valor_total')
                            ->label('Vlr. Total')
                            ->prefix('R$')
                            ->minValue(0.01)
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0.0),
                        TextInput::make('valor_icms')
                            ->label('Vlr. ICMS')
                            ->required()
                            ->numeric()
                            ->default(0.0),

                    ]),
                    TextInput::make('viagem_id')
                        ->label('Viagem ID')
                        ->visibleOn('edit'),


            ]);
    }
}
