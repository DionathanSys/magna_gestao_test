<?php

namespace App\Jobs;

use App\{Models, Enum, Services};
use App\Contracts\ExcelImportInterface;
use App\Enum\Import\StatusImportacaoEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessImportRowJob implements ShouldQueue
{
    use Queueable;

    protected Services\Import\ImportLogService $importLogService;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private array   $batch,
        private array   $headers,
        private string  $importerClass,
        private int     $importLogId
    ) {
        $this->importLogService = new Services\Import\ImportLogService($importLogId);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $importer = app($this->importerClass);

        foreach ($this->batch as $index => $row) {

            $rowNumber = $index + 2;
            $rowData = array_combine($this->headers, $row);

            try {
                $importer->process($rowData, $rowNumber);
                if ($importer->hasError()) {
                    Log::info("Erro ao processar linha {$rowNumber}");
                    $this->importLogService->incrementErrorRows($importer->getData());
                    continue;
                }
                Log::info("Linha {$rowNumber} processada com sucesso.");
                $this->importLogService->incrementSuccessRows();
            } catch (\Exception $e) {
                Log::error("Erro ao processar linha {$rowNumber}", [
                    'metodo' => __METHOD__ . '@' . __LINE__,
                    'exception' => $e->getMessage(),
                    'row' => $rowData
                ]);
                $this->importLogService->incrementErrorRows([
                    "Linha {$rowNumber}: " . $e->getMessage()
                ]);
            }
        }

        $this->importLogService->incrementBatchProcessed();
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Falha ao processar Job', [
            'metodo' => __METHOD__ . '@' . __LINE__,
            'exception' => $exception->getMessage()
        ]);
    }
}

