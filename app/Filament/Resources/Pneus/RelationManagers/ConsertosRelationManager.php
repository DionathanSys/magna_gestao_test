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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConsertosRelationManager extends RelationManager
{
    protected static string $relationship = 'consertos';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('data_conserto')
                    ->required(),
                TextInput::make('tipo_conserto')
                    ->required(),
                Select::make('parceiro_id')
                    ->relationship('parceiro', 'id'),
                TextInput::make('valor')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Toggle::make('garantia')
                    ->required(),
                Select::make('veiculo_id')
                    ->relationship('veiculo', 'id'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('pneu_id')
            ->columns([
                TextColumn::make('data_conserto')
                    ->date()
                    ->sortable(),
                TextColumn::make('tipo_conserto')
                    ->searchable(),
                TextColumn::make('parceiro.id')
                    ->searchable(),
                TextColumn::make('valor')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('garantia')
                    ->boolean(),
                TextColumn::make('veiculo.id')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
