<?php

namespace App\Services\PreventivaOrdemServico\Actions;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Models;
use Illuminate\Support\Facades\Validator;
use App\Traits\UserCheckTrait;
use Illuminate\Support\Facades\Log;

class CriarVinculo
{
    use UserCheckTrait;

    public function handle(array $data): Models\PlanoManutencaoOrdemServico
    {
        Log::debug(__METHOD__.'-'.__LINE__, ['data' => $data]);

        $this->validate($data);

        return Models\PlanoManutencaoOrdemServico::query()
            ->create($data);
    }

    private function isKmExecucaoValido(array $data): bool
    {
        $ultimoRegistro = Models\PlanoManutencaoOrdemServico::query()
            ->where('plano_preventivo_id', $data['plano_preventivo_id'])
            ->where('veiculo_id', $data['veiculo_id'])
            ->orderBy('km_execucao', 'desc')
            ->first();

        if ($ultimoRegistro) {
            Log::info(__METHOD__.'-'.__LINE__, [
                'ultimo_registro' => $ultimoRegistro,
                'data' => $data,
            ]);
            return $data['km_execucao'] < $ultimoRegistro->km_execucao ? false : true;
        }

        return true;
    }

    private function validate(array $data): void
    {
        $validator = Validator::make($data, [
            'plano_preventivo_id' => 'required|exists:planos_preventivo,id',
            'ordem_servico_id'    => 'nullable|min:1|exists:ordens_servico,id',
            'veiculo_id'          => 'required|exists:veiculos,id',
            'km_execucao'         => 'required|numeric|min:0',
            'data_execucao'       => 'required|date',
        ])->validate();

        if (!$this->isKmExecucaoValido($data)) {
            throw new \InvalidArgumentException('O KM de execução deve ser superior ao último registro para o veículo e plano preventivo informados.');
        }

    }

}
