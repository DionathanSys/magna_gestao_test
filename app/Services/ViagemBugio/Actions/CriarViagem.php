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
        $validator = Validator::make($data, [
            'veiculo_id'        => 'required|integer|exists:veiculos,id',
            'destinos'          => 'required|array|size:min:1',
            'km_rodado'         => 'required|numeric|min:0',
            'km_pago'           => 'required|numeric|min:0',
            'data_competencia'  => 'required|date',
            'frete'             => 'required|numeric|min:0',
            'condutor'          => 'nullable|string|max:155',
            'observacao'        => 'nullable|string|max:1000',
            'status'            => 'nullable|string|max:255',
            'created_by'        => 'required|integer|exists:users,id',
        ], [
            
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }

        $validatorDestinos = Validator::make(['destinos' => $data['destinos']], [
            'destinos.*.integrado_id'   => 'required|integer|exists:integrados,id',
            'destinos.*.km_rota'        => 'required|numeric|min:0',
        ]);
    }
}
