<?php

namespace App\Filament\Resources\AutomationJobs\Schemas;

use App\Models\AutomationJob;
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
                TextEntry::make('import_summary')
                    ->label('Importação do resultado')
                    ->state(function (AutomationJob $record): string {
                        $imports = $record->resultImports()
                            ->orderBy('page_number')
                            ->get();

                        if ($imports->isEmpty()) {
                            return 'Nenhuma página de resultado importada.';
                        }

                        return $imports->map(function ($import): string {
                            $summary = sprintf(
                                'Página %d [%s]: recebidos=%d, criados=%d, atualizados=%d, ignorados=%d',
                                $import->page_number + 1,
                                $import->status?->value ?? $import->status,
                                $import->records_received,
                                $import->records_created,
                                $import->records_updated,
                                $import->records_ignored,
                            );

                            return $import->error_message
                                ? $summary.' | erro: '.$import->error_message
                                : $summary;
                        })->implode("\n");
                    })
                    ->columnSpanFull(),
                TextEntry::make('webhook_summary')
                    ->label('Webhooks recebidos')
                    ->state(function (AutomationJob $record): string {
                        $events = $record->events()
                            ->latest('occurred_at')
                            ->get();

                        if ($events->isEmpty()) {
                            return 'Nenhum webhook recebido para este job.';
                        }

                        return $events->map(fn ($event): string => implode(' | ', array_filter([
                            $event->event_type,
                            $event->processing_status?->value ?? $event->processing_status,
                            $event->processing_error,
                        ])))->implode("\n");
                    })
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
