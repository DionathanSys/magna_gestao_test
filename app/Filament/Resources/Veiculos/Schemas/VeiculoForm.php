<?php

namespace App\Filament\Resources\Veiculos\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class VeiculoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Informações Básicas')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('placa')
                            ->columnSpan(1)
                            ->disabledOn('edit')
                            ->required(),
                        Select::make('filial')
                            ->columnSpan(2)
                            ->options([
                                'CATANDUVAS' => 'Catanduvas',
                                'CHAPECO'    => 'Chapecó',
                                'CONCORDIA'  => 'Concórdia',
                            ])
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Ativo')
                            ->columnSpan(1)
                            ->inline(false)
                            ->default(true)
                            ->required(),
                        TextInput::make('marca')
                            ->label('Marca')
                            ->columnSpan(2)
                            ->maxLength(50)
                            ->placeholder('Marca do veículo'),
                        TextInput::make('modelo')
                            ->label('Modelo')
                            ->columnSpan(2)
                            ->maxLength(50)
                            ->placeholder('Modelo do veículo'),
                        TextInput::make('chassis')
                            ->label('Chassi')
                            ->columnSpan(2)
                            ->maxLength(50)
                            ->placeholder('Chassi do veículo'),
                        Select::make('tipo_veiculo_id')
                            ->label('Tipo de Veículo')
                            ->columnStart(1)
                            ->columnSpan(3)
                            ->searchable()
                            ->relationship('tipoVeiculo', 'descricao')
                            ->required(),
                    ]),
                Section::make('Informações Complementares')
                    ->columns(12)
                    ->columnSpanFull()
                    ->compact()
                    ->schema([
                        TextInput::make('informacoes_complementares.codigo_imobilizado')
                            ->label('Código Imobilizado')
                            ->columnSpan(2),
                        Select::make('informacoes_complementares.bloqueador_silo')
                            ->label('Tipo Bloqueador de Silo')
                            ->columnSpan(2)
                            ->options([
                                'NENHUM'     => 'Nenhum',
                                'HOMOLOGADO' => 'Homologado',
                                'RELE'       => 'Relé',
                            ]),
                        DatePicker::make('informacoes_complementares.afericao_tacografo')
                            ->label('Dt. Próx. Aferição Tacógrafo')
                            ->columnSpan(2),
                        DatePicker::make('informacoes_complementares.teste_fumaca')
                            ->label('Dt. Teste de Fumaça')
                            ->columnSpan(2),
                        DatePicker::make('informacoes_complementares.data_ultimo_checklist')
                            ->label('Dt. Último Checklist')
                            ->columnSpan(2),
                    ])





            ]);
    }
}
