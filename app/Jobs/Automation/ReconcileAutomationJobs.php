<?php

namespace App\Jobs\Automation;

use App\Enum\Automation\AutomationJobStatus;
use App\Models\AutomationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ReconcileAutomationJobs implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function handle(): void
    {
        AutomationJob::query()
            ->whereIn('status', [
                AutomationJobStatus::PENDING_SUBMISSION,
                AutomationJobStatus::REQUEST_FAILED,
            ])
            ->where(function ($query): void {
                $query->where('status', AutomationJobStatus::PENDING_SUBMISSION)
                    ->orWhere(function ($query): void {
                        $query->where('status', AutomationJobStatus::REQUEST_FAILED)
                            ->where('submission_retryable', true);
                    });
            })
            ->where(function ($query): void {
                $query->whereNull('last_submission_at')
                    ->orWhere('last_submission_at', '<=', now()->subMinutes(5));
            })
            ->limit(50)
            ->pluck('id')
            ->each(function (int $jobId): void {
                SubmitAutomationJob::dispatch($jobId)
                    ->onQueue((string) config('automation.queues.submission', 'automation'));
            });

        AutomationJob::query()
            ->whereIn('status', [
                AutomationJobStatus::QUEUED,
                AutomationJobStatus::RUNNING,
                AutomationJobStatus::RETRYING,
            ])
            ->whereNotNull('provider_job_id')
            ->where(function ($query): void {
                $query->whereNull('last_synced_at')
                    ->orWhere('last_synced_at', '<=', now()->subMinutes(5));
            })
            ->limit(50)
            ->pluck('id')
            ->each(function (int $jobId): void {
                SyncAutomationJob::dispatch($jobId)
                    ->onQueue((string) config('automation.queues.processing', 'automation-import'));
            });
    }
}
