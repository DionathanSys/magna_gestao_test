<?php

namespace App\Services\OrdemServico;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Enum\OrdemServico\TipoManutencaoEnum;
use App\Models;
use App\Models\ItemOrdemServico;
use App\Models\OrdemServico;
use App\Services\Agendamento\AgendamentoService;
use App\Services\NotificacaoService as notify;
use App\Services\Veiculo\VeiculoService;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use function PHPUnit\Framework\isEmpty;

class OrdemServicoService
{
    use ServiceResponseTrait;

    protected VeiculoService $veiculoService;

    public function __construct()
    {
        $this->veiculoService = new VeiculoService();
    }

    public function firstOrCreate($data): OrdemServico
    {

        $ordemServico = OrdemServico::query()
            ->where('veiculo_id', $data['veiculo_id'])
            ->where('parceiro_id', $data['parceiro_id'] ?? null)
            ->where('status', StatusOrdemServicoEnum::PENDENTE)
            ->first();

        if ($ordemServico) {
            Log::info('Ordem de Serviço pendente já existe', [
                'ordem_servico_id' => $ordemServico->id,
            ]);

            return $ordemServico;
        }

        $ordemServico = $this->create($data);

        return $ordemServico;
    }

    public function create(array $data): ?OrdemServico
    {
        try {
            $ordemServico = (new Actions\CriarOrdemServico())->handle($data);
            $this->setSuccess('Ordem de Serviço criada com sucesso!');
            return $ordemServico;
        } catch (\Exception $e) {
            Log::error(__METHOD__, [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            $this->setError($e->getMessage());
            return null;
        }
    }

    public function vincularAgendamento(Models\Agendamento $agendamento): ?OrdemServico
    {
        try {
            $ordemServico = $this->firstOrCreate([
                'veiculo_id'    => $agendamento->veiculo_id,
                'parceiro_id'   => $agendamento->parceiro_id,
                'quilometragem' => $this->veiculoService::getQuilometragemAtualByVeiculoId($agendamento->veiculo_id),
                'tipo_manutencao' => $agendamento->plano_preventivo_id ? TipoManutencaoEnum::PREVENTIVA : TipoManutencaoEnum::CORRETIVA,
            ]);

            $action = new Actions\VincularAgendamento($ordemServico, $agendamento);
            $action->handle();

            $this->setSuccess('Agendamento vinculado à Ordem de Serviço com sucesso!');
            return $ordemServico;
        } catch (\Exception $e) {
            Log::error('Erro ao vincular agendamento a ordem de serviço', [
                'agendamento_id' => $agendamento->id,
                'error' => $e->getMessage(),
            ]);
            $this->setError($e->getMessage());
            return null;
        }
    }

    public function encerrarOrdemServico(OrdemServico $ordemServico): void
    {
        try {
            $action = new Actions\EncerrarOrdemServico($ordemServico);
            $action->handle();

            $this->setSuccess('Ordem de Serviço encerrada com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao encerrar ordem de serviço', [
                'ordem_servico_id' => $ordemServico->id,
                'error' => $e->getMessage(),
            ]);
            $this->setError($e->getMessage());
            return;
        }
    }

    //TODO Implementar método para excluir ordem de serviço
    //************************************************* */



    public function reagendarServico(ItemOrdemServico $item, $data = null)
    {
        if ($item->status != StatusOrdemServicoEnum::PENDENTE) {
            notify::error('Serviço não pode ser reagendado, pois não está pendente.');
            return;
        }

        $item->update([
            'status' => StatusOrdemServicoEnum::ADIADO,
        ]);

        $service = new AgendamentoService();
        $service->create([
            'ordem_servico_id'  => null,
            'veiculo_id'        => $item->ordemServico->veiculo_id,
            'data_agendamento'  => $data ?? null,
            'servico_id'        => $item->servico_id,
            'observacao'        => $item->observacao,
            'parceiro_id'       => $item->ordemServico->parceiro_id ?? null,
        ]);
    }

    public function ordemServicoPendente(int $veiculoId): ?OrdemServico
    {
        return OrdemServico::where('veiculo_id', $veiculoId)
            ->where('status', StatusOrdemServicoEnum::PENDENTE)
            ->first();
    }
}
