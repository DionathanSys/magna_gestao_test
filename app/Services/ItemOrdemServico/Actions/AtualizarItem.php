<?php

namespace App\Services\ItemOrdemServico\Actions;

use App\Enum\OrdemServico\PosicaoItemOrdemServicoEnum;
use App\Models;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AtualizarItem
{
    public function __construct(protected Models\ItemOrdemServico $itemOrdemServico) {}

    public function handle(array $data): Models\ItemOrdemServico
    {
        $data = $this->sanitize($data);

        $servico = Models\Servico::query()->findOrFail($data['servico_id']);
        $data['posicao'] = $servico->controla_posicao ? ($data['posicao'] ?? null) : null;

        $this->validate($data, $servico->controla_posicao);

        $this->itemOrdemServico->update($data);

        return $this->itemOrdemServico;
    }

    protected function sanitize(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'servico_id',
            'plano_preventivo_id',
            'posicao',
            'observacao',
            'status',
        ]));
    }

    protected function exists(array $data): bool
    {
        return Models\ItemOrdemServico::query()
            ->whereKeyNot($this->itemOrdemServico->id)
            ->where('ordem_servico_id', $this->itemOrdemServico->ordem_servico_id)
            ->where('servico_id', $data['servico_id'])
            ->where('posicao', $data['posicao'] ?? null)
            ->exists();
    }

    protected function validate(array $data, bool $controlaPosicao): void
    {
        Validator::make($data, [
            'servico_id' => 'required|exists:servicos,id',
            'plano_preventivo_id' => 'nullable|exists:planos_preventivo,id',
            'posicao' => [
                Rule::requiredIf($controlaPosicao),
                'nullable',
                'string',
                Rule::in(PosicaoItemOrdemServicoEnum::values()),
            ],
            'observacao' => 'nullable|string|max:255',
        ])->validate();

        if ($this->exists($data)) {
            throw new \InvalidArgumentException('Item já existente nesta ordem de serviço.');
        }
    }
}
