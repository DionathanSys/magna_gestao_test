<?php

namespace App\Filament\Resources\Servicos\Schemas;

use App\Enum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ServicoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns([
                    'sm' => 1,
                    'md' => 4,
                    'lg' => 8,
                ])
            ->components([
                TextInput::make('codigo')
                    ->label('Código')
                    ->columnSpan([
                        'sm' => 1,
                        'md' => 1,
                        'lg' => 2
                    ])
                    ->maxLength(10),
                TextInput::make('descricao')
                    ->label('Descrição')
                    ->required()
                    ->columnSpan([
                        'sm' => 1,
                        'md' => 3,
                        'lg' => 6
                    ])
                    ->maxLength(255),
                TextInput::make('complemento')
                    ->columnSpanFull()
                    ->maxLength(255),
                Select::make('tipo')
                    ->columnSpan([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 3
                    ])
                    ->options(Enum\TipoServicoEnum::toSelectArray())
                    ->default(Enum\TipoServicoEnum::CORRETIVA->value),
                Toggle::make('controla_posicao')
                    ->label('Controla Posição')
                    ->columnSpan([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 3
                    ])
                    ->inline(false)
                    ->default(false)
                    ->required(),
                Toggle::make('is_active')
                    ->label('Ativo')
                    ->columnSpan([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 2
                    ])
                    ->inline(false)
                    ->default(true)
                    ->required(),
            ]);
    }
}
