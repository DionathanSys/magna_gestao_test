<?php

namespace App\Jobs;

use App\Models;
use App\Contracts\ExcelImportInterface;
use App\Enum\Import\StatusImportacaoEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessImportRowJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private array   $batch,
        private array   $headers,
        private string  $importerClass,
        private int     $importLogId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        //mone o foreach para  a batch
        foreach ($this->batch as $index => $row) {
            Log::debug("Iniciando processamento do lote para ImportLog ID: {$this->importLogId}, Linha: {$row}");
        }


    }
}
