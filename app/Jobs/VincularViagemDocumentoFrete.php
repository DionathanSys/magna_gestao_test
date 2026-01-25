<?php

namespace App\Jobs;

use App\Models\Viagem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class VincularViagemDocumentoFrete implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $documentoTransporte,
        protected ?int $viagemId = null,

    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        
        Log::info('Iniciando vinculação do documento de frete à viagem', [
            'documento_transporte'  => $this->documentoTransporte,
            'viagem_id'             => $this->viagemId,
        ]);

        $documentoFreteService = new Services\DocumentoFrete\DocumentoFreteService();
        $documentoFreteService->vincularDocumentoFrete($this->documentoTransporte, $this->viagemId);

        if ($documentoFreteService->hasError()) {
            Log::error('Erro ao vincular documento de frete à viagem', [
                'documento_transporte'  => $this->documentoTransporte,
                'message'               => $documentoFreteService->getMessage(),
                'errors'                => $documentoFreteService->getData(),
            ]);
        }
    }
}
