<?php

namespace App\Services\Pneus\Actions;

use App\{Models, Enum};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class IncrementCicloVidaPneu
{
    public $message     = '';
    public $hasError    = false;
    public $errors      = [];

    public function handle(int $pneuId): bool
    {
        $this->validate($pneuId);

        if ($this->hasError) {
            return false;
        }

        $pneu = Models\Pneu::find($pneuId);

        Log::debug('Incrementando ciclo de vida do pneu', [
            'pneu_id' => $pneuId,
            'ciclo_vida_atual' => $pneu->ciclo_vida,
        ]);

        $pneu->ciclo_vida += 1;
        $pneu->save();
        $pneu->refresh();

        Log::info('Ciclo de vida do pneu incrementado com sucesso', [
            'pneu_id' => $pneuId,
            'ciclo_vida_novo' => $pneu->ciclo_vida,
        ]);

        return true;
    }

    private function validate(int $pneuId): void
    {
        $validator = Validator::make(['pneu_id' => $pneuId], [
            'pneu_id' => 'required|exists:pneus,id',
        ]);

        if($validator->fails()) {
            $this->hasError = true;
            $this->errors   = $validator->errors()->all();
            $this->message  = 'Dados inválidos para incremento do ciclo de vida do pneu.';

            Log::error('Erro ao validar dados para incremento do ciclo de vida do pneu', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'errors' => $this->errors,
                'data'   => ['pneu_id' => $pneuId]
            ]);
        }

    }
}