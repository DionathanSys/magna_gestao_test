<?php

namespace App\Filament\Resources\ResultadoPeriodos\RelationManagers;

use App\Enum\Abastecimento\TipoCombustivelEnum;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AbastecimentosRelationManager extends RelationManager
{
    protected static string $relationship = 'abastecimentos';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id_abastecimento')
                    ->required()
                    ->numeric(),
                Select::make('veiculo_id')
                    ->relationship('veiculo', 'id')
                    ->required(),
                TextInput::make('quilometragem')
                    ->required(),
                TextInput::make('posto_combustivel')
                    ->required(),
                Select::make('tipo_combustivel')
                    ->options(TipoCombustivelEnum::class)
                    ->required(),
                TextInput::make('quantidade')
                    ->required()
                    ->numeric(),
                TextInput::make('preco_por_litro')
                    ->required()
                    ->numeric(),
                TextInput::make('preco_total')
                    ->numeric(),
                DateTimePicker::make('data_abastecimento')
                    ->required(),
                Toggle::make('considerar_fechamento')
                    ->required(),
                Toggle::make('considerar_calculo_medio')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('resultado_periodo_id')
            ->columns([
                TextColumn::make('id_abastecimento')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('veiculo.id')
                    ->searchable(),
                TextColumn::make('quilometragem')
                    ->searchable(),
                TextColumn::make('posto_combustivel')
                    ->searchable(),
                TextColumn::make('tipo_combustivel')
                    ->badge()
                    ->searchable(),
                TextColumn::make('quantidade')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('preco_por_litro')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('preco_total')
                    ->numeric()
                    ->sortable()
                    ->summarize(Sum::make()
                        ->money('BRL')
                        ->label('Total Combustível')
                    ),
                TextColumn::make('data_abastecimento')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('considerar_fechamento')
                    ->boolean(),
                IconColumn::make('considerar_calculo_medio')
                    ->boolean(),
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
