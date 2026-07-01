<?php

namespace App\Filament\Resources\Pneus\Schemas;

use App\Enum;
use App\Filament\Resources\DesenhoPneus\DesenhoPneuResource;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

class PneuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Wizard::make([
                    Step::make('Carcaça')
                        ->description('Dados principais do pneu e identificação da carcaça.')
                        ->columns(12)
                        ->schema([
                            Components\NumeroFogoInput::make()
                                ->columnSpan(6),
                            Components\CicloVidaInput::make()
                                ->columnSpan(2),
                            TextInput::make('valor')
                                ->label('Valor de Compra')
                                ->columnSpan(4)
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
                            Components\MarcaInput::make()
                                ->columnSpan(4),
                            Components\ModeloInput::make()
                                ->columnSpan(4),
                            Components\MedidaInput::make()
                                ->columnSpan(4)
                                ->default(fn () => \App\Models\PneuMedida::query()->orderBy('codigo')->value('id')),
                            Components\DesenhoPneuInput::make()
                                ->columnSpan(4),
                            TextInput::make('numero_serie')
                                ->label('Nº Série')
                                ->columnStart(1)
                                ->columnSpan(4),
                            TextInput::make('dot')
                                ->label('DOT')
                                ->columnSpan(4),
                        ]),
                    Step::make('Condição Inicial')
                        ->description('Defina como o pneu entra no controle da empresa.')
                        ->columns(12)
                        ->schema([
                            Select::make('status')
                                ->native(false)
                                ->columnSpan(4)
                                ->options(Enum\Pneu\StatusPneuEnum::toSelectArray())
                                ->required()
                                ->default(Enum\Pneu\StatusPneuEnum::DISPONIVEL->value),
                            Select::make('pneu_local_id')
                                ->label('Local')
                                ->native(false)
                                ->columnSpan(4)
                                ->options(\App\Models\PneuLocal::query()->where('ativo', true)->orderBy('nome')->pluck('nome', 'id')->toArray())
                                ->required()
                                ->default(fn () => \App\Models\PneuLocal::query()->where('nome', Enum\Pneu\LocalPneuEnum::ESTOQUE_CCO->value)->value('id')),
                            TextInput::make('sulco_inicial')
                                ->label('Sulco Inicial')
                                ->numeric()
                                ->default(0)
                                ->columnSpan(2),
                            TextInput::make('limite_recapagens')
                                ->label('Lim. Recapagens')
                                ->numeric()
                                ->default(3)
                                ->minValue(0)
                                ->maxValue(9)
                                ->columnSpan(2),
                            Toggle::make('recapavel')
                                ->label('Recapável')
                                ->default(true)
                                ->inline(false)
                                ->columnSpan(3),
                            Toggle::make('registrar_recap_inicial')
                                ->label('Já entra recapado')
                                ->helperText('Use quando o pneu chega recapado e ainda não possui histórico anterior no sistema.')
                                ->default(false)
                                ->live()
                                ->inline(false)
                                ->columnSpan(3),
                            Toggle::make('registrar_historico_inicial')
                                ->label('Registrar histórico inicial')
                                ->helperText('Use apenas quando precisar lançar movimentações antigas manualmente.')
                                ->default(false)
                                ->live()
                                ->inline(false)
                                ->columnSpan(3),
                        ]),
                    Step::make('Recapagem Inicial')
                        ->description('Preencha apenas quando o pneu já entrar recapado na frota.')
                        ->visible(fn (Get $get): bool => (bool) $get('registrar_recap_inicial'))
                        ->columns(12)
                        ->schema([
                            Hidden::make('recap.pneu_id'),
                            DatePicker::make('recap.data_recapagem')
                                ->label('Dt. Recapagem')
                                ->date('d/m/Y')
                                ->columnSpan(4)
                                ->displayFormat('d/m/Y')
                                ->closeOnDateSelection()
                                ->default(now())
                                ->maxDate(now())
                                ->required(fn (Get $get): bool => (bool) $get('../../registrar_recap_inicial')),
                            TextInput::make('recap.valor_recapagem')
                                ->label('Valor da Recapagem')
                                ->columnSpan(4)
                                ->numeric()
                                ->default(0)
                                ->prefix('R$'),
                            Select::make('recap.desenho_pneu_id_recapagem')
                                ->label('Desenho Borracha')
                                ->relationship('desenhoPneu', 'descricao', fn ($query) => $query->where('estado_pneu', Enum\Pneu\EstadoPneuEnum::RECAPADO)->where('ativo', true))
                                ->searchable()
                                ->preload()
                                ->createOptionForm(fn (Schema $schema) => DesenhoPneuResource::form($schema))
                                ->required(fn (Get $get): bool => (bool) $get('../../registrar_recap_inicial'))
                                ->columnSpan(4),
                        ]),
                    Step::make('Histórico Inicial')
                        ->description('Opcional. Lance aqui apenas movimentos antigos que precisam constar no sistema.')
                        ->visible(fn (Get $get): bool => (bool) $get('registrar_historico_inicial'))
                        ->columns(12)
                        ->schema([
                            Repeater::make('historicoMovimentacao')
                                ->label('Movimentações')
                                ->columns(12)
                                ->columnSpanFull()
                                ->defaultItems(0)
                                ->minItems(0)
                                ->compact()
                                ->schema([
                                    Select::make('historico.veiculo_id')
                                        ->label('Veículo')
                                        ->columnSpan(6)
                                        ->required()
                                        ->relationship('veiculo', 'placa')
                                        ->searchable(),
                                    TextInput::make('historico.eixo')
                                        ->columnSpan(2)
                                        ->numeric()
                                        ->required()
                                        ->minValue(1)
                                        ->maxValue(9),
                                    TextInput::make('historico.posicao')
                                        ->columnSpan(4)
                                        ->required(),
                                    Select::make('historico.motivo')
                                        ->columnSpan(4)
                                        ->required()
                                        ->options(Enum\Pneu\MotivoMovimentoPneuEnum::toSelectArray()),
                                    TextInput::make('historico.sulco_movimento')
                                        ->label('Sulco')
                                        ->columnSpan(2)
                                        ->required()
                                        ->default(0)
                                        ->numeric(),
                                    TextInput::make('historico.km_inicial')
                                        ->label('KM Inicial')
                                        ->required()
                                        ->columnSpan(3)
                                        ->numeric(),
                                    TextInput::make('historico.km_final')
                                        ->label('KM Final')
                                        ->required()
                                        ->columnSpan(3)
                                        ->numeric(),
                                    DatePicker::make('historico.data_inicial')
                                        ->label('Dt. Inicial')
                                        ->columnSpan(4)
                                        ->required()
                                        ->date('d/m/Y')
                                        ->displayFormat('d/m/Y')
                                        ->closeOnDateSelection()
                                        ->default(now())
                                        ->maxDate(now()),
                                    DatePicker::make('historico.data_final')
                                        ->label('Dt. Final')
                                        ->columnSpan(4)
                                        ->required()
                                        ->date('d/m/Y')
                                        ->displayFormat('d/m/Y')
                                        ->closeOnDateSelection()
                                        ->default(now())
                                        ->maxDate(now()),
                                    TextInput::make('historico.observacao')
                                        ->columnSpan(12)
                                        ->default('Registro de movimentação ao cadastrar pneu.'),
                                ]),
                        ]),
                ])
                    ->columnSpanFull(),
            ]);
    }
}
