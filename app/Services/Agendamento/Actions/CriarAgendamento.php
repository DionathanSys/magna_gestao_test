<?php

namespace App\Services\Agendamento\Actions;

use App\Enum\Agendamento\CategoriaAgendamentoEnum;
use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Models;
use App\Services\Agendamento\AgendamentoHistoricoService;
use App\Traits\UserCheckTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CriarAgendamento
{
    use UserCheckTrait;

    public function handle(array $data): Models\Agendamento
    {
        $data = $this->sanitize($data);

        Log::debug(__METHOD__.' - '.__LINE__, [
            'data' => $data,
        ]);

        $servico = Models\Servico::query()->findOrFail($data['servico_id']);
        $data['posicao'] = $servico->controla_posicao ? ($data['posicao'] ?? null) : null;

        $this->validate($data, $servico);

        $data['categoria'] ??= CategoriaAgendamentoEnum::MANUAL;
        $data['created_by'] = $this->getUserIdChecked();
        $data['updated_by'] = $this->getUserIdChecked();

        $agendamento = Models\Agendamento::query()
            ->createOrFirst([
                'veiculo_id' => $data['veiculo_id'],
                'servico_id' => $data['servico_id'],
                'categoria' => $data['categoria'],
                'parceiro_id' => $data['parceiro_id'] ?? null,
                'plano_preventivo_id' => $data['plano_preventivo_id'] ?? null,
                'posicao' => $data['posicao'] ?? null,
                'status' => StatusOrdemServicoEnum::PENDENTE,
                'observacao' => $data['observacao'] ?? null,
            ], $data);

        app(AgendamentoHistoricoService::class)->registrar(
            agendamento: $agendamento,
            tipoEvento: $agendamento->wasRecentlyCreated ? 'CRIADO' : 'REAPROVEITADO',
            descricao: $agendamento->wasRecentlyCreated ? 'Agendamento criado.' : 'Agendamento existente reaproveitado.',
            dados: [
                'categoria' => $agendamento->categoria?->value,
                'status' => $agendamento->status?->value,
                'veiculo_id' => $agendamento->veiculo_id,
                'servico_id' => $agendamento->servico_id,
                'posicao' => $agendamento->posicao,
            ],
            userId: $this->getUserIdChecked(),
        );

        return $agendamento;

    }

    protected function sanitize(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'ordem_servico_id',
            'veiculo_id',
            'data_agendamento',
            'data_limite',
            'servico_id',
            'posicao',
            'plano_preventivo_id',
            'observacao',
            'parceiro_id',
            'categoria',
        ]));
    }

    protected function validate(array $data, Models\Servico $servico): void
    {
        $validator = Validator::make($data, [
            'veiculo_id' => 'required|exists:veiculos,id',
            'servico_id' => 'required|exists:servicos,id',
            'categoria' => 'nullable|in:'.implode(',', array_column(CategoriaAgendamentoEnum::cases(), 'value')),
            'posicao' => [
                Rule::requiredIf($servico->controla_posicao),
                'nullable',
                'string',
                Rule::in($servico->posicoesPermitidas()),
            ],
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }

    }
}
