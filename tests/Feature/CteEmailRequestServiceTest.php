<?php

namespace Tests\Feature;

use App\Models\CteEmailRequest;
use App\Services\Bugio\CteEmailRequestService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CteEmailRequestServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('cte_email_requests', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->json('nfe_keys')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('cte_email_requests');

        parent::tearDown();
    }

    public function test_finds_the_only_open_request_with_a_referenced_nfe_key(): void
    {
        $key = '35260912345678000123550010000000011000000010';

        CteEmailRequest::query()->create([
            'status' => 'sent',
            'nfe_keys' => [$key],
        ]);

        $request = app(CteEmailRequestService::class)->findSingleOpenByNfeKeys([$key]);

        $this->assertNotNull($request);
        $this->assertSame($key, $request->nfe_keys[0]);
    }

    public function test_does_not_match_ambiguous_referenced_nfe_keys(): void
    {
        $key = '35260912345678000123550010000000011000000010';

        CteEmailRequest::query()->insert([
            ['status' => 'sent', 'nfe_keys' => json_encode([$key]), 'created_at' => now(), 'updated_at' => now()],
            ['status' => 'processing', 'nfe_keys' => json_encode([$key]), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $request = app(CteEmailRequestService::class)->findSingleOpenByNfeKeys([$key]);

        $this->assertNull($request);
    }
}
