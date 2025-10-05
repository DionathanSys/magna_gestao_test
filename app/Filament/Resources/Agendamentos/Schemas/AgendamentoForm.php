<?php

namespace App\Filament\Resources\Agendamentos\Schemas;

use App\{Models, Services, Enum};
use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Filament\Resources\Parceiros\ParceiroResource;
use App\Filament\Resources\Servicos\ServicoResource;
use App\Filament\Tables\Teste;
use App\Models\Parceiro;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\ModalTableSelect;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AgendamentoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Informações do Agendamento')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 4, 'xl' => 8])
                    ->columnSpanFull()
                    ->schema([
                        Select::make('veiculo_id')
                            ->label('Veículo')
                            ->autofocus()
                            ->native(false)
                            ->columnSpan(['sm' => 1, 'md' => 1, 'lg' => 2, 'xl' => 2])
                            ->relationship('veiculo', 'placa')
                            ->searchable()
                            ->searchPrompt('Buscar Veículo')
                            ->placeholder('Buscar ...')
                            ->required()
                            ->afterStateUpdatedJs(<<<'JS'
                                $set('ordem_servico_id', (null))
                            JS),
                        DatePicker::make('data_agendamento')
                            ->label('Agendado Para')
                            ->columnSpan(['sm' => 1, 'md' => 1, 'lg' => 2, 'xl' => 2])
                            ->minDate(now()),
                        DatePicker::make('data_limite')
                            ->label('Dt. Limite')
                            ->after('data_agendamento')
                            ->columnSpan(['sm' => 1, 'md' => 1, 'lg' => 2, 'xl' => 2]),
                        DatePicker::make('data_realizado')
                            ->label('Realizado Em')
                            ->columnSpan(['sm' => 1, 'md' => 1, 'lg' => 2, 'xl' => 2])
                            ->afterOrEqual('data_agendamento')
                            ->maxDate(now()),
                    ]),
                Section::make('Detalhes do Agendamento')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 4, 'xl' => 8])
                    ->columnSpanFull()
                    ->schema([
                        Select::make('servico_id')
                            ->label('Serviço')
                            ->columnStart(1)
                            ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2, 'xl' => 4])
                            ->required()
                            ->relationship('servico', 'descricao')
                            ->createOptionForm(fn(Schema $schema) => ServicoResource::form($schema))
                            ->editOptionForm(fn(Schema $schema) => ServicoResource::form($schema))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state) {
                                    $servico = \App\Models\Servico::find($state);
                                    $set('controla_posicao', $servico?->controla_posicao ? true : false);
                                } else {
                                    $set('controla_posicao', false);
                                }
                            }),
                        Toggle::make('controla_posicao')
                            ->label('Controla Posição')
                            ->columnSpan(['sm' => 1, 'md' => 1, 'lg' => 1, 'xl' => 2])
                            ->inline(false)
                            ->disabled()
                            ->live(),
                        TextInput::make('posicao')
                            ->label('Posição')
                            ->columnSpan(['sm' => 1, 'md' => 1, 'lg' => 1, 'xl' => 2])
                            ->requiredIf('controla_posicao', true)
                            ->minLength(2)
                            ->maxLength(8),
                        Select::make('plano_preventivo_id')
                            ->label('Plano Preventivo')
                            ->native(false)
                            ->columnStart(1)
                            ->columnSpanFull()
                            ->options(function (Get $get) {
                                if ($get('veiculo_id')) {
                                    $service = new Services\Agendamento\AgendamentoService();
                                    return $service->getPlanosPreventivosByVeiculo($get('veiculo_id'));
                                }
                            })
                            ->preload()
                            ->live(),
                        Textarea::make('observacao')
                            ->label('Observação')
                            ->columnSpanFull()
                            ->rows(3)
                            ->maxLength(255),
                        Select::make('parceiro_id')
                            ->label('Parceiro')
                            ->columnSpanFull()
                            ->relationship('parceiro', 'nome')
                            ->createOptionForm(fn(Schema $schema) => ParceiroResource::form($schema))
                            ->editOptionForm(fn(Schema $schema) => ParceiroResource::form($schema))
                            ->searchable()
                            ->preload()
                            ->searchPrompt('Buscar Parceiro')
                            ->placeholder('Buscar ...'),
                        ModalTableSelect::make('ordem_servico_id')
                            ->relationship('ordemServico', 'id')
                            ->columnSpan(6)
                            ->tableConfiguration(Teste::class)
                            ->tableArguments(function (Get $get) {
                                if ($get('veiculo_id')) {
                                    return [
                                        'veiculo_id' => $get('veiculo_id')
                                    ];
                                }
                                return [];
                            })

                    ]),
            ]);
    }
}
