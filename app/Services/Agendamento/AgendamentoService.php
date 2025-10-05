<?php

namespace App\Services\Agendamento;

use App\{Models, Services, Enum};
use App\Services\ItemOrdemServico\ItemOrdemServicoService;
use App\Services\OrdemServico\OrdemServicoService;
use App\Traits\ServiceResponseTrait;
use Illuminate\Database\Eloquent\Collection;

class AgendamentoService
{
    use ServiceResponseTrait;

    protected OrdemServicoService $ordemServicoService;
    protected ItemOrdemServicoService $itemOrdemServicoService;

    public function __construct()
    {
        $this->ordemServicoService      = new OrdemServicoService();
        $this->itemOrdemServicoService  = new ItemOrdemServicoService();
    }

    public function create(array $data): ?Models\Agendamento
    {
        try {
            $agendamento = (new Actions\CriarAgendamento())->handle($data);
            $this->setSuccess('Agendamento criado com sucesso!');
            return $agendamento;
        } catch (\Exception $e) {
           $this->setError($e->getMessage());
           return null;
        }
    }

    public function encerrar(Models\Agendamento $agendamento)
    {
        try {
            $agendamento = (new Actions\EncerrarAgendamento($agendamento))->handle();
            $this->setSuccess('Agendamento encerrado com sucesso!');
            return $agendamento;
        } catch (\Exception $e) {
           $this->setError($e->getMessage());
           return null;
        }

    }

    public function vincularEmOrdemServico(Models\Agendamento $agendamento)
    {
        try {
            $agendamento = (new Actions\VincularOrdemServico($agendamento))->handle();
            return $this->setSuccess('Agendamento vinculado a Ordem de Serviço com sucesso.');
        } catch (\Exception $e) {
            return $this->setError('Erro ao vincular agendamento a ordem de serviço: ' . $e->getMessage());
        }
    }

    public function cancelar(Models\Agendamento $agendamento)
    {
        try {
            $agendamento = (new Actions\CancelarAgendamento($agendamento))->handle();
            $this->setSuccess('Agendamento cancelado com sucesso!');
            return;
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            return null;
        }
    }

    public function getPlanosPreventivosByVeiculo(int $veiculoId): array
    {
        $service = new Services\PlanoManutencao\Queries\GetPlanos();
        return $service->handle($veiculoId)->toArray();
    }

    public function getAgendamentoAbertoByVeiculo(int $veiculoId): ?Collection
    {
        $querie = new Queries\GetAgendamentoAberto();
        return $querie->handle(array($veiculoId));
    }
    
}
