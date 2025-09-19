<?php

namespace App\Services\OrdemServico\Actions;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Enum\OrdemServico\TipoManutencaoEnum;
use App\Models;
use App\Services\Agendamento\AgendamentoService;
use App\Services\ItemOrdemServico\ItemOrdemServicoService;
use App\Services\Veiculo\VeiculoService;
use App\Traits\UserCheckTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\NotificacaoService as notify;

class EncerrarOrdemServico
{
    use UserCheckTrait;

    protected ItemOrdemServicoService $itemOrdemServicoService;
    protected AgendamentoService $agendamentoService;

    public function __construct(protected Models\OrdemServico $ordemServico)
    {
        $this->itemOrdemServicoService = new ItemOrdemServicoService();
        $this->agendamentoService = new AgendamentoService();
    }

    public function handle(): Models\OrdemServico
    {
        $this->validate();

        // Atualiza o status dos itens da ordem de serviço
        $this->ordemServico->itens->each(function (Models\ItemOrdemServico $item) {
            if (in_array($item->status, [StatusOrdemServicoEnum::PENDENTE, StatusOrdemServicoEnum::EXECUCAO])) {
                $this->itemOrdemServicoService->update($item->id, [
                    'status' => StatusOrdemServicoEnum::CONCLUIDO,
                ]);
            }
        });

        // Atualiza o status dos agendamentos relacionados
        $this->ordemServico->agendamentos->each(function (Models\Agendamento $agendamento) {
            if ($agendamento->status == StatusOrdemServicoEnum::EXECUCAO) {
                $this->agendamentoService->encerrar($agendamento);
            }
        });

        $this->ordemServico->update([
            'status'        => StatusOrdemServicoEnum::CONCLUIDO,
            'data_fim'      => now(),
        ]);

        return $this->ordemServico;
    }

    public function validate(): void
    {
        if (! in_array($this->ordemServico->status, [StatusOrdemServicoEnum::PENDENTE, StatusOrdemServicoEnum::EXECUCAO])) {
            throw new \InvalidArgumentException('A ordem de serviço não pode ser encerrada no status atual: ' . $this->ordemServico->status->value);
        }

        if ($this->ordemServico->itens->isEmpty()) {
            throw new \InvalidArgumentException('A ordem de serviço não pode ser encerrada sem itens.');
        }
    }
}
