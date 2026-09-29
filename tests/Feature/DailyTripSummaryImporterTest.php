<?php

namespace Tests\Feature;

use App\Domain\Automation\AutomationReportRegistry;
use App\Domain\Automation\Importers\DailyTripSummaryImporter;
use App\Models\AutomationJob;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DailyTripSummaryImporterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('viagens');
        Schema::dropIfExists('viagem_sequences');
        Schema::dropIfExists('veiculos');
        Schema::create('veiculos', function (Blueprint $table): void {
            $table->id();
            $table->string('placa');
            $table->string('filial')->nullable();
            $table->json('informacoes_complementares')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('veiculos');
        Schema::dropIfExists('viagens');
        Schema::dropIfExists('viagem_sequences');

        parent::tearDown();
    }

    public function test_vehicle_branch_is_used_before_unit_validation(): void
    {
        DB::table('veiculos')->insert([
            'placa' => 'ABC1234',
            'filial' => 'CHAPECO',
            'informacoes_complementares' => json_encode(['cliente' => 'CLIENTE DO CADASTRO']),
            'is_active' => true,
        ]);

        $job = new AutomationJob;
        $job->report_key = 'daily_trip_summary';

        $result = (new DailyTripSummaryImporter(new AutomationReportRegistry))->importPage(
            $job,
            [[
                'numero_viagem' => 'TRIP-001',
                'placa' => 'ABC1234',
                'cliente' => 'CLIENTE VINDO DO PAYLOAD',
            ]],
            [],
        );

        $this->assertSame(1, $result->recordsIgnored);
        $this->assertSame('CHAPECO', $result->errors[0]['unidade_negocio']);
        $this->assertSame('CHAPECO', $result->errors[0]['veiculo_filial']);
        $this->assertSame('CLIENTE DO CADASTRO', $result->errors[0]['cliente']);
        $this->assertStringNotContainsString('unidade_negocio', $result->errors[0]['error']);
    }

    public function test_missing_paid_kilometers_are_persisted_as_zero_with_a_pending_issue(): void
    {
        Queue::fake();

        Schema::create('viagem_sequences', function (Blueprint $table): void {
            $table->id();
            $table->string('cliente')->unique();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });

        Schema::create('viagens', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('veiculo_id');
            $table->string('unidade_negocio');
            $table->string('cliente')->nullable();
            $table->string('numero_viagem')->unique();
            $table->string('numero_interno')->nullable();
            $table->decimal('km_rodado', 10, 2)->nullable();
            $table->decimal('km_pago', 10, 2);
            $table->date('data_competencia');
            $table->dateTime('data_inicio');
            $table->dateTime('data_fim');
            $table->boolean('possui_pendencia')->default(false);
            $table->json('pendencias')->nullable();
            $table->json('motoristas')->nullable();
            $table->boolean('conferido')->default(false);
            $table->boolean('ignorar')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        DB::table('veiculos')->insert([
            'placa' => 'ABC1234',
            'filial' => 'CHAPECO',
            'informacoes_complementares' => json_encode(['cliente' => 'CLIENTE DO CADASTRO']),
            'is_active' => true,
        ]);

        $job = new AutomationJob;
        $job->report_key = 'daily_trip_summary';

        $result = (new DailyTripSummaryImporter(new AutomationReportRegistry))->importPage(
            $job,
            [[
                'numero_viagem' => 'TRIP-002',
                'placa' => 'ABC1234',
                'unidade_negocio' => 'CHAPECO',
                'km_rodado' => 12,
                'data_competencia' => '2026-09-27',
                'data_inicio' => '2026-09-27 08:00:00',
                'data_fim' => '2026-09-27 09:00:00',
                'possui_pendencia' => false,
                'pendencias' => [],
                'motoristas' => [],
            ]],
            [],
        );

        $viagem = DB::table('viagens')->where('numero_viagem', 'TRIP-002')->first();
        $pendencias = json_decode((string) $viagem?->pendencias, true);

        $this->assertSame(1, $result->recordsCreated);
        $this->assertSame(0, $result->recordsIgnored);
        $this->assertSame([], $result->errors);
        $this->assertNotNull($viagem);
        $this->assertSame(0.0, (float) $viagem->km_pago);
        $this->assertTrue((bool) $viagem->possui_pendencia);
        $this->assertSame('Sem km pago', $pendencias['sem_km_pago']);
    }
}
