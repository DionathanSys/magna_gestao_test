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
                    ->options([
                        'APOLLO' => 'APOLLO',
                        'MICHELIN' => 'MICHELIN',
                        'X BRI' => 'X BRI',
                        'GOODYEAR' => 'GOODYEAR',
                        'PIRELLI' => 'PIRELLI',
                        'SPEEDMAX PRIME' => 'SPEEDMAX PRIME',
                        'DUNLOP' => 'DUNLOP',
                        'STRONG TRAC' => 'STRONG TRAC',
                        'CONTINENTAL' => 'CONTINENTAL',
                    ]),
                Select::make('modelo')
                    ->options([
                        'ENDUTRAXMA' => 'ENDUTRAXMA',
                        'KMAX Z' => 'KMAX Z',
                        'X WORKS' => 'X WORKS',
                        'X WORKS Z' => 'X WORKS Z',
                        'FORZA BLOCK' => 'FORZA BLOCK',
                        'DPLUS' => 'DPLUS',
                        'X MULTI Z' => 'X MULTI Z',
                        'X MULTI D' => 'X MULTI D',
                        'MIXMAX A' => 'MIXMAX A',
                        'MIX WORKS' => 'MIX WORKS',
                        'SP320' => 'SP320',
                        'HSR2' => 'HSR2',
                        'FG-01' => 'FG-01',
                        'TG-01' => 'TG-01',
                        'FR-88' => 'FR-88',
                        'TR-01' => 'TR-01',
                        'G686 MSS PLUS' => 'G686 MSS PLUS',
                        'CHD3' => 'CHD3',
                        'R02 PROWAY' => 'R02 PROWAY'
                    ]),
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
