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

            $data['destinos'] = $this->mutateIntegrados($data['integrados']);
            unset($data['integrados']);


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

    private function mutateIntegrados(array $integrados)
    {
        $destinos = [];

        foreach ($integrados as &$integrado) {
            Log::debug(__METHOD__ . '-' . __LINE__, [
                'integrado' => $integrado,
            ]);
            $destino['integrado_id']    = $integrado['integrado_id'];
            $destino['km_rota']         = $integrado['km_rota'];

        }
        return $integrados;
    }
}
