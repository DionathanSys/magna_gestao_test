<?php

namespace App\Filament\Resources\Pneus\Schemas;

use App\Filament\Resources\Pneus\Actions;
use App\Enum;
use App\Filament\Resources\DesenhoPneus\DesenhoPneuResource;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;

class PneuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Dados do Pneu')
                    ->columns(12)
                    ->columnSpanFull()
                    ->description('Preencha os dados para cadastro do pneu.')
                    ->schema([
                        Components\NumeroFogoInput::make()
                            ->columnSpan(6),
                        Components\CicloVidaInput::make()
                            ->columnSpan(2),
                        TextInput::make('valor')
                            ->columnSpan(4)
                            ->columnStart(1)
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->prefix('R$'),
                        DatePicker::make('data_aquisicao')
                            ->label('Dt. Aquisição')
                            ->columnSpan(4)
                            ->default(now())
                            ->maxDate(now())
                            ->required(),
                        Select::make('medida')
                            ->columnSpan(4)
                            ->columnStart(1)
                            ->options([
                                '275/80 R22.5' => '275/80 R22.5',
                                '295/80 R22.5' => '295/80 R22.5',
                            ])
                            ->default('275/80 R22.5'),
                        Components\MarcaInput::make()
                            ->columnSpan(4),
                        Components\ModeloInput::make()
                            ->columnStart(1)
                            ->columnSpan(4),
                        Components\DesenhoPneuInput::make()
                            ->columnSpan(4),
                        Select::make('status')
                            ->columnStart(1)
                            ->columnSpan(4)
                            ->options(Enum\Pneu\StatusPneuEnum::toSelectArray())
                            ->required()
                            ->default(Enum\Pneu\StatusPneuEnum::DISPONIVEL->value),
                        Select::make('local')
                            ->columnSpan(4)
                            ->options(Enum\Pneu\LocalPneuEnum::toSelectArray())
                            ->required()
                            ->default(Enum\Pneu\LocalPneuEnum::ESTOQUE_CCO->value),

                    ]),
                Section::make('Recapagem')
                    ->description('Registrar recapagem do pneu.')
                    ->visibleOn('create')
                    ->columns(12)
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->afterHeader([
                        Actions\RecaparPneuAction::make('recapar')
                            ->tooltip('Apenas para uso de pneus já cadastrados')
                            ->disabled(fn(Get $get) => empty($get('recap.pneu_id'))),
                    ])
                    ->schema([
                        Hidden::make('recap.pneu_id'),
                        DatePicker::make('recap.data_recapagem')
                            ->date('d/m/Y')
                            ->columnSpan(3)
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection()
                            ->default(now())
                            ->maxDate(now()),
                        TextInput::make('recap.valor_recapagem')
                            ->label('Valor')
                            ->columnSpan(3)
                            ->numeric()
                            ->default(0)
                            ->prefix('R$'),
                        Select::make('recap.desenho_pneu_id_recapagem')
                            ->label('Desenho Borracha')
                            ->relationship('desenhoPneu', 'descricao', fn($query) => $query->where('estado_pneu', Enum\Pneu\EstadoPneuEnum::RECAPADO))
                            ->searchable()
                            ->preload()
                            ->createOptionForm(fn(Schema $schema) => DesenhoPneuResource::form($schema))
                            ->columnSpan(4),

                    ]),
                Section::make('Histórico de Movimentações')
                    ->description('Registrar movimentações do pneu.')
                    ->visibleOn('create')
                    ->columns(12)
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Repeater::make('historicoMovimentacao')
                            ->label('Movimentações')
                            ->columns(12)
                            ->columnSpanFull()
                            ->defaultItems(0)
                            ->minItems(0)
                            ->compact()
                            ->schema([
                                Select::make('veiculo_id')
                                    ->label('Veículo')
                                    ->columnSpan(6)
                                    ->relationship('veiculo', 'placa')
                                    ->searchable(),
                                TextInput::make('eixo')
                                    ->columnSpan(2)
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(4),
                                TextInput::make('posicao')
                                    ->columnSpan(2),
                                Select::make('motivo')
                                    ->columnSpan(4)
                                    ->options(Enum\Pneu\MotivoMovimentoPneuEnum::toSelectArray()),
                                TextInput::make('sulco_movimento')
                                    ->label('Sulco')
                                    ->columnSpan(2)
                                    ->default(0)
                                    ->numeric(),
                                TextInput::make('km_inicial')
                                    ->label('KM Inicial')
                                    ->columnStart(1)
                                    ->columnSpan(3)
                                    ->numeric(),
                                TextInput::make('km_final')
                                    ->label('KM Final')
                                    ->columnSpan(3)
                                    ->numeric(),
                                DatePicker::make('data_inicial')
                                    ->label('Dt. Inicial')
                                    ->columnSpan(4)
                                    ->date('d/m/Y')
                                    ->displayFormat('d/m/Y')
                                    ->closeOnDateSelection()
                                    ->default(now())
                                    ->maxDate(now()),
                                DatePicker::make('data_final')
                                    ->label('Dt. Final')
                                    ->columnSpan(4)
                                    ->date('d/m/Y')
                                    ->displayFormat('d/m/Y')
                                    ->closeOnDateSelection()
                                    ->default(now())
                                    ->maxDate(now()),
                                TextInput::make('observacao')
                                    ->columnSpan(12)
                                    ->default('Registro de movimentação ao cadastrar pneu.'),   
                            ]),
                    ])

            ]);
    }
}
