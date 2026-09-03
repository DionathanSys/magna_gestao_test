<?php

namespace Tests\Feature;

use App\Models\CteEmailRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BackfillCteEmailRequestNfeKeysTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('viagens', function (Blueprint $table) {
            $table->id();
        });

        Schema::create('received_fiscal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('chave_nfe')->nullable();
            $table->string('numero_nota')->nullable();
        });

        Schema::create('viagem_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('viagem_id');
            $table->unsignedBigInteger('received_fiscal_document_id')->nullable();
        });

        Schema::create('cte_email_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('viagem_id')->nullable();
            $table->string('status');
            $table->json('payload')->nullable();
            $table->json('nfe_keys')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('cte_email_requests');
        Schema::dropIfExists('viagem_attachments');
        Schema::dropIfExists('received_fiscal_documents');
        Schema::dropIfExists('viagens');

        parent::tearDown();
    }

    public function test_backfills_keys_from_the_request_trip_and_grouped_payload(): void
    {
        $tripKey = '35260912345678000123550010000000011000000010';
        $groupedKey = '35260912345678000123550010000000021000000020';

        DB::table('viagens')->insert(['id' => 1]);
        DB::table('received_fiscal_documents')->insert([
            ['id' => 1, 'numero_nota' => '100', 'chave_nfe' => $tripKey],
            ['id' => 2, 'numero_nota' => '200', 'chave_nfe' => $groupedKey],
        ]);
        DB::table('viagem_attachments')->insert([
            'viagem_id' => 1,
            'received_fiscal_document_id' => 1,
        ]);

        CteEmailRequest::query()->insert([
            ['viagem_id' => 1, 'status' => 'sent', 'payload' => json_encode(['nro_notas' => ['100']]), 'created_at' => now(), 'updated_at' => now()],
            ['viagem_id' => null, 'status' => 'sent', 'payload' => json_encode(['nro_notas' => ['200']]), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->artisan('cte:backfill-request-nfe-keys')
            ->assertSuccessful();

        $requests = CteEmailRequest::query()->orderBy('id')->get();

        $this->assertSame([$tripKey], $requests[0]->nfe_keys);
        $this->assertSame([$groupedKey], $requests[1]->nfe_keys);
    }

    public function test_dry_run_does_not_persist_keys(): void
    {
        $key = '35260912345678000123550010000000011000000010';

        DB::table('received_fiscal_documents')->insert([
            'id' => 1,
            'numero_nota' => '100',
            'chave_nfe' => $key,
        ]);
        CteEmailRequest::query()->create([
            'status' => 'sent',
            'payload' => ['nro_notas' => ['100']],
        ]);

        $this->artisan('cte:backfill-request-nfe-keys', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertNull(CteEmailRequest::query()->value('nfe_keys'));
    }
}
