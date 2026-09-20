<?php

namespace App\Filament\Resources\AutomationJobs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AutomationJobsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('report_key')
                    ->label('Relatório')
                    ->searchable(),
                TextColumn::make('source')
                    ->label('Origem')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('requestedBy.name')
                    ->label('Solicitante')
                    ->placeholder('Sistema'),
                TextColumn::make('progress_current')
                    ->label('Progresso')
                    ->formatStateUsing(fn ($state, $record): string => $record->progress_total
                        ? $state.'/'.$record->progress_total
                        : (string) ($state ?? '-')),
                TextColumn::make('submission_attempts')
                    ->label('Envios')
                    ->numeric(),
                TextColumn::make('requested_at')
                    ->label('Solicitado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('finished_at')
                    ->label('Finalizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('error_message')
                    ->label('Erro')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                ViewAction::make(),
            ])
            ->poll('10s');
    }
}
