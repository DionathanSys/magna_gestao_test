<?php

namespace Tests\Unit;

use App\Enum\Agendamento\CategoriaAgendamentoEnum;
use App\Enum\OrdemServico\TipoManutencaoEnum;
use App\Jobs\Telegram\EnviarOrdemServicoCriadaTelegram;
use App\Models\Agendamento;
use App\Models\OrdemServico;
use App\Models\Parceiro;
use App\Models\Servico;
use App\Models\Veiculo;
use App\Observers\OrdemServicoObserver;
use App\Services\Alertas\TelegramAlertConfigService;
use App\Services\OrdemServico\OrdemServicoAlertaDataService;
use App\Services\Telegram\OrdemServicoTelegramMessageService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class OrdemServicoTelegramAlertTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_observer_queues_one_job_for_each_configured_recipient(): void
    {
        Queue::fake();

        $config = Mockery::mock(TelegramAlertConfigService::class);
        $config->shouldReceive('isEnabled')
            ->once()
            ->with(TelegramAlertConfigService::ORDEM_SERVICO_CRIADA)
            ->andReturnTrue();
        $config->shouldReceive('recipientIds')
            ->once()
            ->with(TelegramAlertConfigService::ORDEM_SERVICO_CRIADA)
            ->andReturn([10, 20]);

        $this->app->instance(TelegramAlertConfigService::class, $config);

        $ordemServico = new OrdemServico;
        $ordemServico->id = 55;

        (new OrdemServicoObserver)->created($ordemServico);

        Queue::assertPushed(EnviarOrdemServicoCriadaTelegram::class, 2);
        Queue::assertPushed(EnviarOrdemServicoCriadaTelegram::class, function (EnviarOrdemServicoCriadaTelegram $job): bool {
            return in_array($job->destinatarioId, [10, 20], true)
                && $job->ordemServicoId === 55;
        });
    }

    public function test_observer_does_not_queue_when_alert_is_disabled(): void
    {
        Queue::fake();

        $config = Mockery::mock(TelegramAlertConfigService::class);
        $config->shouldReceive('isEnabled')
            ->once()
            ->with(TelegramAlertConfigService::ORDEM_SERVICO_CRIADA)
            ->andReturnFalse();

        $this->app->instance(TelegramAlertConfigService::class, $config);

        (new OrdemServicoObserver)->created(new OrdemServico);

        Queue::assertNothingPushed();
    }

    public function test_message_contains_open_appointments_and_preventive_plans(): void
    {
        $dataService = Mockery::mock(OrdemServicoAlertaDataService::class);

        $servico = new Servico(['descricao' => 'Troca de óleo']);
        $parceiro = new Parceiro(['nome' => 'Oficina Central']);
        $agendamento = new Agendamento([
            'id' => 7,
            'categoria' => CategoriaAgendamentoEnum::MANUAL,
            'data_agendamento' => '2026-09-30',
            'data_limite' => '2026-10-02',
            'observacao' => 'Levar pela manhã',
        ]);
        $agendamento->setRelation('servico', $servico);
        $agendamento->setRelation('parceiro', $parceiro);

        $ordemServico = new OrdemServico([
            'id' => 55,
            'tipo_manutencao' => TipoManutencaoEnum::CORRETIVA,
            'quilometragem' => 123456,
            'data_inicio' => '2026-09-29 10:30:00',
        ]);
        $ordemServico->setRelation('veiculo', new Veiculo(['placa' => 'ABC1D23']));
        $ordemServico->setRelation('parceiro', new Parceiro(['nome' => 'Parceiro OS']));

        $dataService->shouldReceive('agendamentosPendentes')
            ->once()
            ->with($ordemServico)
            ->andReturn(new EloquentCollection([$agendamento]));
        $dataService->shouldReceive('planosPreventivos')
            ->once()
            ->with($ordemServico)
            ->andReturn(collect([
                [
                    'plano_descricao' => 'Preventiva do motor',
                    'km_restante' => 2500,
                    'km_atual' => 100000,
                    'proxima_execucao' => 102500,
                    'data_prevista' => '2026-10-15',
                ],
            ]));

        $message = app()->make(OrdemServicoTelegramMessageService::class, [
            'dataService' => $dataService,
        ])->make($ordemServico);

        $this->assertStringContainsString('OS: #55', $message);
        $this->assertStringContainsString('Veículo: ABC1D23', $message);
        $this->assertStringContainsString('Troca de óleo', $message);
        $this->assertStringContainsString('Levar pela manhã', $message);
        $this->assertStringContainsString('Preventiva do motor', $message);
        $this->assertStringContainsString('KM restante: 2.500 km', $message);
    }
}
