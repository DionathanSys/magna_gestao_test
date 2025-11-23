<?php

namespace App\Filament\Resources\ResultadoPeriodos\RelationManagers;

use App\Enum\Abastecimento\TipoCombustivelEnum;
use Carbon\Carbon;
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
                    ->width('1%')
                    ->numeric()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('veiculo.placa')
                    ->label('Veículo')
                    ->width('1%'),
                TextColumn::make('quilometragem')
                    ->numeric(0, ',', '.')
                    ->width('1%'),
                TextColumn::make('posto_combustivel')
                    ->label('Posto Combustível')
                    ->width('1%')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tipo_combustivel')
                    ->label('Tipo Combustível')
                    ->width('1%')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('quantidade')
                    ->numeric(2, ',', '.')
                    ->width('1%')
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->numeric(2, ',', '.')
                            ->label('Total Lts.')
                    ),
                TextColumn::make('preco_por_litro')
                    ->label('Vlr. Litro')
                    ->width('1%')
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('preco_total')
                    ->label('Vlr. Total')
                    ->width('1%')
                    ->sortable()
                    ->money('BRL')
                    ->summarize(
                        Sum::make()
                            ->money('BRL', 100)
                            ->label('Vlr. Total')
                    ),
                TextColumn::make('data_abastecimento')
                    ->label('Dt. Abastecimento')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                IconColumn::make('considerar_fechamento')
                    ->label('Considerar Fechamento')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('considerar_calculo_medio')
                    ->label('Considerar Cálculo Médio')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->defaultSort('data_abastecimento', 'asc')
            ->filters([])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make()
                    ->preloadRecordSelect() 
                    ->recordSelectOptionsQuery(
                        fn($query) => $query
                            ->whereNull('resultado_periodo_id') 
                            ->where('veiculo_id', $this->ownerRecord->veiculo_id)
                            ->orderBy('data_abastecimento', 'desc')
                    )
                    ->recordTitle(
                        fn($record) =>
                        "#{$record->id} | " .
                            Carbon::parse($record->data_abastecimento)->format('d/m/Y H:i') . " | ID: " .
                            number_format($record->id_abastecimento, 0, ',', '.') . " | " .
                            number_format($record->quantidade, 2, ',', '.') . "L | " .
                            "R$ " . number_format($record->preco_total, 2, ',', '.')
                    )
                    ->multiple()
                    ->recordSelectSearchColumns(['id', 'id_abastecimento']) 
                    ->label('Vincular Abastecimentos'),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton(),
                DissociateAction::make()
                    ->iconButton(),
                DeleteAction::make()
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
