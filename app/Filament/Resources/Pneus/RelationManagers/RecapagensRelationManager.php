<?php

namespace App\Filament\Resources\Pneus\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RecapagensRelationManager extends RelationManager
{
    protected static string $relationship = 'recapagens';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('pneu_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('pneu_id')
            ->columns([
                TextColumn::make('pneu_id')
                    ->searchable(),
                TextColumn::make('data_recapagem')
                    ->date('d/m/Y'),
                TextColumn::make('desenho_pneu_id.descricao')
                    ->label('Desenho'),
                TextColumn::make('desenho_pneu_id.modelo')
                    ->label('Modelo'),
                TextColumn::make('valor')
                    ->money('BRL')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->date('d/m/Y H:i'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
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
