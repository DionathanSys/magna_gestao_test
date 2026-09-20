<?php

namespace App\Filament\Resources\AutomationJobs\Schemas;

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
                TextEntry::make('parameters')
                    ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}')
                    ->columnSpanFull(),
                TextEntry::make('progress_current'),
                TextEntry::make('progress_total'),
                TextEntry::make('progress_message'),
                TextEntry::make('submission_attempts'),
                TextEntry::make('provider_attempts'),
                TextEntry::make('result_count'),
                TextEntry::make('result_checksum'),
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
