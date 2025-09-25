<?php

namespace App\Services\Viagem\Actions;

use App\Models;
use Illuminate\Support\Facades\Validator;

class AtualizarViagem
{

    public function __construct(protected Models\Viagem $viagem)
    {

    }

    public function handle(array $data): ?Models\Viagem
    {
        $this->validate($data);

        $this->viagem->update($data);

        return $this->viagem;
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

        if ($this->viagem->conferida) {
            throw new \Exception("Viagem Nº {$this->viagem->numero_viagem} já conferida, não pode ser atualizada.");
        }

        return true;
    }
}
