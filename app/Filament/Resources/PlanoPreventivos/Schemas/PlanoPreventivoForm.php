<?php

namespace App\Filament\Resources\PlanoPreventivos\Schemas;

use App\Services\Servico\ServicoCacheService;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PlanoPreventivoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Dados do plano')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('descricao')
                            ->label('Descrição')
                            ->columnSpan(['sm' => 1, 'md' => 6, 'lg' => 7])
                            ->required()
                            ->maxLength(255),
                        TextInput::make('periodicidade')
                            ->label('Periodicidade')
                            ->columnSpan(['sm' => 1, 'md' => 3, 'lg' => 2])
                            ->maxLength(100)
                            ->helperText('Informação de referência do plano.'),
                        TextInput::make('intervalo')
                            ->label('Intervalo entre execuções')
                            ->columnSpan(['sm' => 1, 'md' => 3, 'lg' => 3])
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->suffix('km')
                            ->required()
                            ->helperText('Usado para calcular a próxima manutenção.'),
                        Toggle::make('is_active')
                            ->label('Plano ativo')
                            ->columnSpan(['sm' => 1, 'md' => 3, 'lg' => 2])
                            ->inline(false)
                            ->default(true)
                            ->required(),
                    ]),
                Section::make('Itens do plano')
                    ->description('Os serviços abaixo serão incluídos na OS quando o plano for vinculado.')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('itens')
                            ->label('Serviços preventivos')
                            ->defaultItems(0)
                            ->addActionLabel('Adicionar serviço')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => ServicoCacheService::getServicoLabel($state['servico_id'] ?? null))
                            ->schema([
                                Select::make('servico_id')
                                    ->label('Serviço')
                                    ->options(ServicoCacheService::getServicosForSelect())
                                    ->searchable()
                                    ->preload()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->distinct()
                                    ->exists('servicos', 'id')
                                    ->required(),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
