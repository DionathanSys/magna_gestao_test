<?php

namespace App\Filament\Resources\AutomationJobs\Schemas;

use App\Models\AutomationJob;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
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
                        TableColumn::make('Qtd. erros'),
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
                        TextEntry::make('error_count')
                            ->label('Qtd. erros')
                            ->numeric(),
                    ])
                    ->state(function (AutomationJob $record): array {
                        return $record->resultImports()
                            ->orderBy('page_number')
                            ->get()
                            ->map(function ($import): array {
                                return [
                                    'page' => $import->page_number + 1,
                                    'status' => $import->status?->value ?? $import->status,
                                    'records_received' => $import->records_received,
                                    'records_created' => $import->records_created,
                                    'records_updated' => $import->records_updated,
                                    'records_ignored' => $import->records_ignored,
                                    'error_count' => count(self::decodeImportErrors($import->error_message)),
                                ];
                            })
                            ->all();
                    })
                    ->placeholder('Nenhuma página de resultado importada.')
                    ->columnSpanFull(),
                Section::make('Erros de importação')
                    ->description('Cada erro é exibido separadamente para facilitar a conferência e a correção.')
                    ->schema([
                        RepeatableEntry::make('import_errors')
                            ->label('Ocorrências')
                            ->columns(2)
                            ->grid(1)
                            ->schema([
                                TextEntry::make('page')
                                    ->label('Página')
                                    ->numeric(),
                                TextEntry::make('record')
                                    ->label('Registro')
                                    ->numeric()
                                    ->placeholder('-'),
                                TextEntry::make('numero_viagem')
                                    ->label('Viagem')
                                    ->placeholder('-'),
                                TextEntry::make('placa')
                                    ->label('Placa')
                                    ->placeholder('-'),
                                TextEntry::make('unidade_negocio')
                                    ->label('Unidade')
                                    ->placeholder('-'),
                                TextEntry::make('error')
                                    ->label('Detalhe do erro')
                                    ->fontFamily('mono')
                                    ->wrap()
                                    ->columnSpanFull(),
                            ])
                            ->state(function (AutomationJob $record): array {
                                return $record->resultImports()
                                    ->orderBy('page_number')
                                    ->get()
                                    ->flatMap(function ($import): array {
                                        $page = $import->page_number + 1;

                                        return collect(self::decodeImportErrors($import->error_message))
                                            ->map(function (mixed $error) use ($page): array {
                                                $details = is_array($error)
                                                    ? $error
                                                    : ['error' => (string) $error];
                                                $rawMessage = $details['error'] ?? $details;

                                                return [
                                                    'page' => $page,
                                                    'record' => array_key_exists('index', $details)
                                                        ? ((int) $details['index'] + 1)
                                                        : null,
                                                    'numero_viagem' => $details['numero_viagem'] ?? null,
                                                    'placa' => $details['placa'] ?? null,
                                                    'unidade_negocio' => $details['unidade_negocio'] ?? null,
                                                    'error' => is_string($rawMessage)
                                                        ? $rawMessage
                                                        : (json_encode($rawMessage, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'Erro sem detalhe.'),
                                                ];
                                            })
                                            ->all();
                                    })
                                    ->values()
                                    ->all();
                            })
                            ->placeholder('Nenhum erro de importação registrado.')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (AutomationJob $record): bool => $record->resultImports()
                        ->whereNotNull('error_message')
                        ->exists())
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
                TextEntry::make('error_message')
                    ->label('Erro do job')
                    ->fontFamily('mono')
                    ->wrap()
                    ->columnSpanFull(),
                TextEntry::make('requested_at')->dateTime(),
                TextEntry::make('submitted_at')->dateTime(),
                TextEntry::make('started_at')->dateTime(),
                TextEntry::make('finished_at')->dateTime(),
                TextEntry::make('last_synced_at')->dateTime(),
            ]);
    }

    /**
     * @return list<mixed>
     */
    private static function decodeImportErrors(?string $message): array
    {
        if (blank($message)) {
            return [];
        }

        $decoded = json_decode($message, true);

        if (is_array($decoded)) {
            return array_is_list($decoded) ? $decoded : [$decoded];
        }

        return [$message];
    }
}
