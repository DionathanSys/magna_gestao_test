<?php

namespace App\Filament\Resources\ItemInspecaos\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ItemInspecaoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('inspecao_id')
                    ->required()
                    ->numeric(),
                TextInput::make('inspecionavel_type')
                    ->required(),
                TextInput::make('inspecionavel_id')
                    ->required()
                    ->numeric(),
                TextInput::make('observacao'),
                TextInput::make('status')
                    ->required(),
            ]);
    }
}
