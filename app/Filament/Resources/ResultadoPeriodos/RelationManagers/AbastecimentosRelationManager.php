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
                    ->label('ID Abastecimento')
                    ->numeric()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('veiculo.placa')
                    ->label('Veículo'),
                TextColumn::make('quilometragem')
                    ->numeric(0, ',', '.'),
                TextColumn::make('posto_combustivel')
                    ->label('Posto Combustível'),
                TextColumn::make('tipo_combustivel')
                    ->label('Tipo Combustível')
                    ->badge(),
                TextColumn::make('quantidade')
                    ->numeric(2, ',', '.')
                    ->sortable()
                    ->summarize(Sum::make()
                        ->numeric(2, ',', '.')
                        ->label('Total Lts.')
                    ),
                TextColumn::make('preco_por_litro')
                    ->label('Vlr. Litro')
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('preco_total')
                    ->label('Vlr. Total')
                    ->sortable()
                    ->money('BRL')
                    ->summarize(Sum::make()
                        ->money('BRL', 100)
                        ->label('Vlr. Total')
                    ),
                TextColumn::make('data_abastecimento')
                    ->label('Dt. Abastecimento')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                IconColumn::make('considerar_fechamento')
                    ->label('Considerar Fechamento')
                    ->boolean(),
                IconColumn::make('considerar_calculo_medio')
                    ->label('Considerar Cálculo Médio')
                    ->boolean(),
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
