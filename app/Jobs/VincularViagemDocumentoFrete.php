<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services;
use Illuminate\Support\Facades\Log;

class VincularViagemDocumentoFrete implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $documentoTransporte
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $documentoFreteService = new Services\DocumentoFrete\DocumentoFreteService();
        $documentoFreteService->vincularDocumentoFrete($this->documentoTransporte);

        if($documentoFreteService->hasError()) {
            Log::error('Erro ao vincular documento de frete à viagem', [
                'documento_transporte'  => $this->documentoTransporte,
                'errors'                => $documentoFreteService->getData(),
            ]);
        }
    }
}
