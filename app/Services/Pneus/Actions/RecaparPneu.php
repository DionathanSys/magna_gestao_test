<?php

namespace App\Services\Pneus\Actions;

use App\Models;
use App\Enum;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RecaparPneu
{
    public $message     = '';
    public $hasError    = false;
    public $errors      = [];

    public function handle(array $data): ?Models\Recapagem
    {
        $this->validate($data);
        
        if ($this->hasError) {
            return null;
        }

        return Models\Recapagem::create($data);
    }

    private function validate(array $data): void
    {
        Log::debug('Validando dados para recapagem de pneu', [
            'metodo' => __METHOD__ . '@' . __LINE__,
            'data'   => $data
        ]);

        $validator = Validator::make($data, [
            'pneu_id'           => 'required|exists:pneus,id',
            'valor'             => 'nullable|numeric|min:0',
            'desenho_pneu_id'   => 'required|exists:desenhos_pneu,id',
            'ciclo_vida'        => 'integer|min:1|max:3',
            'data_recapagem'    => 'required|date',
        ], [
            'pneu_id.required'          => 'O campo Pneu é obrigatório.',
            'pneu_id.exists'            => 'O Pneu informado não existe.',
            'valor.numeric'             => 'O campo Valor deve ser um número válido.',
            'valor.min'                 => 'O campo Valor deve ser maior ou igual a 0.',
            'desenho_pneu_id.required'  => 'O campo Desenho do Pneu é obrigatório.',
            'desenho_pneu_id.exists'    => 'O Desenho do Pneu informado não existe.',
            'ciclo_vida.integer'        => 'O campo Ciclo de Vida deve ser um número inteiro.',
            'ciclo_vida.min'            => 'O campo Ciclo de Vida deve ser no mínimo 1.',
            'ciclo_vida.max'            => 'O campo Ciclo de Vida deve ser no máximo 3.',
            'data_recapagem.required'   => 'O campo Data da Recapagem é obrigatório.',
            'data_recapagem.date'       => 'O campo Data da Recapagem deve ser uma data válida.',
        ]);

        if ($validator->fails()) {
            $this->hasError = true;
            $this->errors   = $validator->errors()->all();
            $this->message  = 'Dados inválidos para recapagem de pneu.';

            dump('chegou aqui quase log', $data, $validator->errors()->all());
            Log::error('Erro ao validar dados para recapagem de pneu', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'errors' => $this->errors,
                'data'   => $data
            ]);
            return;
        }
        
        if ($this->validarStatusPneu($data['pneu_id'] ?? 0) === false) {
            return;
        }
    }

    private function validarStatusPneu(int $pneuId): bool
    {
        $pneu = Models\Pneu::query()
            ->select('id', 'status')
            ->find($pneuId);

        if (in_array($pneu->status->value, [Enum\Pneu\StatusPneuEnum::EM_USO, Enum\Pneu\StatusPneuEnum::SUCATA])) {
            $this->hasError = true;
            $this->message  = 'Status do pneu inválido para recapagem. Status atual: ' . $pneu->status->label;

            Log::error('Erro ao validar status do pneu para recapagem', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'pneu'   => $pneu,
            ]);

            return false;
        }

        return true;
    }
}
