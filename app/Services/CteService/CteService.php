<?php

namespace App\Services\CteService;

use App\DTO\PayloadCteDTO;
use App\Services\CteService\Actions;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CteService
{
    use ServiceResponseTrait;

    public function solicitarCtePorEmail(array $data)
    {
        Log::debug(__METHOD__ . '-' . __LINE__, [
            'data' => $data,
            'user_id' => Auth::id() ?? 'N/A',
        ]);

        try {

            Log::debug(__METHOD__ . '-' . __LINE__, [
                'data' => $data,
                'user_id' => Auth::id() ?? 'N/A',
            ]);

            $data['motorista']['nome'] = collect(db_config('config-bugio.motoristas'))->firstWhere('cpf', $data['motorista']['cpf'] ?? null)['motorista'] ?? null;
            $data['valor_frete'] = db_config('config-bugio.valor-quilometro', 0);

            $payloadDto = PayloadCteDTO::fromArray($data);

            Log::debug(__METHOD__ . '-' . __LINE__, [
                'payloadDto' => $payloadDto->toArray(),
                'user_id' => Auth::id() ?? 'N/A',
            ]);

            if (!$payloadDto->isValid()){
                Log::warning(__METHOD__.'-'.__LINE__, [
                    'errors' => $payloadDto->errors,
                    'user_id' => Auth::id() ?? 'N/A',
                ]);
                throw new \InvalidArgumentException('Dados inválidos: ' . implode(', ', $payloadDto->errors));
            }

            $action = new Actions\EnviarSolicitacaoCte();
            $action->handle($payloadDto);

            $this->setSuccess('Solicitação de CTe enviada com sucesso!');

        } catch (\Exception $e) {
            Log::error(__METHOD__ . '-' . __LINE__, [
                'error' => $e->getMessage(),
                'data' => $data,
                'user_id' => Auth::id() ?? 'N/A',
            ]);
            $this->setError('Erro ao enviar solicitação de CTe: ' . $e->getMessage());
        }
    }
}
