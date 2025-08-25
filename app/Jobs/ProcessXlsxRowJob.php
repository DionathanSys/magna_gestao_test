<?php

namespace App\Jobs;

use App\Contracts\XlsxImportInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
}
