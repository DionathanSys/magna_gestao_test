<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class VincularViagensBatch implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Collection $viagens
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->viagens->each(function ($viagem) {
            if(!$viagem->documento_transporte || !is_numeric($viagem->documento_transporte)) {
                Log::warning('Viagem sem documento de transporte ou valor inválido, não será vinculada.', [
                    'metodo' => __METHOD__ . '@' . __LINE__,
                    'viagem_id' => $viagem->id,
                    'documento_transporte' => $viagem->documento_transporte,
                ]);
                return;
            }
            Log::info('Despachando job de vinculação do documento de frete à viagem em lote', [
                'metodo'                    => __METHOD__ . '@' . __LINE__,
                'viagem_id'                 => $viagem->id,
                'documento_transporte'      => $viagem->documento_transporte,
                'viagemIdType'              => gettype($viagem->id),
                'documento_transporteType'  => gettype($viagem->documento_transporte),
            ]);
            VincularViagemDocumentoFrete::dispatch($viagem->documento_transporte, $viagem->id);
        });
    }
}
