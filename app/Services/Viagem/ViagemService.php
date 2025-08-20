<?php

namespace App\Services\Viagem;

use App\Models;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Log;

class ViagemService
{

    use ServiceResponseTrait;

    public function __construct()
    {

    }

    public function marcarViagemComoConferida(Models\Viagem $viagem)
    {

        try {
            $viagem = (new Actions\ViagemConferida())->handle($viagem);
            $this->setSuccess('Viagem conferida com sucesso!');
            dd($viagem);
            return $viagem;
        } catch (\Exception $e) {
            dd($e);
            Log::error(__METHOD__, [
                'error' => $e->getMessage(),
                'data' => $viagem->toArray(),
            ]);
            $this->setError($e->getMessage());
            return null;
        }
    }

    public function marcarViagemComoNãoConferida(Models\Viagem $viagem)
    {

        try {
            $viagem = (new Actions\ViagemNãoConferida())->handle($viagem);
            $this->setSuccess('Viagem marcada como não conferida!');
            return $viagem;
        } catch (\Exception $e) {
            Log::error(__METHOD__, [
                'error' => $e->getMessage(),
                'data' => $viagem->toArray(),
            ]);
            $this->setError($e->getMessage());
            return null;
        }
    }

}
