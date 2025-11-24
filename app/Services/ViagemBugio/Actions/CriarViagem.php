<?php

namespace App\Services\ViagemBugio\Actions;

use App\{Models, Services, Enum};
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class CriarViagem
{

    protected $fields = [
        'veiculo_id',
        'destinos',
        'km_rodado',
        'km_pago',
        'data_competencia',
        'frete',
        'condutor',
        'observacao',
        'status',
        'created_by',
    ];

    public function handle(array $data): ?Models\ViagemBugio
    {
        $data = Arr::only($data, $this->fields);
        $this->validate($data);
        return Models\ViagemBugio::create($data);
    }

    public function validate(array $data): void
    {
        $validator = Validator::make($data, [
            'veiculo_id'        => 'required|integer|exists:veiculos,id',
            'destinos'          => 'required|array|min:1',
            'destinos.*.integrado_id'   => 'required|integer|exists:integrados,id',
            'destinos.*.km_rota'        => 'required|numeric|min:0',
            'km_rodado'         => 'required|numeric|min:0',
            'km_pago'           => 'required|numeric|min:0',
            'data_competencia'  => 'required|date',
            'frete'             => 'required|numeric|min:0',
            'condutor'          => 'nullable|string|max:155',
            'observacao'        => 'nullable|string|max:1000',
            'status'            => 'nullable|string|max:255',
            'created_by'        => 'required|integer|exists:users,id',
        ], [
            'veiculo_id.required'       => "O campo 'Veículo' é obrigatório.",
            'veiculo_id.exists'         => "O veículo selecionado não existe.",
            'destinos.required'         => "O campo 'Destinos' é obrigatório.",
            'destinos.size'             => "O campo 'Destinos' deve conter ao menos um destino.",
            'km_rodado.required'        => "O campo 'Km Rodado' é obrigatório.",
            'km_pago.required'          => "O campo 'Km Pago' é obrigatório.",
            'data_competencia.required' => "O campo 'Data de Competência' é obrigatório.",
            'frete.required'            => "O campo 'Frete' é obrigatório.",
            'created_by.required'       => "O campo 'Criado Por' é obrigatório.",
            'created_by.exists'         => "O usuário criador não existe.",
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }

    }
}
