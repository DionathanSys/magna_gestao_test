<?php

namespace App\Filament\Resources\ResultadoPeriodos\RelationManagers;

use App\Enum\MotivoDivergenciaViagem;
use Carbon\Carbon;
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
                    ->width('1%')
                    ->searchable(),
                TextColumn::make('documento_transporte')
                    ->label('Doc. Transporte')
                    ->width('1%')
                    ->searchable(),
                TextColumn::make('km_rodado')
                    ->label('Km Rodado')
                    ->width('1%')
                    ->numeric(2, ',', '.')
                    ->summarize(
                        Sum::make()
                            ->numeric(2, ',', '.')
                            ->label('Total Km Rodado')
                    )
                    ->sortable(),
                TextColumn::make('km_pago')
                    ->label('Km Pago')
                    ->width('1%')
                    ->numeric(2, ',', '.')
                    ->summarize(
                        Sum::make()
                            ->numeric(2, ',', '.')
                            ->label('Total Km Pago')
                    )
                    ->sortable(),
                TextColumn::make('km_dispersao')
                    ->label('Km Dispersão')
                    ->width('1%')
                    ->numeric(2, ',', '.')
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->numeric(2, ',', '.')
                            ->label('Total Km Dispersão')
                    ),
                TextColumn::make('dispersao_percentual')
                    ->label('Dispersão Percentual')
                    ->width('1%')
                    ->suffix('%')
                    ->numeric(2, ',', '.')
                    ->sortable(),
                TextColumn::make('km_cobrar')
                    ->label('Km Cobrar')
                    ->width('1%')
                    ->numeric(2, ',', '.')
                    ->summarize(
                        Sum::make()
                            ->numeric(2, ',', '.')
                            ->label('Total Km Cobrar')
                    )
                    ->sortable(),
                TextColumn::make('motivo_divergencia')
                    ->label('Motivo Divergência')
                    ->width('1%')
                    ->badge()
                    ->searchable(),
                TextColumn::make('data_competencia')
                    ->label('Dt. Competência')  
                    ->width('1%')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('data_inicio')
                    ->label('Dt. Início')   
                    ->width('1%')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('data_fim')
                    ->label('Dt. Fim')  
                    ->width('1%')
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
                TextColumn::make('creator.name')
                    ->label('Criado por')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updater.name')
                    ->label('Atualizado por')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('checker.name')
                    ->label('Verificado por')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('condutor')
                    ->label('Condutor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('considerar_relatorio')
                    ->label('Considerar Relatório')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('unidade_negocio')
                    ->label('Unidade de Negócio')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make()
                    ->preloadRecordSelect() 
                    ->recordSelectOptionsQuery(
                        fn($query) => $query
                            ->whereNull('resultado_periodo_id') 
                            ->where('veiculo_id', $this->ownerRecord->veiculo_id)
                            ->orderBy('data_competencia', 'desc')
                    )
                    ->recordTitle(
                        fn($record) =>
                        "#{$record->id} | " .
                            Carbon::parse($record->data_competencia)->format('d/m/Y') . " | Nº " .
                            number_format($record->numero_viagem, 0, ',', '.')
                    )
                    ->multiple()
                    ->recordSelectSearchColumns(['id', 'numero_viagem', 'documento_transporte'])
                    ->label('Vincular Viagens'),
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
