<?php

namespace App\Services\Agendamento\Actions;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Enum\OrdemServico\TipoManutencaoEnum;
use App\Models;
use App\Services\ItemOrdemServico\ItemOrdemServicoService;
use App\Services\OrdemServico\OrdemServicoService;
use App\Services\PreventivaOrdemServico\PreventivaOrdemServicoService;
use App\Services\Veiculo\VeiculoService;
use App\Traits\UserCheckTrait;
use Illuminate\Support\Facades\Log;

class VincularOrdemServico
{
    use UserCheckTrait;

    protected OrdemServicoService $ordemServicoService;

    public function __construct(protected Models\Agendamento $agendamento)
    {
        $this->ordemServicoService = new OrdemServicoService();
    }

    public function handle(): void
    {
        $this->validate();

        $ordemServico = $this->ordemServicoService->vincularAgendamento($this->agendamento);

        if (!$ordemServico) {
            throw new \Exception('Erro ao vincular agendamento a ordem de serviço.');
        }

        $this->agendamento->update([
            'ordem_servico_id'  => $ordemServico->id,
            'status'            => StatusOrdemServicoEnum::EXECUCAO,
            'updated_by'        => $this->getUserIdChecked(),
        ]);

        Log::debug('Agendamento vinculado a Ordem de Serviço', [
            'agendamento_id' => $this->agendamento->id,
            'ordem_servico_id' => $ordemServico->id,
            'user_id' => $this->getUserIdChecked(),
        ]);

        return;
    }

    public function validate(): void
    {
        if ($this->agendamento->status != StatusOrdemServicoEnum::PENDENTE) {
            throw new \InvalidArgumentException('Agendamento deve estar pendente.');
        }

        if ($this->agendamento->ordem_servico_id) {
            throw new \InvalidArgumentException('Agendamento já está vinculado a uma ordem de serviço.');
        }
    }
}
