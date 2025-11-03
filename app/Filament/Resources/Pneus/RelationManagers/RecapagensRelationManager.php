<?php

namespace App\Filament\Resources\Pneus\RelationManagers;

use App\{Models, Enum};
use App\Filament\Resources\DesenhoPneus\DesenhoPneuResource;
use Filament\Actions\{BulkActionGroup, CreateAction, DeleteAction, EditAction, DeleteBulkAction};
use Filament\Forms\Components\{DatePicker, Select, TextInput};
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RecapagensRelationManager extends RelationManager
{
    protected static string $relationship = 'recapagens';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('data_recapagem')
                    ->date('d/m/Y')
                    ->displayFormat('d/m/Y')
                    ->closeOnDateSelection()
                    ->maxDate(now())
                    ->required(),
                Select::make('desenho_pneu_id')
                    ->label('Desenho do Pneu')
                    ->relationship('desenhoPneu', 'descricao')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->createOptionForm(fn (Schema $schema) => DesenhoPneuResource::form($schema)),
                TextInput::make('valor')
                    ->label('Valor')
                    ->numeric()
                    ->default(0)
                    ->prefix('R$'),
                TextInput::make('ciclo_vida')
                    ->label('Ciclo de Vida')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->maxValue(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('pneu_id')
            ->columns([
                TextColumn::make('pneu_id')
                    ->searchable()
                    ->width('1%'),
                TextColumn::make('data_recapagem')
                    ->date('d/m/Y')
                    ->width('1%'),
                TextColumn::make('pneu.modelo')
                    ->label('Modelo')
                    ->width('1%'),
                TextColumn::make('desenhoPneu.descricao')
                    ->label('Desenho')
                    ->width('1%'),
                TextColumn::make('desenhoPneu.modelo')
                    ->label('Modelo')
                    ->width('1%'),
                TextInputColumn::make('ciclo_vida')
                    ->label('Ciclo de Vida')
                    ->width('1%'),
                TextColumn::make('valor')
                    ->money('BRL')
                    ->searchable()
                    ->width('1%'),
                TextColumn::make('created_at')
                    ->date('d/m/Y H:i'),
            ])
            ->groups([
                Group::make('pneu.numero_fogo')
                ->label('Nº Fogo'),
            ])
            ->filters([
                SelectFilter::make('pneu_id')
                    ->label('Pneu')
                    ->relationship('pneu', 'numero_fogo')
                    ->multiple()
                    ->searchable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton(),
                DeleteAction::make()
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
