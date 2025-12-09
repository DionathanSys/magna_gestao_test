<?php

namespace App\Services\CteService;

use App\{Models, Services, Enum};
use App\DTO\PayloadCteDTO;
use App\Services\CteService\Actions;
use App\Traits\ServiceResponseTrait;
use App\Traits\UserCheckTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CteService
{
    use ServiceResponseTrait, UserCheckTrait;

    public function solicitarCtePorEmail(array $data)
    {

        try {

            Log::debug("dados recebidos do componente livewire", [
                'método' => __METHOD__.'-'.__LINE__,
                'data' => $data,
                'db_config.valor-quilometro' => db_config('config-bugio.valor-quilometro'),
                'user_id' => $data['created_by'] ?? null,
            ]);

            $data['motorista']['nome'] = collect(db_config('config-bugio.motoristas'))->firstWhere('cpf', $data['motorista']['cpf'] ?? null)['motorista'] ?? null;
            $data['valor_frete'] = $data['km_total'] * db_config('config-bugio.valor-quilometro', 0);

            $payloadDto = PayloadCteDTO::fromArray($data);

            Log::debug("dados do payload DTO", [
                'método' => __METHOD__.'-'.__LINE__,
                'payloadDto' => $payloadDto->toArray(),
                'user_id' => $data['created_by'],
            ]);

            $action = new Actions\EnviarSolicitacaoCte();
            $action->handle($payloadDto);

            $this->setSuccess('Solicitação de CTe enviada com sucesso!');

        } catch (\Exception $e) {
            Log::error(__METHOD__ . '-' . __LINE__, [
                'error'     => $e->getMessage(),
                'data'      => $data,
                'user_id'   => $data['created_by'] ?? null,
            ]);
            $this->setError('Erro ao enviar solicitação de CTe: ' . $e->getMessage());
        }
    }

}
