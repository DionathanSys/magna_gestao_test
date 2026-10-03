<?php

namespace App\Jobs\Automation;

use App\Domain\Automation\AutomationReportRegistry;
use App\Domain\Automation\AutomationResultImporterRegistry;
use App\Enum\Automation\AutomationEventStatus;
use App\Enum\Automation\AutomationJobStatus;
use App\Enum\Automation\AutomationResultImportStatus;
use App\Infrastructure\Automation\AutomationApiClient;
use App\Models\AutomationEvent;
use App\Models\AutomationJob;
use App\Models\AutomationResultImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class ImportAutomationResult implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly int $automationJobId,
        public readonly ?int $automationEventId = null,
        public readonly bool $force = false,
    ) {}

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(
        AutomationApiClient $client,
        AutomationReportRegistry $reports,
        AutomationResultImporterRegistry $importers,
    ): void {
        $job = AutomationJob::query()->findOrFail($this->automationJobId);

        if (blank($job->provider_job_id)) {
            throw new \RuntimeException('Job sem provider_job_id nao pode importar resultado.');
        }

        if ($this->force) {
            AutomationResultImport::query()
                ->where('automation_job_id', $job->id)
                ->delete();
        }

        $definition = $reports->get($job->report_key);
        $importer = $importers->get($definition);
        $lastCompleted = AutomationResultImport::query()
            ->where('automation_job_id', $job->id)
            ->where('status', AutomationResultImportStatus::COMPLETED)
            ->orderByDesc('page_number')
            ->first();

        $pageNumber = $lastCompleted ? $lastCompleted->page_number + 1 : 0;
        $cursor = $lastCompleted?->next_cursor;

        if ($this->force) {
            $pageNumber = 0;
            $cursor = null;
            $lastCompleted = null;
        }

        if ($lastCompleted && blank($cursor)) {
            $this->finish($job);

            return;
        }

        $import = AutomationResultImport::query()->firstOrNew([
            'automation_job_id' => $job->id,
            'page_number' => $pageNumber,
        ]);

        $import->fill([
            'cursor' => $cursor,
            'status' => AutomationResultImportStatus::PROCESSING,
            'started_at' => $import->started_at ?? now(),
            'error_message' => null,
        ])->save();

        $recordsImportedBeforePage = (int) AutomationResultImport::query()
            ->where('automation_job_id', $job->id)
            ->where('status', AutomationResultImportStatus::COMPLETED)
            ->sum('records_received');

        try {
            $page = $client->getResultPage(
                providerJobId: (string) $job->provider_job_id,
                cursor: $cursor,
                limit: $definition->resultPageLimit,
                requestId: (string) $job->request_id,
            );

            $items = $page['data'] ?? null;
            $meta = $page['meta'] ?? [];

            if (! is_array($items) || ! is_array($meta)) {
                throw new \RuntimeException('Resposta de resultado paginado invalida.');
            }

            $result = $importer->importPage($job->fresh(), array_values($items), $meta);
            if ($result->errors !== []) {
                throw new \RuntimeException('Falha ao importar registros da Automation API: '.json_encode($result->errors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
            $nextCursor = isset($meta['next_cursor']) && $meta['next_cursor'] !== null
                ? (string) $meta['next_cursor']
                : null;
            $total = isset($meta['total']) ? (int) $meta['total'] : $job->progress_total;
            $recordsImported = $recordsImportedBeforePage + $result->recordsReceived;
            $progressCurrent = $nextCursor === null && $total !== null
                ? $total
                : $recordsImported;

            DB::transaction(function () use ($import, $meta, $nextCursor, $result, $job, $total, $progressCurrent): void {
                $import->update([
                    'next_cursor' => $nextCursor,
                    'checksum' => $meta['checksum'] ?? null,
                    'status' => AutomationResultImportStatus::COMPLETED,
                    'records_received' => $result->recordsReceived,
                    'records_created' => $result->recordsCreated,
                    'records_updated' => $result->recordsUpdated,
                    'records_ignored' => $result->recordsIgnored,
                    'error_message' => $result->errors === [] ? null : json_encode($result->errors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'finished_at' => now(),
                ]);

                $job->update([
                    'progress_current' => $progressCurrent,
                    'progress_total' => $total,
                    'result_count' => $total ?? $job->result_count,
                    'result_checksum' => $meta['checksum'] ?? $job->result_checksum,
                    'last_synced_at' => now(),
                ]);
            });

            if ($nextCursor !== null) {
                self::dispatch($job->id, $this->automationEventId, false)
                    ->onQueue((string) config('automation.queues.processing', 'automation-import'));

                return;
            }

            $this->finish($job->fresh());
        } catch (Throwable $exception) {
            $import->update([
                'status' => AutomationResultImportStatus::FAILED,
                'error_message' => $exception->getMessage(),
                'finished_at' => now(),
            ]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $job = AutomationJob::query()->find($this->automationJobId);

        if ($job) {
            $job->update([
                'status' => AutomationJobStatus::FAILED,
                'error_code' => 'RESULT_IMPORT_FAILED',
                'error_message' => $exception?->getMessage() ?? 'Falha ao importar resultado.',
                'finished_at' => now(),
            ]);
        }

        if ($this->automationEventId) {
            AutomationEvent::query()->whereKey($this->automationEventId)->update([
                'processing_status' => AutomationEventStatus::FAILED,
                'processing_error' => $exception?->getMessage() ?? 'Falha ao importar resultado.',
                'processed_at' => now(),
            ]);
        }
    }

    private function finish(AutomationJob $job): void
    {
        $updates = [
            'status' => AutomationJobStatus::COMPLETED,
            'finished_at' => $job->finished_at ?? now(),
            'last_synced_at' => now(),
        ];

        if ($job->progress_total !== null) {
            $updates['progress_current'] = $job->progress_total;
        }

        $job->update($updates);

        if ($this->automationEventId) {
            AutomationEvent::query()->whereKey($this->automationEventId)->update([
                'processing_status' => AutomationEventStatus::PROCESSED,
                'processed_at' => now(),
            ]);
        }
    }
}
