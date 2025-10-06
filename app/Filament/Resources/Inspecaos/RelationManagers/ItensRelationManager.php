<?php

namespace App\Filament\Resources\Inspecaos\RelationManagers;

use App\Filament\Resources\ItemInspecaos\Tables\ItemInspecaosTable as TablesItemInspecaosTable;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use ItemInspecaosTable;

class ItensRelationManager extends RelationManager
{
    protected static string $relationship = 'itens';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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

    public function table(Table $table): Table
    {
        return TablesItemInspecaosTable::configure($table);
    }
}
