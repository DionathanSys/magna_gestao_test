<?php

namespace Tests\Feature;

use App\Domain\Automation\AutomationReportRegistry;
use App\Domain\Automation\Importers\DailyTripSummaryImporter;
use App\Models\AutomationJob;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DailyTripSummaryImporterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
}
