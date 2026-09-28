<?php

namespace Tests\Feature;

use App\Domain\Automation\AutomationReportRegistry;
use App\Domain\Automation\Importers\SascarTraveledDistanceImporter;
use App\Models\AutomationJob;
use App\Models\Veiculo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SascarTraveledDistanceImporterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('historico_quilometragens');
        Schema::dropIfExists('veiculos');

        Schema::create('veiculos', function (Blueprint $table): void {
            $table->id();
            $table->string('placa');
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('historico_quilometragens', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('veiculo_id');
            $table->dateTime('data_referencia');
            $table->unsignedInteger('quilometragem');
            $table->timestamps();
        });

        DB::table('veiculos')->insert([
            'placa' => 'ABC1234',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('historico_quilometragens');
        Schema::dropIfExists('veiculos');

        parent::tearDown();
    }

    public function test_imports_sascar_mileage_and_updates_the_current_vehicle_km(): void
    {
        $job = new AutomationJob;
        $job->report_key = 'sascar_traveled_distance';
        $job->parameters = ['from' => '2026-09-27', 'to' => '2026-09-27'];

        $result = $this->importer()->importPage($job, [[
            'plate' => 'ABC-1234',
            'odometer' => '123.456',
            'date' => '2026-09-27',
        ]], []);

        $vehicle = DB::table('veiculos')->where('placa', 'ABC1234')->first();

        $this->assertSame(1, $result->recordsCreated);
        $this->assertSame(0, $result->recordsIgnored);
        $this->assertSame([], $result->errors);
        $this->assertSame(123456, (int) DB::table('historico_quilometragens')->value('quilometragem'));
        $this->assertSame(123456.0, (float) (new Veiculo)->newQuery()->find($vehicle->id)->quilometragem_atual);
    }

    public function test_reprocessing_the_same_day_is_idempotent_and_can_update_the_value(): void
    {
        $job = new AutomationJob;
        $job->report_key = 'sascar_traveled_distance';

        $importer = $this->importer();
        $item = [
            'placa' => 'ABC1234',
            'quilometragem' => 100000,
            'data_referencia' => '2026-09-27',
        ];

        $first = $importer->importPage($job, [$item], []);
        $same = $importer->importPage($job, [$item], []);
        $updated = $importer->importPage($job, [[...$item, 'quilometragem' => 100500]], []);

        $this->assertSame(1, $first->recordsCreated);
        $this->assertSame(1, $same->recordsIgnored);
        $this->assertSame(1, $updated->recordsUpdated);
        $this->assertSame(1, DB::table('historico_quilometragens')->count());
        $this->assertSame(100500, (int) DB::table('historico_quilometragens')->value('quilometragem'));
    }

    public function test_an_older_report_does_not_replace_the_current_km(): void
    {
        $job = new AutomationJob;
        $job->report_key = 'sascar_traveled_distance';
        $importer = $this->importer();

        $importer->importPage($job, [[
            'placa' => 'ABC1234',
            'quilometragem' => 200000,
            'data_referencia' => '2026-09-28',
        ]], []);
        $importer->importPage($job, [[
            'placa' => 'ABC1234',
            'quilometragem' => 199500,
            'data_referencia' => '2026-09-27',
        ]], []);

        $vehicle = (new Veiculo)->newQuery()->where('placa', 'ABC1234')->firstOrFail();

        $this->assertSame(200000.0, (float) $vehicle->quilometragem_atual);
    }

    private function importer(): SascarTraveledDistanceImporter
    {
        return new SascarTraveledDistanceImporter(new AutomationReportRegistry);
    }
}
