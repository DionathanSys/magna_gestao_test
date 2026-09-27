<?php

namespace App\Filament\Resources\AutomationJobs\Schemas;

use App\Models\AutomationJob;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AutomationJobInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id'),
                TextEntry::make('provider_job_id'),
                TextEntry::make('report_key'),
                TextEntry::make('collector'),
                TextEntry::make('status')->badge(),
                TextEntry::make('source'),
                TextEntry::make('requestedBy.name')->label('Solicitante'),
                TextEntry::make('idempotency_key'),
                TextEntry::make('collector_version'),
                TextEntry::make('schema_version'),
                TextEntry::make('parameters')
                    ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}')
                    ->columnSpanFull(),
                TextEntry::make('metadata')
                    ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}')
                    ->columnSpanFull(),
                TextEntry::make('progress_current'),
                TextEntry::make('progress_total'),
                TextEntry::make('progress_message'),
                TextEntry::make('submission_attempts'),
                TextEntry::make('provider_attempts'),
                TextEntry::make('result_count'),
                TextEntry::make('result_checksum'),
                RepeatableEntry::make('import_diagnostics')
                    ->label('Importação do resultado')
                    ->table([
                        TableColumn::make('Página'),
                        TableColumn::make('Status'),
                        TableColumn::make('Recebidos'),
                        TableColumn::make('Criados'),
                        TableColumn::make('Atualizados'),
                        TableColumn::make('Ignorados'),
                        TableColumn::make('Erros'),
                    ])
                    ->schema([
                        TextEntry::make('page')
                            ->label('Página')
                            ->numeric(),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                        TextEntry::make('records_received')
                            ->label('Recebidos')
                            ->numeric(),
                        TextEntry::make('records_created')
                            ->label('Criados')
                            ->numeric(),
                        TextEntry::make('records_updated')
                            ->label('Atualizados')
                            ->numeric(),
                        TextEntry::make('records_ignored')
                            ->label('Ignorados')
                            ->numeric(),
                        TextEntry::make('error_message')
                            ->label('Erros')
                            ->placeholder('-')
                            ->wrap(),
                    ])
                    ->state(function (AutomationJob $record): array {
                        return $record->resultImports()
                            ->orderBy('page_number')
                            ->get()
                            ->map(fn ($import): array => [
                                'page' => $import->page_number + 1,
                                'status' => $import->status?->value ?? $import->status,
                                'records_received' => $import->records_received,
                                'records_created' => $import->records_created,
                                'records_updated' => $import->records_updated,
                                'records_ignored' => $import->records_ignored,
                                'error_message' => $import->error_message,
                            ])
                            ->all();
                    })
                    ->placeholder('Nenhuma página de resultado importada.')
                    ->columnSpanFull(),
                RepeatableEntry::make('webhook_diagnostics')
                    ->label('Webhooks recebidos')
                    ->table([
                        TableColumn::make('Evento'),
                        TableColumn::make('Status'),
                        TableColumn::make('Erro de processamento'),
                        TableColumn::make('Ocorrido em'),
                    ])
                    ->schema([
                        TextEntry::make('event_type')
                            ->label('Evento'),
                        TextEntry::make('processing_status')
                            ->label('Status')
                            ->badge(),
                        TextEntry::make('processing_error')
                            ->label('Erro de processamento')
                            ->placeholder('-')
                            ->wrap(),
                        TextEntry::make('occurred_at')
                            ->label('Ocorrido em')
                            ->dateTime('d/m/Y H:i:s'),
                    ])
                    ->state(function (AutomationJob $record): array {
                        return $record->events()
                            ->latest('occurred_at')
                            ->get()
                            ->map(fn ($event): array => [
                                'event_type' => $event->event_type,
                                'processing_status' => $event->processing_status?->value ?? $event->processing_status,
                                'processing_error' => $event->processing_error,
                                'occurred_at' => $event->occurred_at,
                            ])
                            ->all();
                    })
                    ->placeholder('Nenhum webhook recebido para este job.')
                    ->columnSpanFull(),
                TextEntry::make('error_code'),
                TextEntry::make('error_message')->columnSpanFull(),
                TextEntry::make('requested_at')->dateTime(),
                TextEntry::make('submitted_at')->dateTime(),
                TextEntry::make('started_at')->dateTime(),
                TextEntry::make('finished_at')->dateTime(),
                TextEntry::make('last_synced_at')->dateTime(),
            ]);
    }
}
