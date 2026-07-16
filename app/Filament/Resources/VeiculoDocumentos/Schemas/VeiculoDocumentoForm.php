<?php

namespace App\Filament\Resources\VeiculoDocumentos\Schemas;

use App\Models\VeiculoDocumento;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VeiculoDocumentoForm
{
    public static function configure(Schema $schema, bool $showVeiculo = true): Schema
    {
        return $schema
            ->columns(12)
            ->components(self::components($showVeiculo));
    }

    public static function components(bool $showVeiculo = true): array
    {
        return [
            Section::make('Documento')
                ->columns(12)
                ->columnSpanFull()
                ->schema([
                    ...($showVeiculo ? [
                        Select::make('veiculo_id')
                            ->label('Veículo')
                            ->relationship('veiculo', 'placa')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(3),
                    ] : []),
                    TextInput::make('nome')
                        ->label('Documento')
                        ->placeholder('Ex: Laudo Teste de Fumaça')
                        ->required()
                        ->maxLength(150)
                        ->columnSpan($showVeiculo ? 3 : 4),
                    Select::make('tipo')
                        ->label('Tipo')
                        ->options(VeiculoDocumento::tipoOptions())
                        ->default(VeiculoDocumento::TIPO_OUTROS)
                        ->native(false)
                        ->required()
                        ->columnSpan(2),
                    DatePicker::make('data_inicio')
                        ->label('Data Início')
                        ->native(false)
                        ->columnSpan(2),
                    DatePicker::make('data_fim')
                        ->label('Data Fim')
                        ->native(false)
                        ->afterOrEqual('data_inicio')
                        ->columnSpan(2),
                    TextInput::make('dias_alerta')
                        ->label('Dias Alerta')
                        ->numeric()
                        ->minValue(0)
                        ->default(30)
                        ->required()
                        ->columnSpan(1),
                    Textarea::make('descricao')
                        ->label('Descrição')
                        ->rows(3)
                        ->columnSpanFull(),
                    FileUpload::make('anexos')
                        ->label('Anexos')
                        ->multiple()
                        ->openable()
                        ->downloadable()
                        ->panelLayout('grid')
                        ->disk('local')
                        ->directory('veiculos/documentos')
                        ->visibility('private')
                        ->columnSpanFull(),
                ]),
        ];
    }
}
