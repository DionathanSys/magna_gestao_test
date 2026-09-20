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
        } catch (AutomationApiException $exception) {
            $job->update([
                'status' => AutomationJobStatus::REQUEST_FAILED,
                'error_code' => $exception->errorCode,
                'error_message' => $exception->getMessage(),
                'submission_retryable' => $exception->retryable,
            ]);

            Log::warning('Falha ao submeter job para a Automation API', [
                'automation_job_id' => $job->id,
                'report_key' => $job->report_key,
                'request_id' => $job->request_id,
                'status_code' => $exception->statusCode,
                'error_code' => $exception->errorCode,
                'retryable' => $exception->retryable,
            ]);

            if ($exception->retryable) {
                throw $exception;
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
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
