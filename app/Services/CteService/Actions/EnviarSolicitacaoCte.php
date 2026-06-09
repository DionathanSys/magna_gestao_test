<?php

namespace App\Services\CteService\Actions;

use App\DTO\PayloadCteDTO;
use App\Mail\SolicitacaoCteMail;
use App\Services\Bugio\CteEmailRequestService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarSolicitacaoCte
{
    public function handle(PayloadCteDTO $payloadCteDTO): void
    {
        $mail = new SolicitacaoCteMail($payloadCteDTO);
        $requestService = app(CteEmailRequestService::class);
        $request = $requestService->createPendingRequest($payloadCteDTO, $mail);

        try {
            Mail::send($mail);
            $requestService->markSent($request);
        } catch (\Throwable $exception) {
            $requestService->markSendFailed($request, $exception->getMessage());

            Log::error('Falha ao enviar solicitacao de CTe e persistir request', [
                'cte_email_request_id' => $request->id,
                'viagem_id' => $payloadCteDTO->viagemId,
                'documento_transporte' => $payloadCteDTO->documentoTransporte,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
