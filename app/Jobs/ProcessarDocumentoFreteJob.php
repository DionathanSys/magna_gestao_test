<?php

namespace App\Jobs;

use App\Contracts\XlsxImportInterface;
use App\Services;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessarDocumentoFreteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $importer, public string $fileName)
    {
    }

    public function handle(): void
    {
        try {

            Log::debug(__METHOD__, [
                'importer' => $this->importer,
                'fileName' => $this->fileName,
            ]);

            $importerClass = new $this->importer();
            $service = new Services\DocumentoFrete\DocumentoFreteService();
            $service->importarRelatorioDocumentoFrete($importerClass, $this->fileName);

            Log::debug(__METHOD__ . ' Job Finalizado');
            
        } catch (\Exception $e) {
            Log::error(__METHOD__, [
                'error' => $e->getMessage(),
                'class_importer' => $this->importer,
                'fileName' => $this->fileName,
            ]);
        }
    }
}
