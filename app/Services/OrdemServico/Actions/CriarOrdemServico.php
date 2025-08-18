<?php

namespace App\Services\OrdemServico\Actions;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Enum\OrdemServico\TipoManutencaoEnum;
use App\Models;
use App\Services\Veiculo\VeiculoService;
use App\Traits\UserCheckTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CriarOrdemServico
{
    use UserCheckTrait;

    protected VeiculoService $veiculoService;

    public function __construct()
    {
        $this->veiculoService = new VeiculoService();
    }

    public function handle(array $data): Models\OrdemServico
    {

        $data['created_by'] = $this->getUserIdChecked();
        $data['data_inicio'] = $data['data_inicio'] ?? now();
        $data['status'] = StatusOrdemServicoEnum::PENDENTE;
        $data['status_sankhya'] = StatusOrdemServicoEnum::PENDENTE;
        $data['quilometragem'] = $data['quilometragem'] ?? $this->veiculoService->getQuilometragemAtualByVeiculoId($data['veiculo_id']);

        Log::debug(__METHOD__. ' - ' . __LINE__, [
            'data' => $data,
        ]);

        $this->validate($data);

        return Models\OrdemServico::query()
            ->create($data);
    }

    private function exists(array $data): bool
    {
        return Models\OrdemServico::query()
            ->where('veiculo_id', $data['veiculo_id'])
            ->where('parceiro_id', $data['parceiro_id'] ?? null)
            ->where('status', StatusOrdemServicoEnum::PENDENTE)
            ->exists();
    }

    private function validate(array $data)
    {

        $validator = Validator::make($data, [
            'veiculo_id'        => 'required|exists:veiculos,id',
            'quilometragem'     => 'required|numeric|min:0',
            'parceiro_id'       => 'nullable|exists:parceiros,id',
            'tipo_manutencao'   => 'required',
            'status'            => 'required',
            'status_sankhya'    => 'required',
            'data_inicio'       => 'required|date',
            'created_by'        => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }

        if ($this->exists($data)) {
            throw new \InvalidArgumentException('Ordem de serviço já existe para este veículo e parceiro.');
        }

    }


}
