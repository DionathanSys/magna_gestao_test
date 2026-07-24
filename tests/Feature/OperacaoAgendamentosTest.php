<?php

namespace Tests\Feature;

use App\Filament\Resources\Agendamentos\Pages\OperacaoAgendamentos;
use App\Models\Parceiro;
use App\Models\Servico;
use App\Models\User;
use App\Models\Veiculo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class OperacaoAgendamentosTest extends TestCase
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

        if (! Schema::hasTable('veiculos')) {
            Schema::create('veiculos', function (Blueprint $table): void {
                $table->id();
                $table->string('filial', 50);
                $table->string('placa', 7)->unique();
                $table->string('modelo')->nullable();
                $table->string('marca')->nullable();
                $table->decimal('ano_fabricacao', 4, 0)->nullable();
                $table->decimal('ano_modelo', 4, 0)->nullable();
                $table->string('chassis')->nullable();
                $table->boolean('is_active')->default(true);
                $table->json('informacoes_complementares')->nullable();
                $table->decimal('km_medio', 10, 2)->default(0);
                $table->dateTime('data_km_medio')->nullable();
                $table->unsignedBigInteger('tipo_veiculo_id')->nullable();
                $table->unsignedBigInteger('mapa_pneu_id')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('servicos')) {
            Schema::create('servicos', function (Blueprint $table): void {
                $table->id();
                $table->string('codigo', 10)->nullable();
                $table->string('descricao');
                $table->string('complemento')->nullable();
                $table->string('tipo')->nullable();
                $table->boolean('controla_posicao')->default(false);
                $table->json('posicoes_permitidas')->nullable();
                $table->boolean('is_active')->default(true);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('parceiros')) {
            Schema::create('parceiros', function (Blueprint $table): void {
                $table->id();
                $table->string('nome')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('planos_preventivo')) {
            Schema::create('planos_preventivo', function (Blueprint $table): void {
                $table->id();
                $table->string('descricao')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('planos_manutencao_veiculo')) {
            Schema::create('planos_manutencao_veiculo', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('veiculo_id');
                $table->unsignedBigInteger('plano_preventivo_id');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('agendamentos')) {
            Schema::create('agendamentos', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('veiculo_id')->constrained('veiculos');
                $table->unsignedBigInteger('ordem_servico_id')->nullable();
                $table->date('data_agendamento')->nullable();
                $table->date('data_limite')->nullable();
                $table->date('data_realizado')->nullable();
                $table->foreignId('servico_id')->constrained('servicos')->cascadeOnDelete();
                $table->string('categoria')->default('MANUAL');
                $table->unsignedBigInteger('plano_preventivo_id')->nullable();
                $table->string('posicao', 10)->nullable();
                $table->string('status', 20);
                $table->string('observacao', 255)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('parceiro_id')->nullable()->constrained('parceiros')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('agendamento_historicos')) {
            Schema::create('agendamento_historicos', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('agendamento_id')->constrained('agendamentos')->cascadeOnDelete();
                $table->string('tipo_evento');
                $table->text('descricao')->nullable();
                $table->json('dados')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function test_operacao_agendamentos_cria_um_novo_agendamento_pelo_modal_do_header(): void
    {
        $user = User::factory()->create();

        $veiculo = Veiculo::query()->create([
            'filial' => 'MATRIZ',
            'placa' => 'T'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'is_active' => true,
        ]);

        $servico = Servico::query()->create([
            'descricao' => 'Troca de oleo teste',
            'controla_posicao' => false,
            'is_active' => true,
        ]);

        $parceiro = Parceiro::query()->create([
            'nome' => 'Parceiro Teste',
        ]);

        $this->actingAs($user);

        Livewire::test(OperacaoAgendamentos::class)
            ->call('openCreateAgendamentoModal')
            ->assertSet('showCreateAgendamentoModal', true)
            ->set('createAgendamentoData.veiculo_id', $veiculo->id)
            ->set('createAgendamentoData.data_agendamento', now()->toDateString())
            ->set('createAgendamentoData.data_limite', now()->addDay()->toDateString())
            ->set('createAgendamentoData.servico_id', $servico->id)
            ->set('createAgendamentoData.observacao', 'Criado pelo teste')
            ->set('createAgendamentoData.parceiro_id', $parceiro->id)
            ->call('saveCreateAgendamento')
            ->assertSet('showCreateAgendamentoModal', false);

        $this->assertDatabaseHas('agendamentos', [
            'veiculo_id' => $veiculo->id,
            'servico_id' => $servico->id,
            'parceiro_id' => $parceiro->id,
            'observacao' => 'Criado pelo teste',
        ]);

        $agendamentoId = DB::table('agendamentos')
            ->where('veiculo_id', $veiculo->id)
            ->where('servico_id', $servico->id)
            ->value('id');

        $this->assertDatabaseHas('agendamento_historicos', [
            'agendamento_id' => $agendamentoId,
            'tipo_evento' => 'CRIADO',
        ]);
    }
}
