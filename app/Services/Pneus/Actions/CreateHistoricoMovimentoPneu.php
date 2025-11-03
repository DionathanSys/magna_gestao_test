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

    /**
     * Cria o registro de historico de movimento do pneu.
     * Lance exceção em caso de validação/falha para que o chamador trate rollback.
     *
     * @param array $data
     * @return HistoricoMovimentoPneu
     */
    public function handle(array $data): Models\HistoricoMovimentoPneu
    {
        $validator = Validator::make($data, [
            'pneu_id'         => 'required|integer|exists:pneus,id',
            'veiculo_id'      => 'required|integer|exists:veiculos,id',
            'eixo'            => 'required|numeric|min:1|max:4',
            'posicao'         => 'required|string',
            'motivo'          => 'required|string|in:' . implode(',', Enum\Pneu\MotivoMovimentoPneuEnum::cases()),
            'sulco_movimento' => 'required|numeric',
            'ciclo_vida'      => 'required|integer|min:0|max:3',
            'data_inicial'    => 'required|date',
            'data_final'      => 'required|date',
            'km_inicial'      => 'required|integer',
            'km_final'        => 'required|integer',
            'observacao'      => 'nullable|string',
            'anexos'          => 'nullable|array',
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

        return Models\HistoricoMovimentoPneu::create($data);
    }
}