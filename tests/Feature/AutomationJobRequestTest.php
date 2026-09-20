<?php

namespace Tests\Feature;

use App\Domain\Automation\Actions\RequestAutomationJob;
use App\Domain\Automation\Data\AutomationJobRequest;
use App\Domain\Automation\Exceptions\AutomationIdempotencyConflictException;
use App\Enum\Automation\AutomationJobSource;
use App\Enum\Automation\AutomationJobStatus;
use App\Jobs\Automation\ReconcileAutomationJobs;
use App\Jobs\Automation\SubmitAutomationJob;
use App\Jobs\Automation\SyncAutomationJob;
use App\Models\AutomationJob;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AutomationJobRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['queue.default' => 'sync']);
        Queue::fake();

        Schema::dropIfExists('automation_jobs');
        Schema::create('automation_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('provider_job_id')->nullable();
            $table->string('report_key');
            $table->string('collector');
            $table->string('collector_version')->nullable();
            $table->string('schema_version')->nullable();
            $table->string('status');
            $table->string('source');
            $table->json('parameters');
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('requested_by_user_id')->nullable();
            $table->string('idempotency_key')->unique();
            $table->char('request_fingerprint', 64);
            $table->string('request_id');
            $table->unsignedInteger('progress_current')->nullable();
            $table->unsignedInteger('progress_total')->nullable();
            $table->string('progress_message')->nullable();
            $table->unsignedSmallInteger('submission_attempts')->default(0);
            $table->boolean('submission_retryable')->nullable();
            $table->unsignedSmallInteger('provider_attempts')->nullable();
            $table->unsignedSmallInteger('provider_max_attempts')->nullable();
            $table->unsignedBigInteger('result_count')->nullable();
            $table->string('result_checksum')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('last_submission_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->unsignedBigInteger('retry_of_job_id')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('automation_jobs');

        parent::tearDown();
    }

    public function test_creates_a_local_job_and_dispatches_submission(): void
    {
        $job = app(RequestAutomationJob::class)->handle(new AutomationJobRequest(
            reportKey: 'daily_trip_summary',
            parameters: ['date' => '2026-09-19'],
            source: AutomationJobSource::SCHEDULED,
            idempotencyKey: 'schedule:daily_trip_summary:2026-09-19',
        ));

        $this->assertSame(AutomationJobStatus::PENDING_SUBMISSION, $job->status);
        $this->assertSame('daily_trip_summary', $job->report_key);
        $this->assertSame(AutomationJobSource::SCHEDULED, $job->source);

        Queue::assertPushed(SubmitAutomationJob::class, function (SubmitAutomationJob $queuedJob) use ($job): bool {
            return $queuedJob->automationJobId === $job->id;
        });
    }

    public function test_same_idempotency_key_returns_the_existing_job(): void
    {
        $request = new AutomationJobRequest(
            reportKey: 'daily_trip_summary',
            parameters: ['date' => '2026-09-19'],
            idempotencyKey: 'same-key',
        );

        $first = app(RequestAutomationJob::class)->handle($request);
        $second = app(RequestAutomationJob::class)->handle($request);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, AutomationJob::query()->count());
    }

    public function test_same_idempotency_key_with_different_content_is_rejected(): void
    {
        $this->expectException(AutomationIdempotencyConflictException::class);

        app(RequestAutomationJob::class)->handle(new AutomationJobRequest(
            reportKey: 'daily_trip_summary',
            parameters: ['date' => '2026-09-19'],
            idempotencyKey: 'same-key',
        ));

        app(RequestAutomationJob::class)->handle(new AutomationJobRequest(
            reportKey: 'daily_trip_summary',
            parameters: ['date' => '2026-09-20'],
            idempotencyKey: 'same-key',
        ));
    }

    public function test_reconciliation_dispatches_submission_and_status_sync_jobs(): void
    {
        $pending = app(RequestAutomationJob::class)->handle(new AutomationJobRequest(
            reportKey: 'daily_trip_summary',
            parameters: ['date' => '2026-09-19'],
            idempotencyKey: 'pending-key',
        ));

        $active = app(RequestAutomationJob::class)->handle(new AutomationJobRequest(
            reportKey: 'daily_trip_summary',
            parameters: ['date' => '2026-09-20'],
            idempotencyKey: 'active-key',
        ));
        $active->update([
            'status' => AutomationJobStatus::RUNNING,
            'provider_job_id' => 'provider-job-001',
        ]);

        Queue::assertPushed(SubmitAutomationJob::class, 2);

        (new ReconcileAutomationJobs)->handle();

        Queue::assertPushed(SubmitAutomationJob::class, function (SubmitAutomationJob $job) use ($pending): bool {
            return $job->automationJobId === $pending->id;
        });
        Queue::assertPushed(SyncAutomationJob::class, function (SyncAutomationJob $job) use ($active): bool {
            return $job->automationJobId === $active->id;
        });
    }
}
