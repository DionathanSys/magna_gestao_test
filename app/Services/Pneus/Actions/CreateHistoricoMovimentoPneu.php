<?php

namespace App\Services\Pneus\Actions;

use App\{Models, Enum};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CreateHistoricoMovimentoPneu
{

    public $message     = '';
    public $hasError    = false;
    public $errors      = [];

    public function handle(array $data): ?Models\HistoricoMovimentoPneu
    {
        $this->validate($data);

        if ($this->hasError) {
            return null;
        }

        return Models\HistoricoMovimentoPneu::create($data);
    }

    private function validate(array $data): void
    {
        $validator = Validator::make($data, [
            'pneu_id'         => 'required|integer|exists:pneus,id',
            'veiculo_id'      => 'required|integer|exists:veiculos,id',
            'eixo'            => 'required|numeric|min:1|max:4',
            'posicao'         => 'required|string',
            'motivo'          => 'required|string',
            'sulco_movimento' => 'required|numeric',
            'ciclo_vida'      => 'required|integer|min:0|max:3',
            'data_inicial'    => 'required|date',
            'data_final'      => 'required|date',
            'km_inicial'      => 'required|integer',
            'km_final'        => 'required|integer',
            'observacao'      => 'nullable|string',
            'anexos'          => 'nullable|array',
        ], [
            'pneu_id.required'        => 'O campo Pneu é obrigatório.',
            'pneu_id.exists'          => 'O Pneu informado não existe.',
            'veiculo_id.required'     => 'O campo Veículo é obrigatório.',
            'veiculo_id.exists'       => 'O Veículo informado não existe.',
            'eixo.required'           => 'O campo Eixo é obrigatório.',
            'eixo.min'                => 'O campo Eixo deve ser no mínimo :min.',
            'eixo.max'                => 'O campo Eixo deve ser no máximo :max.',
            'posicao.required'        => 'O campo Posição é obrigatório.',
            'motivo.required'         => 'O campo Motivo é obrigatório.',
            'motivo.in'               => 'O Motivo informado é inválido.',
            'sulco_movimento.required'=> 'O campo Sulco é obrigatório.',
            'data_inicial.required'   => 'O campo Data Inicial é obrigatório.',
            'data_final.required'     => 'O campo Data Final é obrigatório.',
            'km_inicial.required'     => 'O campo KM Inicial é obrigatório.',
            'km_final.required'       => 'O campo KM Final é obrigatório.',
        ]);

        if ($validator->fails()) {
            $this->hasError = true;
            $this->errors = $validator->errors()->all();
            $this->message = 'Dados inválidos para histórico de movimento do pneu.';

            Log::error('Validação falhou ao criar historico movimento pneu', [
                'errors' => $validator->errors()->all(),
                'input'  => $data,
            ]);
        }
    }
}