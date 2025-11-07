<?php

namespace App\Jobs;

use App\Services;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RegistrarHistoricoMovimentacao implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected array $dataMovimentacao)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        
        $service = new Services\Pneus\MovimentarPneuService();
        $service->registrarHistoricoMovimento($this->dataMovimentacao);
        if ($service->hasError()) {
            Log::error('Erro ao registrar histórico de movimentação de pneu.', [
                'metodo'            => __METHOD__ . '@' . __LINE__,
                'dataMovimentacao'  => $this->dataMovimentacao,
                'erro'              => $service->getMessage(),
            ]);
        }
        Log::debug("Registrando histórico de movimentação de pneu.", [
            'metodo' => __METHOD__ . '@' . __LINE__,
            'dataMovimentacao' => $this->dataMovimentacao
        ]);
    }
}
