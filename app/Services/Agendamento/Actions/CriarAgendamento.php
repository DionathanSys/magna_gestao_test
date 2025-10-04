<?php

namespace App\Services\Agendamento\Actions;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Models;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\UserCheckTrait;
use Illuminate\Support\Facades\Log;

class CriarAgendamento
{
    use UserCheckTrait;

    public function handle(array $data): Models\Agendamento
    {
        Log::debug(__METHOD__. ' - ' . __LINE__, [
            'data' => $data,
        ]);

        $this->validate($data);

        $data['created_by'] = $this->getUserIdChecked();
        $data['updated_by'] = $this->getUserIdChecked();

        return Models\Agendamento::query()
            ->createOrFirst([
                'veiculo_id'            => $data['veiculo_id'],
                'servico_id'            => $data['servico_id'],
                'parceiro_id'           => $data['parceiro_id'] ?? null,
                'plano_preventivo_id'   => $data['plano_preventivo_id'] ?? null,
                'posicao'               => $data['posicao'] ?? null,
                'status'                => StatusOrdemServicoEnum::PENDENTE,
                'observacao'            => $data['observacao'] ?? null,
            ]
            , $data);

    }

    protected function validate(array $data): void
    {
        $validator = Validator::make($data, [
            'veiculo_id' => 'required|exists:veiculos,id',
            'servico_id' => 'required|exists:servicos,id',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }

    }
}
