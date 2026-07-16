<?php

namespace App\Filament\Resources\Veiculos\RelationManagers;

use App\Filament\Resources\VeiculoDocumentos\Schemas\VeiculoDocumentoForm;
use App\Models\VeiculoDocumento;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentosRelationManager extends RelationManager
{
    protected static string $relationship = 'documentos';

    protected static ?string $title = 'Documentos';

    public function form(Schema $schema): Schema
    {
        return VeiculoDocumentoForm::configure($schema, showVeiculo: false);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('data_fim')
            ->columns([
                TextColumn::make('nome')
                    ->label('Documento')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->formatStateUsing(fn (?string $state): string => VeiculoDocumento::tipoOptions()[$state] ?? ($state ?: '-'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('data_inicio')
                    ->label('Início')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('data_fim')
                    ->label('Fim')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('dias_restantes')
                    ->label('Dias Rest.')
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '-' : number_format($state, 0, ',', '.'))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('data_fim', $direction)),
                TextColumn::make('dias_alerta')
                    ->label('Alerta')
                    ->numeric(0, ',', '.')
                    ->suffix(' dias')
                    ->sortable(),
                TextColumn::make('status_documento')
                    ->label('Status')
                    ->badge()
                    ->color(fn (VeiculoDocumento $record): string => $record->getStatusColor()),
                TextColumn::make('anexos')
                    ->label('Anexos')
                    ->formatStateUsing(fn ($state): string => is_array($state) ? (string) count($state) : '0')
                    ->alignCenter(),
            ])
            ->filters([
                Filter::make('vencidos')
                    ->label('Vencidos')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereDate('data_fim', '<', now()->toDateString())),
                Filter::make('em_alerta')
                    ->label('Em alerta')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('data_fim')
                        ->whereDate('data_fim', '>=', now()->toDateString())
                        ->whereRaw('DATEDIFF(data_fim, CURDATE()) <= dias_alerta')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Novo Documento'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
