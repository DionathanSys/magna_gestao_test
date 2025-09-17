<?php

namespace App\Services\Pneus\Actions;

use App\Models;
use App\Enum;
use Illuminate\Support\Facades\Validator;

class RecaparPneu
{
    public function handle(array $data): ?Models\Recapagem
    {
        $this->validate($data);
        return Models\Recapagem::create($data);
    }

    private function validate(array $data): void
    {
        Validator::make($data, [
            'pneu_id'           => 'required|exists:pneus,id',
            'valor'             => 'nullable|numeric|min:0',
            'desenho_pneu_id'   => 'required|exists:desenhos_pneu,id',
            'data_recapagem'    => 'required|date',
        ])->validate();

    }
}
