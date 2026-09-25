<?php

namespace App\Filament\Resources\AutomationJobs\Pages;

use App\Domain\Automation\Exceptions\AutomationApiException;
use App\Enum\Automation\AutomationJobStatus;
use App\Filament\Resources\AutomationJobs\AutomationJobResource;
use App\Infrastructure\Automation\AutomationApiClient;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Throwable;

class ViewAutomationJob extends ViewRecord
{
    protected static string $resource = AutomationJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('diagnostic')
                ->label('Atualizar diagnóstico')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->visible(fn (): bool => filled($this->record->provider_job_id))
                ->action(function (AutomationApiClient $client): void {
                    try {
                        $response = $client->getJob(
                            (string) $this->record->provider_job_id,
                            (string) $this->record->request_id,
                        );
                        $data = $response['data'] ?? null;

                        if (! is_array($data) || blank($data['status'] ?? null)) {
                            throw new \RuntimeException('Resposta inválida ao consultar status da Automation API.');
                        }

                        $status = AutomationJobStatus::fromProvider((string) $data['status']);
                        $this->record->update([
                            'status' => $status,
                            'progress_current' => data_get($data, 'progress.current'),
                            'progress_total' => data_get($data, 'progress.total'),
                            'progress_message' => data_get($data, 'progress.message'),
                            'started_at' => $data['started_at'] ?? $this->record->started_at,
                            'finished_at' => $data['finished_at'] ?? $this->record->finished_at,
                            'result_count' => $data['result_count'] ?? $this->record->result_count,
                            'error_code' => data_get($data, 'error.code'),
                            'error_message' => data_get($data, 'error.message'),
                            'last_synced_at' => now(),
                        ]);
                        $this->record->refresh();

                        Notification::make()
                            ->success()
                            ->title('Diagnóstico atualizado')
                            ->body('Status atual: '.$status->value.'.')
                            ->send();
                    } catch (AutomationApiException $exception) {
                        $metadata = $this->record->metadata ?? [];

                        if (filled($exception->requestId)) {
                            $metadata['provider_request_id'] = $exception->requestId;
                        }

                        $this->record->update([
                            'error_code' => $exception->errorCode,
                            'error_message' => $exception->getMessage(),
                            'metadata' => $metadata,
                            'last_synced_at' => now(),
                        ]);

                        Notification::make()
                            ->danger()
                            ->title('Falha ao consultar diagnóstico')
                            ->body(implode(' | ', array_filter([
                                $exception->statusCode ? 'HTTP '.$exception->statusCode : null,
                                $exception->errorCode,
                                $exception->getMessage(),
                                $exception->requestId ? 'request_id: '.$exception->requestId : null,
                            ])))
                            ->send();
                    } catch (Throwable $exception) {
                        $this->record->update([
                            'error_code' => 'AUTOMATION_DIAGNOSTIC_FAILED',
                            'error_message' => $exception->getMessage(),
                            'last_synced_at' => now(),
                        ]);

                        Notification::make()
                            ->danger()
                            ->title('Falha ao consultar diagnóstico')
                            ->body($exception->getMessage())
                            ->send();
                    }
                }),
            Action::make('verifyCollector')
                ->label('Verificar collector')
                ->icon('heroicon-o-check-badge')
                ->color('gray')
                ->action(function (AutomationApiClient $client): void {
                    try {
                        $response = $client->getCollectors((string) $this->record->request_id);
                        $collectors = $response['data'] ?? null;

                        if (! is_array($collectors)) {
                            throw new \RuntimeException('Resposta inválida ao listar collectors da Automation API.');
                        }

                        $collector = collect($collectors)->first(fn ($item): bool => is_array($item)
                            && ($item['name'] ?? null) === $this->record->collector);

                        if (! is_array($collector)) {
                            Notification::make()
                                ->danger()
                                ->title('Collector indisponível')
                                ->body($this->record->collector.' não foi retornado por /api/v1/collectors.')
                                ->send();

                            return;
                        }

                        $version = (string) ($collector['version'] ?? 'desconhecida');
                        $schemaVersion = (string) ($collector['schema_version'] ?? data_get($collector, 'schema.version', 'desconhecida'));
                        $expected = $this->record->collector_version.'.'.$this->record->schema_version;
                        $received = $version.'.'.$schemaVersion;
                        $notification = $expected === $received
                            ? Notification::make()->success()
                            : Notification::make()->warning();

                        $notification
                            ->title('Collector encontrado')
                            ->body($this->record->collector.' | versão/schema: '.$received.' | esperado: '.$expected)
                            ->send();
                    } catch (AutomationApiException $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Falha ao verificar collector')
                            ->body(implode(' | ', array_filter([
                                $exception->statusCode ? 'HTTP '.$exception->statusCode : null,
                                $exception->errorCode,
                                $exception->getMessage(),
                                $exception->requestId ? 'request_id: '.$exception->requestId : null,
                            ])))
                            ->send();
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Falha ao verificar collector')
                            ->body($exception->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
