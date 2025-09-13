<?php

namespace App\Filament\Resources\Pneus\Schemas;

use App\Enum\Pneu\LocalPneuEnum;
use App\Enum\Pneu\StatusPneuEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PneuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([
                TextInput::make('numero_fogo')
                    ->label('Nº de Fogo')
                    ->required()
                    ->maxLength(255),
                Select::make('marca')
                    ->options(db_config('config-pneu.marcas_pneu', [])),
                Select::make('modelo')
                    ->options(db_config('config-pneu.modelos_pneu', [])),
                Select::make('medida')
                    ->options([
                        '275/80 R22.5' => '275/80 R22.5',
                        '295/80 R22.5' => '295/80 R22.5',
                    ])
                    ->default('275/80 R22.5'),
                TextInput::make('ciclo_vida')
                    ->label('Vida')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(3),
                TextInput::make('valor')
                    ->label('Valor')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->prefix('R$'),
                Select::make('desenho_pneu_id')
                    ->label('Desenho Borracha')
                    ->relationship('desenhoPneu', 'descricao')
                    ->searchable()
                    ->required()
                    // ->createOptionForm(fn(Schema $schema) => DesenhoPneuResource::form($schema))
                    ,
                Select::make('status')
                    ->options(StatusPneuEnum::toSelectArray())
                    ->required()
                    ->default(StatusPneuEnum::DISPONIVEL->value),
                Select::make('local')
                    ->options(LocalPneuEnum::toSelectArray())
                    ->required()
                    ->default(LocalPneuEnum::ESTOQUE_CCO->value),
                DatePicker::make('data_aquisicao')
                    ->label('Dt. Aquisição')
                    ->default(now())
                    ->maxDate(now())
                    ->required(),
            ]);
    }
}
