<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BackfillCteEmailRequestDocumentosFreteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('cte_email_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('viagem_id')->nullable();
            $table->string('documento_transporte');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('viagens', function (Blueprint $table) {
            $table->id();
            $table->string('documento_transporte')->nullable();
        });

        Schema::create('incoming_emails', function (Blueprint $table) {
            $table->id();
        });

        Schema::create('cte_email_request_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cte_email_request_id');
            $table->unsignedBigInteger('incoming_email_id')->nullable();
        });

        Schema::create('incoming_email_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('incoming_email_id');
            $table->json('metadata')->nullable();
        });

        Schema::create('documentos_frete', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cte_email_request_id')->nullable();
            $table->unsignedBigInteger('viagem_id')->nullable();
            $table->string('documento_transporte');
            $table->string('numero_documento');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('documentos_frete');
        Schema::dropIfExists('viagens');
        Schema::dropIfExists('incoming_email_attachments');
        Schema::dropIfExists('cte_email_request_messages');
        Schema::dropIfExists('incoming_emails');
        Schema::dropIfExists('cte_email_requests');

        parent::tearDown();
    }

    public function test_backfills_the_freight_document_created_from_a_cte_return(): void
    {
        DB::table('cte_email_requests')->insert([
            'id' => 10,
            'viagem_id' => 20,
            'documento_transporte' => 'DT-123',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('incoming_emails')->insert(['id' => 30]);
        DB::table('cte_email_request_messages')->insert([
            'cte_email_request_id' => 10,
            'incoming_email_id' => 30,
        ]);
        DB::table('incoming_email_attachments')->insert([
            'incoming_email_id' => 30,
            'metadata' => json_encode([
                'cte_email_request_id' => 10,
                'cte_return' => 'document_created',
                'numero_documento' => '456',
            ]),
        ]);
        DB::table('documentos_frete')->insert([
            'id' => 40,
            'viagem_id' => 20,
            'documento_transporte' => 'DT-123',
            'numero_documento' => '456',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('cte:backfill-request-documentos-frete')
            ->assertSuccessful();

        $this->assertSame(10, DB::table('documentos_frete')->value('cte_email_request_id'));
    }
}
