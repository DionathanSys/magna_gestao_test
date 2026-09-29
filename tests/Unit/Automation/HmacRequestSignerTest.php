<?php

namespace Tests\Unit\Automation;

use App\Infrastructure\Automation\HmacRequestSigner;
use Tests\TestCase;

class HmacRequestSignerTest extends TestCase
{
    public function test_generates_the_v1_canonical_signature(): void
    {
        config([
            'automation.client.id' => 'magna_gestao',
            'automation.client.secret' => 'test-secret',
            'automation.signature.version' => 'v1',
        ]);

        $headers = app(HmacRequestSigner::class)->headers(
            method: 'POST',
            pathWithQuery: '/api/v1/jobs',
            rawBody: '{"collector":"daily_trip_summary"}',
            requestId: 'req-test-001',
            timestamp: 1789831200,
            nonce: 'nonce-test-001',
        );

        $canonical = implode("\n", [
            'POST',
            '/api/v1/jobs',
            '1789831200',
            'nonce-test-001',
            hash('sha256', '{"collector":"daily_trip_summary"}'),
        ]);

        $this->assertSame('magna_gestao', $headers['X-Client-ID']);
        $this->assertSame('req-test-001', $headers['X-Request-ID']);
        $this->assertSame('v1', $headers['X-Signature-Version']);
        $this->assertSame(hash_hmac('sha256', $canonical, 'test-secret'), $headers['X-Signature']);
    }
}
