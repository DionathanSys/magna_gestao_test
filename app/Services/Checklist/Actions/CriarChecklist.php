<?php

namespace App\Services\Checklist\Actions;

use App\{Models, Services, Enum};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CriarChecklist
{
    public function handle(array $data): ?Models\Checklist
    {
        $this->validate($data);

        $data['itens_corrigidos'] = collect($data['itens'] ?? [])
            ->filter(fn($item) => $item['corrigido'])
            ->toArray();

        $data['itens_verificados'] = collect($data['itens'] ?? [])
            ->filter(fn($item) => isset($item['status']))
            ->toArray();

        $data['pendencias'] = collect($data['itens'] ?? [])
            ->filter(fn($item) => ($item['status'] ?? null) === 'NOK' && empty($item['corrigido']))
            ->toArray();

        $data['created_by'] = Auth::id();
        unset($data['itens']);

        $data['status'] = 'CONCLUIDO';

        $data['periodo'] = \Carbon\Carbon::parse($data['data_referencia'])->startOfMonth();

        return Models\Checklist::create($data);
    }

    private function validate(array $data): void
    {
        $validacao = Validator::make($data, [
            'veiculo_id'        => 'required|exists:veiculos,id',
            'data_referencia'   => 'required|date',
            'quilometragem'     => 'required|integer|min:0',
            'itens'             => 'required|array|min:1',
            'anexos'            => 'nullable|array',
        ],[
            'veiculo_id.required'       => 'O veículo é obrigatório.',
            'veiculo_id.exists'         => 'O veículo selecionado é inválido.',
            'data_referencia.required'  => 'A data de realização é obrigatória.',
            'data_referencia.date'      => 'A data de realização deve ser uma data válida.',
            'quilometragem.required'    => 'A quilometragem é obrigatória.',
            'quilometragem.integer'     => 'A quilometragem deve ser um número inteiro.',
            'quilometragem.min'         => 'A quilometragem deve ser no mínimo 0.',
            'itens.required'            => 'Os itens do checklist são obrigatórios.',
            'itens.array'               => 'Os itens do checklist devem ser um array.',
            'itens.min'                 => 'Deve haver no mínimo 1 item no checklist.',
            'anexos.array'              => 'Os anexos devem ser um array.',
        ]
        );

        if( $validacao->fails()) {
            Log::error('Erro de validação ao criar checklist.', [
                'data' => $data,
                'errors' => $validacao->errors()->all(),
            ]);
            throw new \InvalidArgumentException('Erro de validação ao criar checklist: ' . implode(', ', $validacao->errors()->all()));
        }

        Log::debug('Validação inicial do checklist concluída com sucesso.');
        $this->validateItens($data['itens']);
    }

    private function validateItens(array $itens): void
    {

        foreach ($itens as $item) {
            $validacao = Validator::make($item, [
                'item'          => 'required|string|max:255',
                'status'        => 'nullable|boolean',
                'corrigido'     => 'nullable|boolean',
                'observacoes'   => 'nullable|string',
                'obrigatorio'   => 'boolean',
            ],[
                'item.required'         => 'O nome do item é obrigatório.',
                'item.string'           => 'O nome do item deve ser uma string.',
                'item.max'              => 'O nome do item deve ter no máximo 255 caracteres.',
                'status.boolean'        => 'O status do item deve ser OK ou NOK.',
                'corrigido.boolean'     => 'O campo corrigido deve ser verdadeiro ou falso.',
                'observacoes.string'    => 'As observações devem ser uma string.',
                'obrigatorio.boolean'   => 'O campo obrigatório deve ser verdadeiro ou falso.',
            ]
            );

            if ($validacao->fails()) {
                Log::error('Erro de validação em um dos itens do checklist.', [
                    'item' => $item,
                    'errors' => $validacao->errors()->all(),
                ]);
                throw new \InvalidArgumentException('Erro de validação em um dos itens do checklist: ' . implode(', ', $validacao->errors()->all()));
            }

        }

        Log::debug('Validação dos itens do checklist concluída com sucesso.');
    }
}
