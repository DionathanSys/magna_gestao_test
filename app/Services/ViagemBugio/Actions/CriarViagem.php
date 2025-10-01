<?php

namespace App\Services\ViagemBugio\Actions;

use App\{Models, Services, Enum};
use Illuminate\Support\Facades\Validator;

class CriarViagem
{
    public function handle(array $data): ?Models\ViagemBugio
    {
        $this->validate($data);
        return Models\ViagemBugio::create($data);
    }

    public function validate(array $data): void
    {
        Validator::make($data, [
            'destinos'          => 'required|array|size:min:1',
            'data_competencia'  => 'required|date',
            'km_rodado'         => 'required|numeric|min:0',
            'km_pago'           => 'required|numeric|min:0',
            'frete'             => 'required|numeric|min:0',
            'condutor'          => 'nullable|string|max:155',
            'veiculo_id'        => 'required|integer|exists:veiculos,id',
            'observacao'        => 'nullable|string|max:1000',
            'status'            => 'nullable|string|max:255',
            'created_by'        => 'required|integer|exists:users,id',
        ])->validate();
    }
}
