<?php

namespace App\Filament\Resources\ResultadoPeriodos\RelationManagers;

use App\Enum\MotivoDivergenciaViagem;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
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

class ViagensRelationManager extends RelationManager
{
    protected static string $relationship = 'viagens';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('veiculo_id')
                    ->required()
                    ->numeric(),
                TextInput::make('numero_viagem')
                    ->required(),
                TextInput::make('documento_transporte'),
                TextInput::make('km_rodado')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('km_pago')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('km_cadastro')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('km_cobrar')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Select::make('motivo_divergencia')
                    ->options(MotivoDivergenciaViagem::class),
                DatePicker::make('data_competencia')
                    ->required(),
                DateTimePicker::make('data_inicio')
                    ->required(),
                DateTimePicker::make('data_fim')
                    ->required(),
                Toggle::make('conferido')
                    ->required(),
                TextInput::make('divergencias'),
                TextInput::make('created_by')
                    ->numeric(),
                TextInput::make('updated_by')
                    ->numeric(),
                TextInput::make('checked_by')
                    ->numeric(),
                TextInput::make('km_dispersao')
                    ->numeric(),
                TextInput::make('dispersao_percentual')
                    ->numeric(),
                TextInput::make('condutor'),
                Toggle::make('considerar_relatorio')
                    ->required(),
                TextInput::make('unidade_negocio'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('resultado_periodo_id')
            ->columns([
                TextColumn::make('numero_viagem')
                    ->label('Nº Viagem')
                    ->searchable(),
                TextColumn::make('documento_transporte')
                    ->label('Doc. Transporte')
                    ->searchable(),
                TextColumn::make('km_rodado')
                    ->label('Km Rodado')
                    ->numeric(2, ',', '.')
                    ->summarize(
                        Sum::make()
                            ->numeric(2, ',', '.')
                            ->label('Total Km Rodado')
                    )
                    ->sortable(),
                TextColumn::make('km_pago')
                    ->label('Km Pago')
                    ->numeric(2, ',', '.')
                    ->summarize(
                        Sum::make()
                            ->numeric(2, ',', '.')
                            ->label('Total Km Pago')
                    )
                    ->sortable(),
                TextColumn::make('km_dispersao')
                    ->label('Km Dispersão')
                    ->numeric(2, ',', '.')
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->numeric(2, ',', '.')
                            ->label('Total Km Dispersão')
                    ),
                TextColumn::make('dispersao_percentual')
                    ->label('Dispersão Percentual')
                    ->suffix('%')
                    ->numeric(2, ',', '.')
                    ->sortable(),
                TextColumn::make('km_cobrar')
                    ->label('Km Cobrar')
                    ->numeric(2, ',', '.')
                    ->summarize(
                        Sum::make()
                            ->numeric(2, ',', '.')
                            ->label('Total Km Cobrar')
                    )
                    ->sortable(),
                TextColumn::make('motivo_divergencia')
                    ->label('Motivo Divergência')
                    ->badge()
                    ->searchable(),
                TextColumn::make('data_competencia')
                    ->label('Dt. Competência')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('data_inicio')
                    ->label('Dt. Início')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('data_fim')
                    ->label('Dt. Fim')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                IconColumn::make('conferido')
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
                TextColumn::make('created_by')
                    ->label('Criado por')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_by')
                    ->label('Atualizado por')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('checked_by')
                    ->label('Verificado por')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('condutor')
                    ->label('Condutor')
                    ->searchable(),
                IconColumn::make('considerar_relatorio')
                    ->label('Considerar Relatório')
                    ->boolean(),
                TextColumn::make('unidade_negocio')
                    ->label('Unidade de Negócio')
                    ->searchable(),
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
