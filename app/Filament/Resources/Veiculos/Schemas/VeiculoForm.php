<?php

namespace App\Filament\Resources\Veiculos\Schemas;

use App\Enum\ClienteEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
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
                    ->compact()
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
                                'CHAPECO' => 'Chapecó',
                                'CONCORDIA' => 'Concórdia',
                            ])
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
                        Toggle::make('is_active')
                            ->label('Ativo')
                            ->columnSpan(1)
                            ->inline(false)
                            ->default(true)
                            ->required(),
                        Select::make('tipo_veiculo_id')
                            ->label('Tipo de Veículo')
                            ->columnStart(1)
                            ->columnSpan(3)
                            ->searchable()
                            ->relationship('tipoVeiculo', 'descricao')
                            ->required(),
                        Select::make('mapa_pneu_id')
                            ->label('Mapa de Pneu')
                            ->columnSpan(3)
                            ->searchable()
                            ->preload()
                            ->relationship('mapaPneu', 'nome')
                            ->required(),
                    ]),
                Section::make('Informações Complementares')
                    ->columns(12)
                    ->columnSpanFull()
                    ->compact()
                    ->schema([
                        Select::make('informacoes_complementares.cliente')
                            ->label('Cliente')
                            ->columnSpan(2)
                            ->searchable()
                            ->options(ClienteEnum::toSelectArray())
                            ->required(),
                        Select::make('informacoes_complementares.motorista_padrao_cte_cpf')
                            ->label('Motorista Padrão CTe')
                            ->columnSpan(3)
                            ->searchable()
                            ->native(false)
                            ->options(fn () => collect(db_config('config-bugio.motoristas'))->pluck('motorista', 'cpf')->toArray())
                            ->helperText('Usado para preencher automaticamente a action de Solicitar CTe deste caminhão.'),
                        TextInput::make('informacoes_complementares.codigo_imobilizado')
                            ->label('Código Imobilizado')
                            ->columnSpan(2),
                        Select::make('informacoes_complementares.bloqueador_silo')
                            ->label('Tipo Bloqueador de Silo')
                            ->native(false)
                            ->columnSpan(2)
                            ->options([
                                'NENHUM' => 'Nenhum',
                                'HOMOLOGADO' => 'Homologado',
                                'RELE' => 'Relé',
                            ]),
                        DatePicker::make('informacoes_complementares.data_ultimo_checklist')
                            ->label('Dt. Último Checklist')
                            ->columnSpan(2),
                        TextInput::make('informacoes_complementares.ano_modelo')
                            ->label('Ano Modelo')
                            ->columnSpan(2),
                        TextInput::make('informacoes_complementares.ano_fabricacao')
                            ->label('Ano Fabricação')
                            ->columnSpan(2),
                        TextInput::make('informacoes_complementares.ano_silo')
                            ->label('Ano Fab. Silo')
                            ->columnSpan(2),
                        TextInput::make('informacoes_complementares.marca_silo')
                            ->label('Marca Silo')
                            ->columnSpan(2),
                    ]),

            ]);
    }
}
