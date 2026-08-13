<?php

namespace App\Jobs;

use App\Services\HistoricoQuilometragem\HistoricoQuilometragemService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RegistrarQuilometragem implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private array $registro,
        private string $loteId,
        private string $requestId,
    ) {}

    public function handle(HistoricoQuilometragemService $service): void
    {
        $historico = $service->registrar([
            'veiculo_id' => $this->registro['veiculo_id'],
            'data_referencia' => $this->registro['data_referencia'],
            'quilometragem' => $this->registro['quilometragem'],
        ]);

        if ($historico === null) {
            Log::error('Falha ao registrar quilometragem do lote', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'request_id' => $this->requestId,
                'lote_id' => $this->loteId,
                'index' => $this->registro['index'],
                'placa' => $this->registro['placa'],
                'errors' => $service->getErrors(),
            ]);

            return;
        }

        Log::info('Quilometragem do lote registrada', [
            'metodo' => __METHOD__.'@'.__LINE__,
            'request_id' => $this->requestId,
            'lote_id' => $this->loteId,
            'index' => $this->registro['index'],
            'historico_quilometragem_id' => $historico->id,
        ]);
    }
}
