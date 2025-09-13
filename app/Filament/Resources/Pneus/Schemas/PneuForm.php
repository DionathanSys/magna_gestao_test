<?php

namespace App\Filament\Resources\Pneus\Schemas;

use App\Models;
use App\Enum\Pneu\LocalPneuEnum;
use App\Enum\Pneu\StatusPneuEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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
                    ->numeric()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state) {
                        if($state){
                            $pneu = Models\Pneu::query()
                                ->where('numero_fogo', $state)
                                ->first();
                            if($pneu){
                                Notification::make()
                                    ->title('Pneu encontrado')
                                    ->send();
                            }
                        }
                    }),
                Select::make('marca')
                    ->searchable()
                    ->options(db_config('config-pneu.marcas_pneu', [])),
                Select::make('modelo')
                    ->searchable()
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
                    ->relationship('desenhoPneu', 'descricao', fn ($query) => $query->where('estado_pneu', 'NOVO'))
                    ->searchable()
                    ->preload()
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
