<?php

namespace App\Filament\Resources\Veiculos\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use planos_preventivo;

class PlanoPreventivoRelationManager extends RelationManager
{
    protected static string $relationship = 'planoPreventivo';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('descricao')
                    ->required(),
                TextInput::make('periodicidade'),
                TextInput::make('intervalo')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('itens'),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('descricao'),
                TextEntry::make('periodicidade'),
                TextEntry::make('intervalo')
                    ->numeric(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->dateTime(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('periodicidade')
                    ->label('Periodicidade')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('intervalo')
                    ->label('Intervalo (km)')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Ativo?')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('itens')
                    ->label('Itens')
                    ->getStateUsing(function ($record) {
                        $itens = json_decode($record->itens) ?? [];
                        return $itens;
                    })
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->color('danger')
                    ->placeholder('Nenhum item'),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
            ]);
    }

}
