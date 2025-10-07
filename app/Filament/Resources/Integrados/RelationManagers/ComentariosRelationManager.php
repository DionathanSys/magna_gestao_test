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
            ->description('Comentários relacionados à viagem do integrado')
            ->columns([
                TextColumn::make('veiculo.placa')
                    ->label('Placa')
                    ->sortable(),
                TextColumn::make('conteudo')
                    ->label('Comentário')
                    ->wrap()
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Criador')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
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
