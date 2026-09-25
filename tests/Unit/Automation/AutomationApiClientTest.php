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
            'parameters' => ['from' => '2026-09-19', 'to' => '2026-09-21'],
            'metadata' => [],
            'source' => 'manual',
            'requested_by_user_id' => 1,
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
        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();

            return $payload['collector'] === 'daily_trip_summary'
                && $payload['parameters'] === [
                    'from' => '2026-09-19',
                    'to' => '2026-09-21',
                ]
                && $payload['requested_by'] === 'user:1'
                && $payload['metadata'] === [
                    'local_job_id' => '42',
                    'report_key' => 'daily_trip_summary',
                    'collector_version' => '1.0.0',
                    'schema_version' => '1.0',
                ];
        });

        Http::assertSent(function (Request $request): bool {
            $timestamp = $request->header('X-Timestamp')[0];
            $nonce = $request->header('X-Nonce')[0];
            $canonical = implode("\n", [
                'POST',
                '/api/v1/jobs',
                $timestamp,
                $nonce,
                hash('sha256', $request->body()),
            ]);

            return $request->header('X-Signature-Version')[0] === 'v1'
                && $request->header('Content-Type')[0] === 'application/json'
                && $request->header('Idempotency-Key')[0] === 'job-key-001'
                && hash_hmac('sha256', $canonical, 'test-secret') === $request->header('X-Signature')[0];
        });
    }

    public function test_queries_the_allowed_collectors(): void
    {
        config([
            'automation.api.base_url' => 'https://automation.test',
            'automation.client.id' => 'magna_gestao',
            'automation.client.secret' => 'test-secret',
            'automation.signature.version' => 'v1',
        ]);

        Http::fake([
            'https://automation.test/api/v1/collectors' => Http::response([
                'data' => [[
                    'name' => 'daily_trip_summary',
                    'version' => '1.0.0',
                    'schema_version' => '1.0',
                    'enabled' => true,
                ]],
            ]),
        ]);

        $response = app(AutomationApiClient::class)->getCollectors('request-collectors-001');

        $this->assertSame('daily_trip_summary', $response['data'][0]['name']);
        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://automation.test/api/v1/collectors'
                && $request->header('X-Client-ID')[0] === 'magna_gestao'
                && $request->header('X-Signature-Version')[0] === 'v1';
        });
    }
}
