<?php

namespace Tests\Feature;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Models\ItemOrdemServico;
use App\Models\OrdemServico;
use App\Models\PlanoManutencaoOrdemServico;
use App\Models\PlanoManutencaoVeiculo;
use App\Models\PlanoPreventivo;
use App\Models\Veiculo;
use App\Services\OrdemServico\ManutencaoPreventivaService;
use App\Services\PlanoManutencao\RelatorioExecucoesPlanoPreventivoService;
use App\Services\PlanoManutencao\RelatorioPlanoManutencaoService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlanosPreventivosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        foreach ([
            'planos_manutencao_ordem_servico',
            'itens_ordem_servico',
            'ordens_servico',
            'planos_manutencao_veiculo',
            'planos_preventivo',
            'historico_quilometragens',
            'veiculos',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('veiculos', function (Blueprint $table): void {
            $table->id();
            $table->string('placa');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('planos_preventivo', function (Blueprint $table): void {
            $table->id();
            $table->string('descricao');
            $table->unsignedInteger('intervalo')->default(0);
            $table->json('itens')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('planos_manutencao_veiculo', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('veiculo_id');
            $table->unsignedBigInteger('plano_preventivo_id');
            $table->timestamps();
        });

        Schema::create('ordens_servico', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('veiculo_id');
            $table->string('status')->nullable();
            $table->string('status_sankhya')->nullable();
            $table->decimal('quilometragem', 10, 2)->nullable();
            $table->dateTime('data_inicio')->nullable();
            $table->timestamps();
        });

        Schema::create('itens_ordem_servico', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ordem_servico_id');
            $table->unsignedBigInteger('servico_id');
            $table->unsignedBigInteger('plano_preventivo_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('planos_manutencao_ordem_servico', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('plano_preventivo_id');
            $table->unsignedBigInteger('ordem_servico_id')->nullable();
            $table->unsignedBigInteger('veiculo_id');
            $table->decimal('km_execucao', 10, 2);
            $table->date('data_execucao');
            $table->timestamps();
        });

        Schema::create('historico_quilometragens', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('veiculo_id');
            $table->decimal('quilometragem', 10, 2);
            $table->dateTime('data_referencia');
            $table->timestamps();
        });
    }

    public function test_remover_plano_exclui_itens_e_retorna_ao_km_da_execucao_anterior(): void
    {
        $veiculo = Veiculo::query()->create(['placa' => 'ABC1234']);
        $plano = PlanoPreventivo::query()->create([
            'descricao' => 'Preventiva 10 mil',
            'intervalo' => 10000,
        ]);
        $ordem = OrdemServico::query()->create([
            'veiculo_id' => $veiculo->id,
            'status' => StatusOrdemServicoEnum::PENDENTE,
            'quilometragem' => 20000,
        ]);

        $anterior = PlanoManutencaoOrdemServico::query()->create([
            'plano_preventivo_id' => $plano->id,
            'veiculo_id' => $veiculo->id,
            'km_execucao' => 10000,
            'data_execucao' => '2026-01-01',
        ]);
        $atual = PlanoManutencaoOrdemServico::query()->create([
            'plano_preventivo_id' => $plano->id,
            'ordem_servico_id' => $ordem->id,
            'veiculo_id' => $veiculo->id,
            'km_execucao' => 20000,
            'data_execucao' => '2026-02-01',
        ]);
        $item = ItemOrdemServico::query()->create([
            'ordem_servico_id' => $ordem->id,
            'servico_id' => 1,
            'plano_preventivo_id' => $plano->id,
            'status' => StatusOrdemServicoEnum::PENDENTE,
        ]);

        $this->assertTrue(ManutencaoPreventivaService::desassociarPlanoPreventivo($atual));
        $this->assertDatabaseMissing('planos_manutencao_ordem_servico', ['id' => $atual->id]);
        $this->assertDatabaseMissing('itens_ordem_servico', ['id' => $item->id]);

        $planoVeiculo = PlanoManutencaoVeiculo::query()->create([
            'veiculo_id' => $veiculo->id,
            'plano_preventivo_id' => $plano->id,
        ]);

        $this->assertSame($anterior->id, $planoVeiculo->ultima_execucao->id);
        $this->assertSame(20000.0, $planoVeiculo->proxima_execucao);
    }

    public function test_relatorio_traz_todas_as_execucoes_do_veiculo(): void
    {
        $veiculo = Veiculo::query()->create(['placa' => 'ABC1234']);
        $outroVeiculo = Veiculo::query()->create(['placa' => 'DEF5678']);
        $plano = PlanoPreventivo::query()->create([
            'descricao' => 'Preventiva 10 mil',
            'intervalo' => 10000,
        ]);
        $ordem = OrdemServico::query()->create([
            'veiculo_id' => $veiculo->id,
            'status' => StatusOrdemServicoEnum::PENDENTE,
        ]);

        PlanoManutencaoOrdemServico::query()->create([
            'plano_preventivo_id' => $plano->id,
            'ordem_servico_id' => $ordem->id,
            'veiculo_id' => $veiculo->id,
            'km_execucao' => 20000,
            'data_execucao' => '2026-02-01',
        ]);
        PlanoManutencaoOrdemServico::query()->create([
            'plano_preventivo_id' => $plano->id,
            'veiculo_id' => $veiculo->id,
            'km_execucao' => 10000,
            'data_execucao' => '2026-01-01',
        ]);
        PlanoManutencaoOrdemServico::query()->create([
            'plano_preventivo_id' => $plano->id,
            'veiculo_id' => $outroVeiculo->id,
            'km_execucao' => 30000,
            'data_execucao' => '2026-03-01',
        ]);

        $dados = app(RelatorioExecucoesPlanoPreventivoService::class)->obterDados([
            'veiculo_id' => $veiculo->id,
        ]);

        $this->assertCount(2, $dados);
        $this->assertSame('ABC1234', $dados[0]['placa']);
        $this->assertSame('PENDENTE', $dados[0]['ordem_servico_status']);
        $this->assertSame('Registro manual', $dados[1]['origem']);
        $this->assertSame(20000.0, $dados[0]['km_execucao']);
    }

    public function test_relatorio_de_manutencao_filtra_multiplos_veiculos_e_planos_e_agrupa_no_pdf(): void
    {
        $veiculoA = Veiculo::query()->create(['placa' => 'ABC1234']);
        $veiculoB = Veiculo::query()->create(['placa' => 'DEF5678']);
        $planoA = PlanoPreventivo::query()->create([
            'descricao' => 'Plano A',
            'intervalo' => 10000,
        ]);
        $planoB = PlanoPreventivo::query()->create([
            'descricao' => 'Plano B',
            'intervalo' => 20000,
        ]);

        foreach ([$veiculoA, $veiculoB] as $veiculo) {
            DB::table('historico_quilometragens')->insert([
                'veiculo_id' => $veiculo->id,
                'quilometragem' => 50000,
                'data_referencia' => '2026-01-01 00:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ([$veiculoA, $veiculoB] as $veiculo) {
            foreach ([$planoA, $planoB] as $plano) {
                PlanoManutencaoVeiculo::query()->create([
                    'veiculo_id' => $veiculo->id,
                    'plano_preventivo_id' => $plano->id,
                ]);
            }
        }

        $filtros = [
            'veiculo_id' => [$veiculoA->id, $veiculoB->id],
            'plano_preventivo_id' => [$planoA->id, $planoB->id],
            'agrupamento' => 'plano',
        ];
        $dados = app(RelatorioPlanoManutencaoService::class)->obterDadosRelatorio($filtros);

        $this->assertCount(4, $dados);
        $this->assertEqualsCanonicalizing(
            [$veiculoA->id, $veiculoB->id],
            collect($dados)->pluck('veiculo_id')->unique()->all()
        );
        $this->assertEqualsCanonicalizing(
            ['Plano A', 'Plano B'],
            collect($dados)->pluck('plano_descricao')->unique()->all()
        );

        $pdfView = view('pdf.relatorio-plano-manutencao', [
            'dados' => $dados,
            'filtros' => $filtros,
            'totalRegistros' => count($dados),
            'dataGeracao' => now()->format('d/m/Y H:i:s'),
        ])->render();

        $this->assertSame(2, substr_count($pdfView, 'class="group-row"'));
    }
}
