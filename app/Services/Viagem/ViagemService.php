<?php

namespace App\Services\Viagem;

use App\DTO\ViagemDTO;
use App\Models;
use App\Services;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Log;

class ViagemService
{

    use ServiceResponseTrait;

    public function __construct(
        // protected Services\Veiculo\VeiculoService $veiculoService
    ) {}

    public function create(array $data): ?Models\Viagem
    {
        try {

            $action = new Actions\CriarViagem();
            $viagem = $action->handle($data);
            $this->setSuccess('Viagem criada com sucesso!');
            return $viagem;
        } catch (\Exception $e) {
            Log::error(__METHOD__, [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            $this->setError($e->getMessage());
            return null;
        }

    }

    public function marcarViagemComoConferida(Models\Viagem $viagem)
    {

        try {
            $viagem = (new Actions\ViagemConferida())->handle($viagem);
            $this->setSuccess('Viagem conferida com sucesso!');
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

    public function getKmCadastroIntegrado()
    {

    }

}
