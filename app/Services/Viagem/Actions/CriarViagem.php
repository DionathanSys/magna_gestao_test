<?php

namespace App\Services\Viagem\Actions;

use App\Models;
use App\Traits\UserCheckTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CriarViagem
{
    use UserCheckTrait;

    public array $errors = [];

    public bool $hasError = false;

    protected array $allowedFields = [
        'veiculo_id',
        'unidade_negocio',
        'cliente',
        'numero_viagem',
        'documento_transporte',
        'km_rodado',
        'km_pago',
        'km_cadastro',
        'km_cobrar',
        'motivo_divergencia',
        'data_competencia',
        'data_inicio',
        'data_fim',
        'qtde_destino_viagem',
        'conferido',
        'condutor',
        'created_by',
        'updated_by',
    ];

    public function handle(array $data): ?Models\Viagem
    {
        // Filtra apenas campos permitidos
        $filteredData = Arr::only($data, $this->allowedFields);

        $this->validate($filteredData);

        if ($this->hasError === false) {

            $data = [
                ...$filteredData,
                'created_by' => $this->getUserIdChecked(),
                'updated_by' => $this->getUserIdChecked(),
            ];

            Log::info(__METHOD__ . '@' . __LINE__, [
                'data' => $data,
            ]);
            
            $viagem = Models\Viagem::create($data);
            return $viagem;
        }
        return null;
    }

    private function validate(array $data): void
    {
        $validator = Validator::make($data, [
            'veiculo_id'            => 'required|exists:veiculos,id',
            'unidade_negocio'       => 'required|string',
            'cliente'               => 'nullable|string',
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
            'condutor'              => 'nullable|string',
        ], [
            'veiculo_id.required'           => 'O campo Veículo é obrigatório.',
            'veiculo_id.exists'             => 'Veículo não encontrado.',
            'unidade_negocio.required'      => 'O campo Unidade de Negócio é obrigatório.',
            'unidade_negocio.string'        => 'O campo Unidade de Negócio deve ser um texto válido.',
            'cliente.string'                => 'O campo Cliente deve ser um texto válido.',
            'numero_viagem.required'        => 'O campo Viagem é obrigatório.',
            'numero_viagem.string'          => 'O campo Viagem deve ser um texto válido.',
            'numero_viagem.unique'          => 'O número da Viagem já está em uso.',
            'km_rodado.required'            => 'O campo Km Rodado é obrigatório.',
            'km_rodado.numeric'             => 'O campo Km Rodado deve ser um número válido.',
            'km_rodado.min'                 => 'O campo Km Rodado deve ser maior ou igual a 0.',
            'km_pago.required'              => 'O campo Km Pago é obrigatório.',
            'km_pago.numeric'               => 'O campo Km Pago deve ser um número válido.',
            'km_pago.min'                   => 'O campo Km Pago deve ser maior ou igual a 0.',
            'km_cadastro.required'          => 'O campo Km Cadastro é obrigatório.',
            'km_cadastro.numeric'           => 'O campo Km Cadastro deve ser um número válido.',
            'km_cadastro.min'               => 'O campo Km Cadastro deve ser maior ou igual a 0.',
            'km_cobrar.required'            => 'O campo Km Cobrar é obrigatório.',
            'km_cobrar.numeric'             => 'O campo Km Cobrar deve ser um número válido.',
            'km_cobrar.min'                 => 'O campo Km Cobrar deve ser maior ou igual a 0.',
            'motivo_divergencia.required'   => 'O campo Motivo Divergência é obrigatório.',
            'motivo_divergencia.string'     => 'O campo Motivo Divergência deve ser um texto válido.',
            'data_competencia.required'     => 'O campo Data Competência é obrigatório.',
            'data_competencia.date'         => 'O campo Data Competência deve ser uma data válida.',
            'data_inicio.required'          => 'O campo Data Início é obrigatório.',
            'data_inicio.date'              => 'O campo Data Início deve ser uma data válida.',
            'data_fim.required'             => 'O campo Data Fim é obrigatório.',
            'data_fim.date'                 => 'O campo Data Fim deve ser uma data válida.',
            'data_fim.after_or_equal'       => 'O campo Data Fim deve ser uma data posterior ou igual à Data Início.',
            'conferido.boolean'             => 'O campo Conferido deve ser verdadeiro ou falso.',
            'created_by.required'           => 'O campo Criado Por é obrigatório.',
            'created_by.exists'             => 'Usuário Criador não encontrado.',
            'updated_by.required'           => 'O campo Atualizado Por é obrigatório.',
            'updated_by.exists'             => 'Usuário Atualizador não encontrado.',
            'condutor.string'              => 'O campo Condutor deve ser um texto válido.',
        ]);

        if ($validator->fails()) {
            Log::error(__METHOD__ . '@' . __LINE__, [
                'error' => $validator->errors()->all(),
                'data'  => $data,
            ]);
            $this->errors = $validator->errors()->all();
            $this->hasError = true;
            return;
        }

        return;
    }
}
