<?php

namespace App\Services\PreventivaOrdemServico;

use App\Models;
use App\Services\ItemOrdemServico\ItemOrdemServicoService;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Log;

class PreventivaOrdemServicoService
{
    use ServiceResponseTrait;

    protected ItemOrdemServicoService $itemOrdemServicoService;

    public function __construct()
    {
        $this->itemOrdemServicoService = new ItemOrdemServicoService();
    }

    /**
     * Cria uma Ordem de Serviço vinculada a um Plano Preventivo.
     * @param array $data
     * @return Models\PlanoManutencaoOrdemServico
     */


    public function create(array $data): ?Models\PlanoManutencaoOrdemServico
    {
        try {

            Log::debug(__METHOD__ . '-' . __LINE__, ['data' => $data]);

            $preventivaOrdemServico = (new Actions\CriarVinculo())->handle($data);

            Log::debug(__METHOD__ . '-' . __LINE__, [
                'ordem_servico_id' => $preventivaOrdemServico->ordem_servico_id,
                'plano_preventivo_id' => $preventivaOrdemServico->plano_preventivo_id,
            ]);

            $itensPlano = $preventivaOrdemServico->planoPreventivo->itens;

            Log::debug(__METHOD__ . '-' . __LINE__, [
                'itens_plano' => $itensPlano,
                'ordem_servico_id' => $preventivaOrdemServico->ordem_servico_id,
            ]);

            foreach ($itensPlano as $item) {

                $this->itemOrdemServicoService->create([
                    'plano_preventivo_id'   => $preventivaOrdemServico->plano_preventivo_id,
                    'ordem_servico_id'      => $preventivaOrdemServico->ordem_servico_id,
                    'servico_id'            => $item['servico_id'],
                ]);
            }

            $this->setSuccess('Plano Preventivo vinculado à Ordem de Serviço com sucesso!');
            return $preventivaOrdemServico;
        } catch (\Exception $e) {
            Log::error(__METHOD__, [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            $this->setError($e->getMessage());
            return null;
        }
    }
}
