<?php

namespace App\Services\OrdemServico\Actions;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Enum\OrdemServico\TipoManutencaoEnum;
use App\Models;
use App\Models\OrdemServico;
use App\Services\ItemOrdemServico\ItemOrdemServicoService;
use App\Services\OrdemServico\OrdemServicoService;
use App\Services\PreventivaOrdemServico\PreventivaOrdemServicoService;
use App\Services\Veiculo\VeiculoService;
use App\Traits\UserCheckTrait;
use Illuminate\Support\Facades\Log;

class VincularAgendamento
{
    use UserCheckTrait;

    protected VeiculoService $veiculoService;
    protected ItemOrdemServicoService $itemOrdemServicoService;
    protected PreventivaOrdemServicoService $preventivaOrdemServicoService;

    public function __construct(protected Models\OrdemServico $ordemServico, protected Models\Agendamento $agendamento)
    {
        $this->itemOrdemServicoService = new ItemOrdemServicoService();
        $this->preventivaOrdemServicoService = new PreventivaOrdemServicoService();
        $this->veiculoService = new VeiculoService();
    }

    public function handle(): void
    {
        $this->validate();

        if ($this->agendamento->plano_preventivo_id) {
            $this->preventivaOrdemServicoService->create([
                'plano_preventivo_id' => $this->agendamento->plano_preventivo_id,
                'ordem_servico_id'    => $this->ordemServico->id,
                'veiculo_id'          => $this->ordemServico->veiculo_id,
                'km_execucao'         => $this->ordemServico->quilometragem,
                'data_execucao'       => $this->ordemServico->data_inicio,
            ]);

            Log::debug('Plano Preventivo vinculado a Ordem de Serviço', [
                'ordem_servico_id' => $this->ordemServico->id,
                'plano_preventivo_id' => $this->agendamento->plano_preventivo_id,
                'user_id' => $this->getUserIdChecked(),
            ]);

            return;
        }

        $item = $this->itemOrdemServicoService->create([
            'ordem_servico_id'      => $this->ordemServico->id,
            'servico_id'            => $this->agendamento->servico_id,
            'plano_preventivo_id'   => $this->agendamento->plano_preventivo_id,
            'posicao'               => $this->agendamento->posicao,
            'observacao'            => $this->agendamento->observacao,
        ]);


        if (!$item) {
            throw new \Exception('Erro ao criar item na ordem de serviço ' . $this->ordemServico->id);
        }

        return;
    }

    public function validate(): void
    {

        if ($this->ordemServico->status !== StatusOrdemServicoEnum::PENDENTE) {
            throw new \InvalidArgumentException('A Ordem de Serviço deve estar pendente para vincular um agendamento.');
        }

        if ($this->agendamento->status !== StatusOrdemServicoEnum::PENDENTE) {
            throw new \InvalidArgumentException('O Agendamento deve estar pendente para ser vinculado a uma Ordem de Serviço.');
        }

        if ($this->agendamento->veiculo_id !== $this->ordemServico->veiculo_id) {
            throw new \InvalidArgumentException('O veículo do agendamento deve ser o mesmo da Ordem de Serviço.');
        }
    }
}
