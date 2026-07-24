<?php

namespace App\Services\ItemOrdemServico\Actions;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Models;
use App\Traits\UserCheckTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CriarItem
{
    use UserCheckTrait;

    public function handle(array $data): Models\ItemOrdemServico
    {
        $data = $this->sanitize($data);

        Log::debug(__METHOD__.' - '.__LINE__, [
            'data' => $data,
        ]);

        $servico = Models\Servico::query()->findOrFail($data['servico_id']);

        $data['created_by'] = $this->getUserIdChecked();
        $data['status'] = StatusOrdemServicoEnum::PENDENTE;
        $data['posicao'] = $servico->controla_posicao ? ($data['posicao'] ?? null) : null;

        $this->validate($data, $servico);

        return Models\ItemOrdemServico::query()
            ->create($data);
    }

    protected function sanitize(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'ordem_servico_id',
            'servico_id',
            'plano_preventivo_id',
            'posicao',
            'observacao',
        ]));
    }

    private function exists(array $data): bool
    {
        return Models\ItemOrdemServico::query()
            ->where('ordem_servico_id', $data['ordem_servico_id'])
            ->where('servico_id', $data['servico_id'])
            ->where('posicao', $data['posicao'] ?? null)
            ->exists();
    }

    private function validate(array $data, Models\Servico $servico): void
    {
        $validator = Validator::make($data, [
            'ordem_servico_id' => 'required|exists:ordens_servico,id',
            'servico_id' => 'required|exists:servicos,id',
            'plano_preventivo_id' => 'nullable|exists:planos_preventivo,id',
            'posicao' => [
                Rule::requiredIf($servico->controla_posicao),
                'nullable',
                'string',
                Rule::in($servico->posicoesPermitidas()),
            ],
            'observacao' => 'nullable|string|max:255',
        ])->validate();

        if ($this->exists($data)) {
            throw new \InvalidArgumentException('Item já existente nesta ordem de serviço.');
        }
    }
}
