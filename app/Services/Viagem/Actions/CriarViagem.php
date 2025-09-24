<?php

namespace App\Services\Viagem\Actions;

use App\Models;
use Illuminate\Support\Facades\Validator;

class CriarViagem
{

    public function handle(array $data): ?Models\Viagem
    {
        $this->validate($data);

        $viagem = Models\Viagem::create($data);

        return $viagem;
    }

    private function validate(array $data): bool
    {
        Validator::make($data, [
            'veiculo_id'            => 'required|exists:veiculos,id',
            'numero_viagem'         => 'required|string|unique:viagens,numero_viagem',
            'documento_transporte'  => 'nullable|string',
            'km_rodado'             => 'required|numeric|min:0',
            'km_pago'               => 'required|numeric|min:0',
            'km_cadastro'           => 'required|numeric|min:0',
            'km_cobrar'             => 'required|numeric|min:0',
            'motivo_divergencia'    => 'required|string',
            'data_competencia'      => 'required|date',
            'data_inicio'           => 'required|date',
            'data_fim'              => 'required|date|after_or_equal:data_inicio',
            'conferido'             => 'boolean',
            'created_by'            => 'required|exists:users,id',
            'updated_by'            => 'required|exists:users,id',
        ])->validate();

        return true;
    }
}
