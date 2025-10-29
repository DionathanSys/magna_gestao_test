<?php

namespace App\Filament\Resources\OrdemServicos\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use App\Enum;
use App\Filament\Resources\Parceiros\ParceiroResource;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;

class OrdemServicoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Grid::make(['default' => 1,'sm' => 2,'md' => 3])
                    ->schema([
                        Flex::make([
                            Grid::make(['default' => 1,'sm' => 2, 'md' => 4])
                                ->schema([
                                    Components\OrdemServicoVeiculoInput::make()
                                        ->columnSpan(1),
                                    static::getQuilometragemFormField()
                                        ->columnSpan(1),
                                    Components\OrdemServicoTipoManutencaoInput::make()
                                        ->columnSpan(1),
                                    Components\OrdemServicoDataAberturaInput::make()
                                        ->columnSpan(1),
                                    static::getDataFimFormField()
                                        ->columnSpan(1)
                                        ->visibleOn('edit'),
                                    static::getStatusFormField()
                                        ->columnSpan(1),
                                    static::getStatusSankhyaFormField()
                                        ->columnSpan(1),
                                    static::getParceiroIdFormField()
                                        ->columnSpan(2),
                                ])->grow(false),
                            Components\ItensRepeater::make()
                                ->visibleOn('edit'),
                        ])->columnSpanFull(),
                        ])
                    ->columnSpanFull(),

            ]);
    }


    public static function getQuilometragemFormField(): TextInput
    {
        return TextInput::make('quilometragem')
            ->label('Quilometragem')
            ->columnSpan(2)
            ->numeric()
            ->minValue(0)
            ->maxValue(999999)
            ->required();
    }

    public static function getDataFimFormField(): DateTimePicker
    {
        return DateTimePicker::make('data_fim')
            ->label('Dt. Fim')
            ->columnSpan(2)
            ->seconds(false)
            ->maxDate(now());
    }

    public static function getStatusFormField(): Select
    {
        return Select::make('status')
            ->label('Status')
            ->columnSpan(2)
            ->options(Enum\OrdemServico\StatusOrdemServicoEnum::toSelectArray())
            ->default(Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE->value)
            ->required();
    }

    public static function getStatusSankhyaFormField(): Select
    {
        return Select::make('status_sankhya')
            ->label('Sankhya')
            ->columnSpan(2)
            ->options(Enum\OrdemServico\StatusOrdemServicoEnum::toSelectArray())
            ->default(Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE->value)
            ->required();
    }

    public static function getParceiroIdFormField(): Select
    {
        return Select::make('parceiro_id')
            ->label('Parceiro')
            ->columnSpanFull()
            ->relationship('parceiro', 'nome')
            ->createOptionForm(fn(Schema $schema) => ParceiroResource::form($schema))
            ->editOptionForm(fn(Schema $schema) => ParceiroResource::form($schema))
            ->searchable()
            ->preload()
            ->searchPrompt('Buscar Parceiro')
            ->placeholder('Buscar ...');
    }
}
