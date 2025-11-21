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
                TextColumn::make('veiculo_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('numero_viagem')
                    ->searchable(),
                TextColumn::make('documento_transporte')
                    ->searchable(),
                TextColumn::make('km_rodado')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('km_pago')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('km_cadastro')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('km_cobrar')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('motivo_divergencia')
                    ->badge()
                    ->searchable(),
                TextColumn::make('data_competencia')
                    ->date()
                    ->sortable(),
                TextColumn::make('data_inicio')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('data_fim')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('conferido')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('checked_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('km_dispersao')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('dispersao_percentual')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('condutor')
                    ->searchable(),
                IconColumn::make('considerar_relatorio')
                    ->boolean(),
                TextColumn::make('unidade_negocio')
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
