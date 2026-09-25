<?php

namespace App\Jobs\Automation;

use App\Domain\Automation\AutomationReportRegistry;
use App\Domain\Automation\Exceptions\AutomationApiException;
use App\Enum\Automation\AutomationJobStatus;
use App\Infrastructure\Automation\AutomationApiClient;
use App\Models\AutomationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SubmitAutomationJob implements ShouldQueue
{
    use Queueable;

    public int $tries;

    public function __construct(
        public readonly int $automationJobId,
    ) {
        $this->tries = max(1, (int) config('automation.api.retry_times', 3));
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(
        AutomationApiClient $client,
        AutomationReportRegistry $reports,
    ): void {
        $job = AutomationJob::query()->findOrFail($this->automationJobId);

        Log::info('Iniciando submissao de job para a Automation API', [
            'automation_job_id' => $job->id,
            'request_id' => $job->request_id,
            'report_key' => $job->report_key,
            'status' => $job->status?->value,
        ]);

        if (! $job->isAwaitingSubmission() || filled($job->provider_job_id)) {
            return;
        }

        if (! (bool) config('automation.enabled', false)) {
            $job->update([
                'status' => AutomationJobStatus::REQUEST_FAILED,
                'submission_retryable' => false,
                'error_code' => 'AUTOMATION_DISABLED',
                'error_message' => 'A integracao com a Automation API esta desativada.',
            ]);

            Log::error('Submissao de job cancelada: integracao com a Automation API desativada', [
                'automation_job_id' => $job->id,
                'request_id' => $job->request_id,
                'error_code' => 'AUTOMATION_DISABLED',
            ]);

            return;
        }

        $definition = $reports->get($job->report_key);

        $job->increment('submission_attempts');
        $job->update([
            'last_submission_at' => now(),
            'error_code' => null,
            'error_message' => null,
        ]);

        try {
            $response = $client->createJob($job->fresh(), $definition);
            $status = AutomationJobStatus::fromProvider((string) $response['status']);

            $job->update([
                'provider_job_id' => (string) $response['id'],
                'collector_version' => $response['collector_version'] ?? $definition->collectorVersion,
                'status' => $status,
                'submitted_at' => now(),
                'last_synced_at' => now(),
                'submission_retryable' => null,
                'provider_attempts' => isset($response['attempts']) ? (int) $response['attempts'] : null,
                'provider_max_attempts' => isset($response['max_attempts']) ? (int) $response['max_attempts'] : null,
            ]);

            SyncAutomationJob::dispatch($job->id)
                ->onQueue((string) config('automation.queues.processing', 'automation-import'));

            Log::info('Job submetido com sucesso para a Automation API', [
                'automation_job_id' => $job->id,
                'provider_job_id' => $job->provider_job_id,
                'request_id' => $job->request_id,
                'status' => $status->value,
            ]);
        } catch (AutomationApiException $exception) {
            $metadata = $job->metadata ?? [];

            if (filled($exception->requestId)) {
                $metadata['provider_request_id'] = $exception->requestId;
            }

            $job->update([
                'status' => AutomationJobStatus::REQUEST_FAILED,
                'error_code' => $exception->errorCode,
                'error_message' => $exception->getMessage(),
                'submission_retryable' => $exception->retryable,
                'metadata' => $metadata,
            ]);

            Log::error('Falha ao submeter job para a Automation API', [
                'automation_job_id' => $job->id,
                'report_key' => $job->report_key,
                'collector' => $definition->collector,
                'request_id' => $job->request_id,
                'status_code' => $exception->statusCode,
                'error_code' => $exception->errorCode,
                'error' => $exception->getMessage(),
                'provider_request_id' => $exception->requestId,
                'retryable' => $exception->retryable,
            ]);

            if ($exception->retryable) {
                throw $exception;
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Falha definitiva ao submeter job para a Automation API', [
            'automation_job_id' => $this->automationJobId,
            'error' => $exception?->getMessage(),
        ]);

        AutomationJob::query()
            ->whereKey($this->automationJobId)
            ->update([
                'status' => AutomationJobStatus::REQUEST_FAILED,
                'submission_retryable' => true,
                'error_code' => 'AUTOMATION_SUBMISSION_FAILED',
                'error_message' => $exception?->getMessage() ?? 'Falha ao submeter job.',
            ]);
    }
}
