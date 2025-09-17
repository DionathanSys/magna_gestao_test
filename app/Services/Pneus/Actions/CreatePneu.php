<?php

namespace App\Services\Pneus\Actions;

use App\Models;
use App\Enum;
use Illuminate\Support\Facades\Validator;

class CreatePneu
{
    public function handle(array $data): ?Models\Pneu
    {
        $this->validate($data);
        return Models\Pneu::create($data);
    }

    private function exists(int $numeroFogoPneu): bool
    {
        if (Models\Pneu::where('numero_fogo', $numeroFogoPneu)->exists()) {
            return true;
        }

        return false;
    }

    private function validate(array $data): void
    {
        Validator::make($data, [
            'numero_fogo'       => 'required|integer',
            'medida'            => 'required',
            'marca'             => 'required',
            'modelo'            => 'nullable',
            'valor'             => 'nullable|numeric|min:0',
            'desenho_pneu_id'   => 'required|exists:desenhos_pneu,id',
            'local'             => 'required|in:' . implode(',', array_column(Enum\Pneu\LocalPneuEnum::cases(), 'value')),
            'status'            => 'required|in:' . implode(',', array_column(Enum\Pneu\StatusPneuEnum::cases(), 'value')),
            'ciclo_vida'        => 'required|integer|min:0',
            'data_aquisicao'    => 'required|date',
        ])->validate();

        if ($this->exists($data['numero_fogo'])) {
            throw new \InvalidArgumentException("Pneu nº de fogo {$data['numero_fogo']} já existe.");
        }
    }
}
