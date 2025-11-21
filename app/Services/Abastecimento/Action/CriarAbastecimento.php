<?php

namespace App\Services\Abastecimento\Action;

use App\Jobs\VincularRegistroResultadoJob;
use App\Models;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CriarAbastecimento
{
    public bool     $hasErrors = false;
    public array    $errors = [];

    public function handle(array $data): ?Models\Abastecimento
    {
        Log::debug('Iniciando criação de abastecimento', [
            'data' => $data,
        ]);
        
        $this->validate($data);

        if (!$this->hasErrors) {
           return Models\Abastecimento::create($data);
        }

        return null;
    }

    private function validate(array $data): void
    {
        $validator = Validator::make($data, [
            'id_abastecimento'      => 'required|unique:abastecimentos,id_abastecimento',
            'veiculo_id'            => 'required|exists:veiculos,id',
            'quilometragem'         => 'required|integer|min:0',
            'posto_combustivel'     => 'required|string|max:150',
            'tipo_combustivel'      => 'required|string|max:50',
            'data_abastecimento'    => 'required|date',
            'quantidade'            => 'required|numeric|min:0',
            'preco_por_litro'       => 'required|numeric|min:0',
        ], [
            'id_abastecimento.required'     => 'O campo id_abastecimento é obrigatório.',
            'id_abastecimento.unique'       => 'O id_abastecimento informado já existe.',
            'veiculo_id.required'           => 'O campo veiculo_id é obrigatório.',
            'veiculo_id.exists'             => 'O veiculo_id informado não existe.',
            'quilometragem.required'        => 'O campo quilometragem é obrigatório.',
            'quilometragem.integer'         => 'O campo quilometragem deve ser um número inteiro.',
            'quilometragem.min'             => 'O campo quilometragem deve ser maior ou igual a 0.',
            'posto_combustivel.required'    => 'O campo posto_combustivel é obrigatório.',
            'posto_combustivel.string'      => 'O campo posto_combustivel deve ser uma string.',
            'posto_combustivel.max'         => 'O campo posto_combustivel deve ter no máximo 150 caracteres.',
            'tipo_combustivel.required'     => 'O campo tipo_combustivel é obrigatório.',
            'tipo_combustivel.string'       => 'O campo tipo_combustivel deve ser uma string.',
            'tipo_combustivel.max'          => 'O campo tipo_combustivel deve ter no máximo 50 caracteres.',
            'data_abastecimento.required'   => 'O campo data_abastecimento é obrigatório.',
            'data_abastecimento.date'       => 'O campo data_abastecimento deve ser uma data válida.',
            'quantidade.required'           => 'O campo quantidade é obrigatório.',
            'quantidade.numeric'            => 'O campo quantidade deve ser um número.',
            'quantidade.min'                => 'O campo quantidade deve ser maior ou igual a 0.',
            'preco_por_litro.required'      => 'O campo preco_por_litro é obrigatório.',
            'preco_por_litro.numeric'       => 'O campo preco_por_litro deve ser um número.',
            'preco_por_litro.min'           => 'O campo preco_por_litro deve ser maior ou igual a 0.',
        ]);

        if ($validator->fails()) {
            $this->hasErrors = true;
            $this->errors = $validator->errors()->all();
        }

        if(!$this->validarQuilometragem($data['veiculo_id'], $data['quilometragem'], $data['data_abastecimento'])) {
            $this->hasErrors = true;
            $this->errors[] = 'A quilometragem informada é menor que a do último abastecimento registrado.';
        }
    }

    private function validarQuilometragem(int $veiculoId, int $quilometragem, string $dataAbastecimento): bool
    {
        $ultimoAbastecimento = Models\Abastecimento::query()
            ->anterioresAData($dataAbastecimento, $veiculoId)
            ->first();

        if ($ultimoAbastecimento && $quilometragem < $ultimoAbastecimento->quilometragem) {
            return false;
        }

        return true;
    }
}