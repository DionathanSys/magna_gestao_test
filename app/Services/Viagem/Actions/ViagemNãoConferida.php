<?php

namespace App\Services\Viagem\Actions;

use App\Enum;
use App\Models;
use Illuminate\Support\Facades\Auth;

class ViagemNãoConferida
{

    public function handle(Models\Viagem $viagem): Models\Viagem
    {
        $this->validate($viagem);

        $viagem->update([
            'conferido' => false,
            'updated_by' => Auth::user()->id,
            'checked_by' => null,
        ]);

        return $viagem;
    }

    private function validate(Models\Viagem $viagem): void
    {
        if ($viagem->checked_by != Auth::user()->id && ! Auth::user()->is_admin) {
            throw new \InvalidArgumentException('Apenas o usuário que conferiu a viagem pode marcá-la como não conferida.');
        }
    }
}
