<?php

namespace App\Services\CteService;

use App\DTO\PayloadCteDTO;
use App\Models\CteEmailRequest;
use App\Traits\ServiceResponseTrait;
use App\Traits\UserCheckTrait;
use Illuminate\Support\Facades\Log;

class CteService
{
    use ServiceResponseTrait, UserCheckTrait;

    public function solicitarCtePorEmail(CteEmailRequest $request): void
    {
        try {
            $payloadDto = PayloadCteDTO::fromArray($request->payload ?? []);

            Log::debug('dados do payload DTO', [
                'método' => __METHOD__.'-'.__LINE__,
                'payloadDto' => $payloadDto->toArray(),
                'user_id' => $request->created_by,
            ]);

            $action = new Actions\EnviarSolicitacaoCte;
            $action->handle($payloadDto, $request);

            $this->setSuccess('Solicitação de CTe enviada com sucesso!');

        } catch (\Exception $e) {
            Log::error(__METHOD__.'-'.__LINE__, [
                'metodo' => __METHOD__.'@'.__LINE__,
                'error' => $e->getMessage(),
                'cte_email_request_id' => $request->id,
                'user_id' => $request->created_by,
            ]);
            $this->setError('Erro ao enviar solicitação de CTe: '.$e->getMessage());
        }
    }
}
