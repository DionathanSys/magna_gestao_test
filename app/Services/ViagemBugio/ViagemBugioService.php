<?php

namespace App\Services\ViagemBugio;

use App\{Models, Services, Enum};
use App\Traits\ServiceResponseTrait;
use App\Traits\UserCheckTrait;
use Illuminate\Support\Facades\Log;

class ViagemBugioService
{

    use ServiceResponseTrait, UserCheckTrait;

    public function criarViagem(array $data): ?Models\ViagemBugio
    {
        try {

            $action = new Actions\CriarViagem();
            $viagem = $action->handle($data);
            $this->setSuccess('Viagem criada com sucesso!');
            return $viagem;
        } catch (\Exception $e) {
            Log::error(__METHOD__ . '-' . __LINE__, [
                'error'     => $e->getMessage(),
                'data'      => $data,
                'user_id'   => $this->getUserIdChecked(),
            ]);
            $this->setError('Erro ao criar viagem', ['error' => $e->getMessage()]);
            return null;
        }
    }

    
}
