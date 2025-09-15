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
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class ConsertosRelationManager extends RelationManager
{
    protected static string $relationship = 'consertos';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                DatePicker::make('data_conserto')
                    ->columnSpan(4)
                    ->required(),
                TextInput::make('tipo_conserto')
                    ->columnSpan(4)
                    ->required(),
                TextInput::make('valor')
                    ->columnSpan(4)
                    ->required()
                    ->numeric()
                    ->prefix('R$')
                    ->default(0.0),
                Select::make('parceiro_id')
                    ->columnSpan(4)
                    ->relationship('parceiro', 'nome')
                    ->searchable()
                    ->preload(),
                Select::make('veiculo_id')
                    ->columnSpan(4)
                    ->relationship('veiculo', 'placa')
                    ->searchable()
                    ->preload(),
                Toggle::make('garantia')
                    ->columnSpan(4)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('pneu_id')
            ->columns([
                TextColumn::make('data_conserto')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('tipo_conserto')
                    ->label('Tipo'),
                TextColumn::make('parceiro.nome')
                    ->label('Fornecedor')
                    ->placeholder('Não Informado'),
                TextColumn::make('valor')
                    ->money('BRL')
                    ->sortable(),
                IconColumn::make('garantia')
                    ->label('C/ Garantia')
                    ->boolean(),
                TextColumn::make('veiculo.placa')
                    ->label('Veículo')
                    ->placeholder('Não Informado'),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
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
                EditAction::make()
                    ->iconButton(),
                DeleteAction::make()
                    ->iconButton(),
            ], RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
