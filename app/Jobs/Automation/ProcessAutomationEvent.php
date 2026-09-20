<?php

namespace App\Jobs\Automation;

use App\Enum\Automation\AutomationEventStatus;
use App\Enum\Automation\AutomationJobStatus;
use App\Models\AutomationEvent;
use App\Models\AutomationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessAutomationEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function __construct(
        public readonly int $automationEventId,
    ) {}

    public function handle(): void
    {
        $event = AutomationEvent::query()->findOrFail($this->automationEventId);

        if ($event->processing_status !== AutomationEventStatus::RECEIVED) {
            return;
        }

        $event->update([
            'processing_status' => AutomationEventStatus::PROCESSING,
            'processing_error' => null,
        ]);

        $job = AutomationJob::query()
            ->where('provider_job_id', $event->provider_job_id)
            ->first();

        if (! $job) {
            $this->failEvent($event, 'JOB_NOT_FOUND', 'Job local nao encontrado para o evento recebido.');

            return;
        }

        try {
            $payloadJob = (array) data_get($event->payload, 'job', []);
            $status = AutomationJobStatus::fromProvider((string) ($payloadJob['status'] ?? ''));

            $job->update([
                'status' => $status,
                'provider_attempts' => isset($payloadJob['attempts']) ? (int) $payloadJob['attempts'] : $job->provider_attempts,
                'provider_max_attempts' => isset($payloadJob['max_attempts']) ? (int) $payloadJob['max_attempts'] : $job->provider_max_attempts,
                'progress_current' => data_get($payloadJob, 'progress.current'),
                'progress_total' => data_get($payloadJob, 'progress.total'),
                'progress_message' => data_get($payloadJob, 'progress.message'),
                'started_at' => $payloadJob['started_at'] ?? $job->started_at,
                'finished_at' => $payloadJob['finished_at'] ?? $job->finished_at,
                'result_count' => $payloadJob['result_count'] ?? data_get($event->payload, 'result.count'),
                'result_checksum' => $payloadJob['result_checksum'] ?? data_get($event->payload, 'result.checksum'),
                'error_code' => data_get($payloadJob, 'error.code'),
                'error_message' => data_get($payloadJob, 'error.message'),
                'last_synced_at' => now(),
            ]);

            if ($event->event_type === 'job.completed') {
                $event->update(['processing_status' => AutomationEventStatus::IMPORTING]);

                ImportAutomationResult::dispatch($job->id, $event->id)
                    ->onQueue((string) config('automation.queues.processing', 'automation-import'));

                return;
            }

            $event->update([
                'processing_status' => AutomationEventStatus::PROCESSED,
                'processed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $event->update([
                'processing_status' => AutomationEventStatus::RECEIVED,
                'processing_error' => 'EVENT_PROCESSING_FAILED: '.$exception->getMessage(),
            ]);

            Log::error('Falha ao processar evento da Automation API', [
                'automation_event_id' => $event->id,
                'provider_job_id' => $event->provider_job_id,
                'event_type' => $event->event_type,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $event = AutomationEvent::query()->find($this->automationEventId);

        if ($event) {
            $this->failEvent($event, 'EVENT_PROCESSING_FAILED', $exception?->getMessage() ?? 'Falha ao processar evento.');
        }
    }

    private function failEvent(AutomationEvent $event, string $code, string $message): void
    {
        $event->update([
            'processing_status' => AutomationEventStatus::FAILED,
            'processing_error' => $code.': '.$message,
            'processed_at' => now(),
        ]);
    }
}
