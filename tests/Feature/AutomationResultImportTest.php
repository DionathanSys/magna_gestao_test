<?php

namespace Tests\Feature;

use App\Domain\Automation\AutomationReportDefinition;
use App\Domain\Automation\AutomationReportRegistry;
use App\Domain\Automation\AutomationResultImporterRegistry;
use App\Domain\Automation\Contracts\AutomationResultImporter;
use App\Domain\Automation\Data\AutomationImportPageResult;
use App\Enum\Automation\AutomationJobSource;
use App\Enum\Automation\AutomationJobStatus;
use App\Enum\Automation\AutomationResultImportStatus;
use App\Infrastructure\Automation\AutomationApiClient;
use App\Jobs\Automation\ImportAutomationResult;
use App\Models\AutomationJob;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class AutomationResultImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
            $table->string('idempotency_key')->unique();
            $table->char('request_fingerprint', 64);
            $table->string('request_id');
            $table->unsignedInteger('progress_current')->nullable();
            $table->unsignedInteger('progress_total')->nullable();
            $table->string('progress_message')->nullable();
            $table->unsignedBigInteger('result_count')->nullable();
            $table->string('result_checksum')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('automation_result_imports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('automation_job_id');
            $table->unsignedInteger('page_number');
            $table->text('cursor')->nullable();
            $table->text('next_cursor')->nullable();
            $table->string('checksum')->nullable();
            $table->string('status')->default('PROCESSING');
            $table->unsignedInteger('records_received')->default(0);
            $table->unsignedInteger('records_created')->default(0);
            $table->unsignedInteger('records_updated')->default(0);
            $table->unsignedInteger('records_ignored')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['automation_job_id', 'page_number']);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('automation_result_imports');
        Schema::dropIfExists('automation_jobs');

        Mockery::close();

        parent::tearDown();
    }

    public function test_finishing_a_single_result_page_reaches_the_total_record_count(): void
    {
        $definition = new AutomationReportDefinition(
            key: 'daily_trip_summary',
            collector: 'daily_trip_summary',
            collectorVersion: '1.0.0',
            schemaVersion: '1.0',
            defaultUnidadeNegocio: null,
            resultPageLimit: 500,
            parameterRules: [],
            fields: [],
        );
        $importer = Mockery::mock(AutomationResultImporter::class);
        $importer->shouldReceive('importPage')
            ->once()
            ->andReturn(new AutomationImportPageResult(20, 20, 0, 0));

        $reports = Mockery::mock(AutomationReportRegistry::class);
        $reports->shouldReceive('get')->once()->with('daily_trip_summary')->andReturn($definition);
        $importers = Mockery::mock(AutomationResultImporterRegistry::class);
        $importers->shouldReceive('get')->once()->with($definition)->andReturn($importer);
        $client = Mockery::mock(AutomationApiClient::class);
        $client->shouldReceive('getResultPage')->once()->andReturn([
            'data' => array_fill(0, 20, ['numero_viagem' => 'VIAGEM']),
            'meta' => [
                'total' => 20,
                'next_cursor' => null,
                'checksum' => 'sha256:test',
            ],
        ]);

        $job = AutomationJob::query()->create([
            'provider_job_id' => 'provider-job-001',
            'report_key' => 'daily_trip_summary',
            'collector' => 'daily_trip_summary',
            'status' => AutomationJobStatus::COMPLETED,
            'source' => AutomationJobSource::MANUAL,
            'parameters' => [],
            'idempotency_key' => 'test-key',
            'request_fingerprint' => hash('sha256', 'test'),
            'request_id' => 'request-001',
            'progress_current' => 1,
            'progress_total' => 20,
        ]);

        (new ImportAutomationResult($job->id))->handle($client, $reports, $importers);

        $job->refresh();

        $this->assertSame(20, $job->progress_current);
        $this->assertSame(20, $job->progress_total);
        $this->assertSame(20, $job->result_count);
        $this->assertSame(AutomationJobStatus::COMPLETED, $job->status);
        $this->assertDatabaseHas('automation_result_imports', [
            'automation_job_id' => $job->id,
            'page_number' => 0,
            'records_received' => 20,
            'status' => AutomationResultImportStatus::COMPLETED->value,
        ]);
    }
}
