<?php

namespace App\Filament\Resources\Integrados\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ComentariosRelationManager extends RelationManager
{
    protected static string $relationship = 'comentarios';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Textarea::make('conteudo')
                //     ->columnSpanFull(),
                // TextInput::make('veiculo.placa')
                //     ->numeric(),
                // TextInput::make('comentavel_type')
                //     ->required(),
                // TextInput::make('comentavel_id')
                //     ->required()
                //     ->numeric(),
                // TextInput::make('created_by')
                //     ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('veiculo.placa')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('conteudo')
                    ->wrap()
                    ->sortable(),
                // TextColumn::make('comentavel_type')
                //     ->searchable(),
                // TextColumn::make('comentavel_id')
                //     ->numeric()
                //     ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
