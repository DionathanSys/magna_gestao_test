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
                    ->searchable(),
                TextColumn::make('file_path')
                    ->searchable(),
                TextColumn::make('file_size')
                    ->searchable(),
                TextColumn::make('file_hash')
                    ->searchable(),
                TextColumn::make('import_type')
                    ->searchable(),
                TextColumn::make('import_description')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('total_rows')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('processed_rows')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('success_rows')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('error_rows')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('warning_rows')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('skipped_rows')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_batches')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('processed_batches')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('progress_percentage')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('finished_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('duration_seconds')
                    ->numeric()
                    ->sortable(),
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
