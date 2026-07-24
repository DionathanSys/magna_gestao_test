<?php

namespace App\Filament\Resources\Agendamentos\Schemas;

use App\Filament\Resources\Parceiros\ParceiroResource;
use App\Filament\Resources\Servicos\ServicoResource;
use App\Models\Servico;
use App\Services;
use App\Services\Servico\ServicoCacheService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
                            ->afterStateUpdated(function (Set $set, $state) {
                                $set('plano_preventivo_id', null);
                            }),
                        DatePicker::make('data_agendamento')
                            ->label('Agendado Para')
                            ->columnSpan(['sm' => 1, 'md' => 1, 'lg' => 2, 'xl' => 2]),
                        DatePicker::make('data_limite')
                            ->label('Dt. Limite')
                            ->after('data_agendamento')
                            ->columnSpan(['sm' => 1, 'md' => 1, 'lg' => 2, 'xl' => 2])
                            ->minDate(fn (Get $get) => $get('data_agendamento')),
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
                            ->options(fn (): array => ServicoCacheService::getServicosForSelect())
                            ->createOptionForm(fn (Schema $schema) => ServicoResource::form($schema))
                            ->createOptionUsing(function (array $data, Schema $schema): int {
                                $servico = Servico::query()->create($data);
                                $schema->model($servico)->saveRelationships();
                                ServicoCacheService::forget($servico->id);

                                return $servico->id;
                            })
                            ->editOptionForm(fn (Schema $schema) => ServicoResource::form($schema))
                            ->getSelectedRecordUsing(fn (Select $component): ?Servico => Servico::query()->find($component->getState()))
                            ->updateOptionUsing(function (array $data, Schema $schema): void {
                                $schema->getRecord()?->update($data);
                                ServicoCacheService::forget($schema->getRecord()?->getKey());
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                $set('controla_posicao', ServicoCacheService::controlaPosicao($state));

                                $set('posicao', null);
                            }),
                        Toggle::make('controla_posicao')
                            ->label('Controla Posição')
                            ->columnSpan(['sm' => 1, 'md' => 1, 'lg' => 1, 'xl' => 2])
                            ->inline(false)
                            ->disabled()
                            ->dehydrated(false)
                            ->live(),
                        Select::make('posicao')
                            ->label('Posição')
                            ->options(function (Get $get): array {
                                $servicoId = $get('servico_id');

                                return ServicoCacheService::getPosicoesForSelect($servicoId);
                            })
                            ->placeholder('Selecione a posição')
                            ->columnSpan(['sm' => 1, 'md' => 1, 'lg' => 1, 'xl' => 2])
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => (bool) $get('controla_posicao'))
                            ->requiredIf('controla_posicao', true)
                            ->dehydrated(fn (Get $get): bool => (bool) $get('controla_posicao')),
                        Select::make('plano_preventivo_id')
                            ->label('Plano Preventivo')
                            ->native(false)
                            ->columnStart(1)
                            ->columnSpanFull()
                            ->options(function (Get $get) {
                                if ($get('veiculo_id')) {
                                    $service = new Services\Agendamento\AgendamentoService;

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
                            ->createOptionForm(fn (Schema $schema) => ParceiroResource::form($schema))
                            ->editOptionForm(fn (Schema $schema) => ParceiroResource::form($schema))
                            ->searchable()
                            ->preload()
                            ->searchPrompt('Buscar Parceiro')
                            ->placeholder('Buscar ...'),

                    ]),
            ]);
    }
}
