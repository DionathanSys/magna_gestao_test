<?php

namespace App\Jobs;

use App\Services\CteService\CteService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Services\NotificacaoService as notify;

class SolicitarCteBugio implements ShouldQueue
{
    use Queueable;

    public $tries = 3; // Tentar 3 vezes
    public $backoff = 60; // Aguardar 60 segundos entre tentativas
    public $timeout = 300; // Timeout de 5 minutos

    /**
     * Create a new job instance.
     */
    public function __construct(protected array $data)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $service = new CteService();
            $service->solicitarCtePorEmail($this->data);

            notify::success('Solicitação de CTe enviada com sucesso!', "#Placa: " . ($this->data['veiculo'] ?? 'N/A') . ' | Notas: ' . ($this->data['nro_notas'] ?? 'N/A' . ' | Integrado: ' . ($this->data['destinos']['integrado_nome'] ?? 'N/A')), true);
            
        } catch (\Exception $e) {
            notify::error('Erro ao solicitar CTe', "Placa: " . ($this->data['veiculo'] ?? 'N/A') . ' | Notas: ' . ($this->data['nro_notas'] ?? 'N/A' . ' | Integrado: ' . ($this->data['destinos']['integrado_nome'] ?? 'N/A')), true);
            Log::error('Erro ao solicitar CTE: ' . $e->getMessage(), [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'data' => $this->data,
            ]);
        }
    }
}
