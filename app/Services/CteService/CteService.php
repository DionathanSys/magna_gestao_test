<?php

namespace App\Services\CteService;

use App\DTO\PayloadCteDTO;
use App\Services\CteService\Actions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CteService
{

    public function solicitarCtePorEmail(array $data)
    {
        Log::debug(__METHOD__ . '-' . __LINE__, [
            'data' => $data,
            'user_id' => Auth::id() ?? 'N/A',
        ]);

        try {

            $payloadDto = PayloadCteDTO::fromArray($data);
            if (!$payloadDto->isValid()){
                Log::warning(__METHOD__.'-'.__LINE__, [
                    'errors' => $payloadDto->errors,
                    'user_id' => Auth::id() ?? 'N/A',
                ]);
                throw new \InvalidArgumentException('Dados inválidos: ' . implode(', ', $payloadDto->errors));
            }

            Log::debug('payloadDto', [
                'payload' => $payloadDto
            ]);

            $action = new Actions\EnviarSolicitacaoCte($payloadDto);
            $action->handle();

        } catch (\Exception $e) {
            Log::error(__METHOD__ . '-' . __LINE__, [
                'error' => $e->getMessage(),
                'data' => $data,
                'user_id' => Auth::id() ?? 'N/A',
            ]);
        }
    }
}
