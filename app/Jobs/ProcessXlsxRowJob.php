<?php

namespace App\Jobs;

use App\Contracts\XlsxImportInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessXlsxRowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $row, public string $importerClass)
    {
    }

    public function handle()
    {
        $importer = new $this->importerClass();
        $importer->processRow($this->row);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Falha ao processar Job ProcessXlsxRowJob', [
            'metodo' => __METHOD__ . '@' . __LINE__,
            'data' => $this->row,
            'exception' => $exception?->getMessage()
        ]);
    }
}
