<?php

namespace App\Domain\Automation\Actions;

use App\Domain\Automation\AutomationReportRegistry;
use App\Domain\Automation\Data\AutomationJobRequest;
use App\Domain\Automation\Exceptions\AutomationIdempotencyConflictException;
use App\Enum\Automation\AutomationJobStatus;
use App\Jobs\Automation\SubmitAutomationJob;
use App\Models\AutomationJob;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RequestAutomationJob
{
    public function __construct(
        private readonly AutomationReportRegistry $reports,
    ) {}

    public function handle(AutomationJobRequest $request): AutomationJob
    {
        $definition = $this->reports->get($request->reportKey);
        $this->reports->validateParameters($definition, $request->parameters);

        $idempotencyKey = $request->idempotencyKey ?: (string) Str::ulid();
        $fingerprint = $this->fingerprint($request);
        $existing = AutomationJob::query()->where('idempotency_key', $idempotencyKey)->first();

        if ($existing) {
            $this->assertSameRequest($existing, $fingerprint, $idempotencyKey);
            $this->resubmitIfNecessary($existing);

            return $existing;
        }

        try {
            return DB::transaction(function () use ($request, $definition, $idempotencyKey, $fingerprint): AutomationJob {
                $job = AutomationJob::query()->create([
                    'report_key' => $request->reportKey,
                    'collector' => $definition->collector,
                    'collector_version' => $definition->collectorVersion,
                    'schema_version' => $definition->schemaVersion,
                    'status' => AutomationJobStatus::PENDING_SUBMISSION,
                    'source' => $request->source,
                    'parameters' => $request->parameters,
                    'metadata' => $request->metadata,
                    'requested_by_user_id' => $request->requestedByUserId,
                    'idempotency_key' => $idempotencyKey,
                    'request_fingerprint' => $fingerprint,
                    'request_id' => (string) Str::ulid(),
                    'requested_at' => now(),
                ]);

                $queue = (string) config('automation.queues.submission', 'automation');

                SubmitAutomationJob::dispatch($job->id)
                    ->onQueue($queue)
                    ->afterCommit();

                Log::info('Job de automacao enfileirado para submissao', [
                    'automation_job_id' => $job->id,
                    'queue' => $queue,
                    'request_id' => $job->request_id,
                    'report_key' => $job->report_key,
                ]);

                return $job;
            });
        } catch (QueryException $exception) {
            $existing = AutomationJob::query()->where('idempotency_key', $idempotencyKey)->first();

            if (! $existing) {
                throw $exception;
            }

            $this->assertSameRequest($existing, $fingerprint, $idempotencyKey);
            $this->resubmitIfNecessary($existing);

            return $existing;
        }
    }

    private function fingerprint(AutomationJobRequest $request): string
    {
        $payload = [
            'report_key' => $request->reportKey,
            'parameters' => $this->sortRecursively($request->parameters),
            'source' => $request->source->value,
            'requested_by_user_id' => $request->requestedByUserId,
            'metadata' => $this->sortRecursively($request->metadata),
        ];

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function assertSameRequest(AutomationJob $job, string $fingerprint, string $idempotencyKey): void
    {
        if (! hash_equals((string) $job->request_fingerprint, $fingerprint)) {
            throw new AutomationIdempotencyConflictException($idempotencyKey);
        }
    }

    private function resubmitIfNecessary(AutomationJob $job): void
    {
        if (! $job->isAwaitingSubmission() || ($job->status?->value === 'REQUEST_FAILED' && ! $job->submission_retryable)) {
            return;
        }

        SubmitAutomationJob::dispatch($job->id)
            ->onQueue((string) config('automation.queues.submission', 'automation'));
    }

    private function sortRecursively(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sortRecursively($item);
            }
        }

        if (array_is_list($value)) {
            return $value;
        }

        ksort($value);

        return $value;
    }
}
