<?php

namespace App\Services\HistoricoQuilometragem\Action;

use App\Models;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RegistrarQuilometragem
{
    public bool $hasErrors = false;

    public array $errors = [];

    public function handle(array $data): ?Models\HistoricoQuilometragem
    {
        $this->validate($data);

        if (! $this->hasErrors) {
            return Models\HistoricoQuilometragem::create($data);
        }

        return null;
    }

    private function validate(array $data): void
    {
        $validator = Validator::make($data, [
            'veiculo_id' => 'required|exists:veiculos,id',
            'data_referencia' => 'required|date',
            'quilometragem' => 'required|integer|min:0',
        ], [
            'veiculo_id.required' => 'O veículo é obrigatório.',
            'veiculo_id.exists' => 'O veículo informado não existe.',
            'data_referencia.required' => 'A data de referência é obrigatória.',
            'data_referencia.date' => 'A data de referência deve ser uma data válida.',
            'quilometragem.required' => 'A quilometragem é obrigatória.',
            'quilometragem.integer' => 'A quilometragem deve ser um número inteiro.',
            'quilometragem.min' => 'A quilometragem deve ser maior ou igual a 0.',
        ]);

        $validator->after(function ($validator) use ($data): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $ultimaQuilometragem = Models\HistoricoQuilometragem::query()
                ->where('veiculo_id', $data['veiculo_id'])
                ->whereDate('data_referencia', '<=', $data['data_referencia'])
                ->orderByDesc('data_referencia')
                ->orderByDesc('id')
                ->value('quilometragem');

            if ($ultimaQuilometragem !== null && $data['quilometragem'] < $ultimaQuilometragem) {
                $validator->errors()->add(
                    'quilometragem',
                    'A quilometragem nao pode ser menor que a ultima registrada ate a data de referencia.'
                );
            }
        });

        if ($validator->fails()) {
            Log::warning('Validação falhou ao registrar quilometragem', [
                'errors' => $validator->errors()->all(),
                'data' => $data,
            ]);
            $this->hasErrors = true;
            $this->errors = $validator->errors()->all();
        }
    }
}
