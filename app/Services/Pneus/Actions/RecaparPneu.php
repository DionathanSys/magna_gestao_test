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
            'data_recapagem'    => 'required|date',
        ]);

        if ($validator->fails()) {
            $this->hasError = true;
            $this->errors   = $validator->errors()->all();
            $this->message  = 'Dados inválidos para recapagem de pneu.';

            Log::error('Erro ao validar dados para recapagem de pneu', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'errors' => $this->errors,
                'data'   => $data
            ]);
        }

        if ($this->validarStatusPneu($data['pneu_id']) === false) {
            Log::error('Erro ao validar status do pneu para recapagem', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'pneu_id' => $data['pneu_id']
            ]);
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
            return false;
        }

        return true;
    }
}
