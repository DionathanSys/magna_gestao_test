<?php

namespace Tests\Feature;

use App\Models\Abastecimento;
use App\Models\ResultadoPeriodo;
use App\Models\TipoVeiculo;
use App\Models\Veiculo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResultadoPeriodoWasteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('abastecimentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('veiculo_id');
            $table->unsignedBigInteger('resultado_periodo_id')->nullable();
            $table->integer('quilometragem');
            $table->dateTime('data_abastecimento');
            $table->boolean('considerar_calculo_medio')->default(true);
            $table->decimal('quantidade', 8, 2)->default(0);
            $table->decimal('preco_total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('abastecimentos');

        parent::tearDown();
    }

    public function test_calculates_liters_and_money_waste_against_vehicle_meta(): void
    {
        $vehicle = new Veiculo(['id' => 9]);
        $vehicle->setRelation('tipoVeiculo', new TipoVeiculo(['meta_media' => 2.5]));

        Abastecimento::query()->create([
            'veiculo_id' => 9,
            'quilometragem' => 1000,
            'data_abastecimento' => '2026-08-31 10:00:00',
            'considerar_calculo_medio' => true,
        ]);

        $abastecimentoInicial = new Abastecimento([
            'veiculo_id' => 9,
            'quilometragem' => 1000,
            'data_abastecimento' => '2026-09-01 10:00:00',
        ]);
        $abastecimentoFinal = new Abastecimento([
            'veiculo_id' => 9,
            'quilometragem' => 2000,
            'data_abastecimento' => '2026-09-30 10:00:00',
        ]);
        $record = new ResultadoPeriodo;
        $record->veiculo_id = 9;
        $record->setRelation('veiculo', $vehicle);
        $record->setRelation('abastecimentoInicial', $abastecimentoInicial);
        $record->setRelation('abastecimentoFinal', $abastecimentoFinal);
        $record->setAttribute('abastecimentos_sum_quantidade', 500);
        $record->setAttribute('abastecimentos_sum_preco_total', 100000);

        $this->assertSame(2.5, $record->meta_consumo);
        $this->assertSame(400.0, $record->litros_estimados_meta);
        $this->assertSame(100.0, $record->desperdicio_litros);
        $this->assertSame(2.0, $record->preco_medio_combustivel);
        $this->assertSame(200.0, $record->desperdicio_valor);
    }

    public function test_returns_no_waste_when_vehicle_has_no_consumption_meta(): void
    {
        $vehicle = new Veiculo(['id' => 9]);
        $vehicle->setRelation('tipoVeiculo', new TipoVeiculo(['meta_media' => 0]));

        $record = new ResultadoPeriodo;
        $record->veiculo_id = 9;
        $record->setRelation('veiculo', $vehicle);
        $record->setAttribute('abastecimentos_sum_quantidade', 500);
        $record->setAttribute('abastecimentos_sum_preco_total', 100000);

        $this->assertNull($record->meta_consumo);
        $this->assertNull($record->litros_estimados_meta);
        $this->assertNull($record->desperdicio_litros);
        $this->assertNull($record->desperdicio_valor);
    }
}
