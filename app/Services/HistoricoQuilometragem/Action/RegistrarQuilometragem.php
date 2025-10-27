<?php

namespace App\Services\HistoricoQuilometragem\Action;

use Illuminate\Support\Facades\Validator;
use App\Models;

class RegistrarQuilometragem
{
    public bool     $hasErrors = false;
    public array    $errors = [];

    public function handle(array $data): ?Models\HistoricoQuilometragem
    {
        $this->validate($data);

        if (!$this->hasErrors) {    
            return Models\HistoricoQuilometragem::create($data);
        }

        return null;
    }

    private function validate(array $data): void
    {
        $validator = Validator::make($data, [
            'veiculo_id'        => 'required|exists:veiculos,id',
            'data_referencia'   => 'required|date',
            'quilometragem'     => 'required|integer|min:0',
        ], [
            'veiculo_id.required'        => 'O veículo é obrigatório.',
            'veiculo_id.exists'          => 'O veículo informado não existe.',
            'data_referencia.required'   => 'A data de referência é obrigatória.',
            'data_referencia.date'       => 'A data de referência deve ser uma data válida.',
            'quilometragem.required'     => 'A quilometragem é obrigatória.',
            'quilometragem.integer'      => 'A quilometragem deve ser um número inteiro.',
            'quilometragem.min'          => 'A quilometragem deve ser maior ou igual a 0.',
        ]);

        if ($validator->fails()) {
            $this->hasErrors = true;
            $this->errors = $validator->errors()->all();
        }
    }
}