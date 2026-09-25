<?php

namespace App\Jobs\Automation;

use App\Domain\Automation\Exceptions\AutomationApiException;
use App\Enum\Automation\AutomationJobStatus;
use App\Infrastructure\Automation\AutomationApiClient;
use App\Models\AutomationJob;
use App\Models\AutomationResultImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncAutomationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly int $automationJobId,
    ) {}

    public function handle(AutomationApiClient $client): void
    {
        $job = AutomationJob::query()->findOrFail($this->automationJobId);

        if (blank($job->provider_job_id) || $job->status?->isTerminal()) {
            return;
        }

        try {
            $response = $client->getJob((string) $job->provider_job_id, (string) $job->request_id);
            $data = $response['data'] ?? null;

            if (! is_array($data) || blank($data['status'] ?? null)) {
                throw new \RuntimeException('Resposta invalida ao consultar status da Automation API.');
            }

            $status = AutomationJobStatus::fromProvider((string) $data['status']);

            $job->update([
                'status' => $status,
                'provider_attempts' => isset($data['attempts']) ? (int) $data['attempts'] : $job->provider_attempts,
                'provider_max_attempts' => isset($data['max_attempts']) ? (int) $data['max_attempts'] : $job->provider_max_attempts,
                'progress_current' => data_get($data, 'progress.current'),
                'progress_total' => data_get($data, 'progress.total'),
                'progress_message' => data_get($data, 'progress.message'),
                'started_at' => $data['started_at'] ?? $job->started_at,
                'finished_at' => $data['finished_at'] ?? $job->finished_at,
                'result_count' => $data['result_count'] ?? $job->result_count,
                'result_checksum' => $data['result_checksum'] ?? $job->result_checksum,
                'error_code' => data_get($data, 'error.code'),
                'error_message' => data_get($data, 'error.message'),
                'last_synced_at' => now(),
            ]);

            if ($status === AutomationJobStatus::COMPLETED && ! $this->hasCompletedImport($job)) {
                ImportAutomationResult::dispatch($job->id)
                    ->onQueue((string) config('automation.queues.processing', 'automation-import'));
            }
        } catch (AutomationApiException $exception) {
            $metadata = $job->metadata ?? [];

            if (filled($exception->requestId)) {
                $metadata['provider_request_id'] = $exception->requestId;
            }

            Log::warning('Falha ao reconciliar job da Automation API', [
                'automation_job_id' => $job->id,
                'provider_job_id' => $job->provider_job_id,
                'error_code' => $exception->errorCode,
                'retryable' => $exception->retryable,
            ]);

            if ($exception->retryable) {
                throw $exception;
            }

            $job->update([
                'error_code' => $exception->errorCode,
                'error_message' => $exception->getMessage(),
                'metadata' => $metadata,
                'last_synced_at' => now(),
            ]);
        }
    }

    private function hasCompletedImport(AutomationJob $job): bool
    {
        return AutomationResultImport::query()
            ->where('automation_job_id', $job->id)
            ->where('status', 'COMPLETED')
            ->whereNull('next_cursor')
            ->exists();
    }
}
