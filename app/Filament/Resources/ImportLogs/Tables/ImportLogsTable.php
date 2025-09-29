<?php

namespace App\Filament\Resources\ImportLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImportLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file_name')
                    ->label('Arquivo')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('import_type')
                    ->label('Tipo de Importação')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('import_description')
                    ->label('Descrição')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Usuário')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('errors')
                    ->label('Erros')
                    ->getStateUsing(function ($record) {
                        // Verificar se já é um array ou se precisa decodificar
                        if (is_array($record->errors)) {
                            $errors = $record->errors;
                        } elseif (is_string($record->errors)) {
                            $errors = json_decode($record->errors, true) ?? [];
                        } else {
                            $errors = [];
                        }

                        return count($errors);
                    })
                    ->numeric()
                    ->color('danger')
                    ->placeholder('0'),
                TextColumn::make('total_rows')
                    ->label('Total de Linhas')
                    ->numeric(0, ',', '.')
                    ->sortable(),
                TextColumn::make('processed_rows')
                    ->label('Linhas Processadas')
                    ->numeric(0, ',', '.')
                    ->sortable(),
                TextColumn::make('success_rows')
                    ->label('Linhas com Sucesso')
                    ->numeric(0, ',', '.')
                    ->sortable(),
                TextColumn::make('error_rows')
                    ->label('Linhas com Erros')
                    ->numeric(0, ',', '.')
                    ->sortable(),
                TextColumn::make('warning_rows')
                    ->label('Linhas com Avisos')
                    ->numeric(0, ',', '.')
                    ->sortable(),
                TextColumn::make('skipped_rows')
                    ->label('Linhas Ignoradas')
                    ->numeric(0, ',', '.')
                    ->sortable(),
                TextColumn::make('total_batches')
                    ->label('Total de Batches')
                    ->numeric(0, ',', '.')
                    ->sortable(),
                TextColumn::make('processed_batches')
                    ->label('Batches Processados')
                    ->numeric(0, ',', '.')
                    ->sortable(),
                TextColumn::make('progress_percentage')
                    ->label('Progresso (%)')
                    ->suffix('%')
                    ->numeric(0, ',', '.')
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label('Iniciado em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('finished_at')
                    ->label('Finalizado em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('duration_seconds')
                    ->label('Duração (s)')
                    ->numeric(0, ',', '.')
                    ->sortable(),
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
            ->reorderableColumns()
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
