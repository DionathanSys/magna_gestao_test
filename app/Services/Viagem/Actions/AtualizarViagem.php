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
            'numero_viagem'         => 'required|string',
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
        ], [
            'veiculo_id.required'           => 'O campo Veículo é obrigatório.',
            'veiculo_id.exists'             => 'Veículo não encontrado.',
            'numero_viagem.required'        => 'O campo Viagem é obrigatório.',
            'numero_viagem.string'          => 'O campo Viagem deve ser um texto válido.',
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
        ])->validate();

        if ($this->viagem->conferida) {
            throw new \Exception("Viagem Nº {$this->viagem->numero_viagem} já conferida, não pode ser atualizada.");
        }

        return true;
    }
}
