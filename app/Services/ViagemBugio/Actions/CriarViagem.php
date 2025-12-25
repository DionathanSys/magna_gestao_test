<?php

namespace App\Services\ViagemBugio\Actions;

use App\{Models, Services, Enum};
use App\Enum\ClienteEnum;
use App\Enum\Frete\TipoDocumentoEnum;
use App\Services\ViagemNumberService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
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
        'nro_notas',
        'numero_sequencial',
        'info_adicionais',
    ];

    public function handle(array $data): ?Models\ViagemBugio
    {
        $data = Arr::only($data, $this->fields);

        Log::debug('Dados para criar viagem Bugio', [
            'metodo' => __METHOD__ . '@' . __LINE__,
            'data' => $data
        ]);

        if (empty($data['numero_sequencial'])) {
            $service = new ViagemNumberService();
            $n = $service->next(ClienteEnum::BUGIO->prefixoViagem());
            $data['numero_sequencial'] = $n['numero_sequencial'];
        }

        $this->validate($data);

        return Models\ViagemBugio::create($data);
    }

    public function validate(array $data): void
    {
        $validator = Validator::make($data, [
            'veiculo_id'                        => 'required|integer|exists:veiculos,id',
            'destinos'                          => 'required|array|min:1',
            'destinos.integrado_id'             => 'required|integer|exists:integrados,id',
            'destinos.km_rota'                  => 'required|numeric|min:0',
            'nro_notas'                         => 'required|array|min:1',
            'nro_notas.*'                       => 'required|string|max:20',
            'km_rodado'                         => 'required|numeric|min:0',
            'km_pago'                           => 'required|numeric|min:0',
            'data_competencia'                  => 'required|date',
            'frete'                             => 'required|numeric|min:0',
            'condutor'                          => 'nullable|string|max:155',
            'observacao'                        => 'nullable|string|max:1000',
            'status'                            => 'nullable|string|max:255',
            'created_by'                        => 'required|integer|exists:users,id',
            'numero_sequencial'                 => 'required|integer',
            'info_adicionais'                   => 'required|array',
            'info_adicionais.tipo_documento'    => 'required|string|in:' . implode(',', TipoDocumentoEnum::toSelectArray()),
            'info_adicionais.cte_retroativo'    => 'required|boolean',
            'info_adicionais.cte_referencia'    => 'required_if:info_adicionais.tipo_documento,cte_complemento|nullable|string|max:20',

        ], [
            'veiculo_id.required'           => "O campo 'Veículo' é obrigatório.",
            'veiculo_id.exists'             => "O veículo selecionado não existe.",
            'destinos.required'             => "O campo 'Destinos' é obrigatório.",
            'destinos.size'                 => "O campo 'Destinos' deve conter ao menos um destino.",
            'nro_notas.required'            => "O campo 'Nro Notas' é obrigatório.",
            'nro_notas.size'                => "O campo 'Nro Notas' deve conter ao menos uma nota.",
            'numero_sequencial.required'    => "O campo 'Número Sequencial' é obrigatório.",
            'numero_sequencial.integer'     => "O campo 'Número Sequencial' deve ser um número inteiro.",
            'km_rodado.required'            => "O campo 'Km Rodado' é obrigatório.",
            'km_pago.required'              => "O campo 'Km Pago' é obrigatório.",
            'data_competencia.required'     => "O campo 'Data de Competência' é obrigatório.",
            'frete.required'                => "O campo 'Frete' é obrigatório.",
            'created_by.required'           => "O campo 'Criado Por' é obrigatório.",
            'created_by.exists'             => "O usuário criador não existe.",
            'info_adicionais.required'      => "O campo 'Informações Adicionais' é obrigatório.",
            'info_adicionais.tipo_documento.required'   => "O campo 'Tipo de Documento' em Informações Adicionais é obrigatório.",
            'info_adicionais.tipo_documento.in'         => "O campo 'Tipo de Documento' em Informações Adicionais deve ser 'cte' ou 'nfse'.",
            'info_adicionais.cte_retroativo.required'   => "O campo 'CTE Retroativo' em Informações Adicionais é obrigatório.",
            'info_adicionais.cte_referencia.required'   => "O campo 'CTE Referência' em Informações Adicionais é obrigatório.",
            'info_adicionais.cte_referencia.max'        => "O campo 'CTE Referência' em Informações Adicionais deve ter no máximo 20 caracteres.",
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }

    }
}
