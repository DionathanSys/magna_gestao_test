<?php

namespace App\Services\Abastecimento\Action;

use App\Models;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CriarAbastecimento
{
    public bool     $hasErrors = false;
    public array    $errors = [];

    public function handle(array $data): ?Models\Abastecimento
    {
        Log::debug('Criando abastecimento', ['data' => $data]);
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

        Log::debug('Último abastecimento', [
            'veiculo_id'    => $veiculoId,
            'quilometragem' => $quilometragem,
            'data_abastecimento' => $dataAbastecimento,
            'abastecimento' => $ultimoAbastecimento
        ]);

        if ($ultimoAbastecimento && $quilometragem < $ultimoAbastecimento->quilometragem) {
            return false;
        }

        return true;
    }
}