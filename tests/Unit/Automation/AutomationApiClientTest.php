<?php

namespace Tests\Unit\Automation;

use App\Domain\Automation\AutomationReportDefinition;
use App\Infrastructure\Automation\AutomationApiClient;
use App\Models\AutomationJob;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AutomationApiClientTest extends TestCase
{
    public function test_submits_a_job_with_the_same_idempotency_key_and_signed_raw_body(): void
    {
        config([
            'automation.api.base_url' => 'https://automation.test',
            'automation.client.id' => 'magna_gestao',
            'automation.client.secret' => 'test-secret',
            'automation.signature.version' => 'v1',
        ]);

        Http::fake(function (Request $request) {
            $this->assertSame('job-key-001', $request->header('Idempotency-Key')[0]);
            $this->assertSame('magna_gestao', $request->header('X-Client-ID')[0]);
            $this->assertNotEmpty($request->header('X-Signature')[0]);
            $this->assertSame('https://automation.test/api/v1/jobs', $request->url());

            return Http::response([
                'data' => [
                    'id' => 'provider-job-001',
                    'collector' => 'daily_trip_summary',
                    'status' => 'QUEUED',
                ],
            ], 202);
        });

        $job = new AutomationJob([
            'id' => 42,
            'report_key' => 'daily_trip_summary',
            'parameters' => ['date' => '2026-09-19'],
            'metadata' => ['purpose' => 'test'],
            'source' => 'manual',
            'idempotency_key' => 'job-key-001',
            'request_id' => 'request-001',
        ]);

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

        $response = app(AutomationApiClient::class)->createJob($job, $definition);

        $this->assertSame('provider-job-001', $response['id']);
        Http::assertSentCount(1);
    }
}
