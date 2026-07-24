<?php

namespace App\Jobs;

use App\Services;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessImportRowJob implements ShouldQueue
{
    use Queueable;

    protected Services\Import\ImportLogService $importLogService;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private array $batch,
        private array $headers,
        private string $importerClass,
        private int $importLogId,
        private int $startRowNumber = 2,
    ) {
        $this->importLogService = new Services\Import\ImportLogService($importLogId);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        Log::debug('Iniciando processamento do lote para import_log_id: '.$this->importLogId, [
            'metodo' => __METHOD__.'@'.__LINE__,
            'batch_size' => count($this->batch),
        ]);

        $importer = app($this->importerClass);

        if (method_exists($importer, 'setImportContext')) {
            $importer->setImportContext($this->importLogId);
        }

        foreach ($this->batch as $index => $row) {
            if (method_exists($importer, 'clearResponse')) {
                $importer->clearResponse();
            }

            $rowNumber = $this->startRowNumber + $index;
            $rowData = array_combine($this->headers, $row);

            Log::info('Linha bruta do lote de importacao de viagens', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'import_log_id' => $this->importLogId,
                'row_number' => $rowNumber,
                'headers' => $this->headers,
                'row_data' => $rowData,
            ]);

            try {
                $importer->process($rowData, $rowNumber);
                if ($importer->hasError()) {
                    Log::error("Linha {$rowNumber} falhou ao processar", [
                        'metodo' => __METHOD__.'@'.__LINE__,
                        'row' => $rowData,
                        'errors' => $importer->getErrors(),
                    ]);
                    $this->importLogService->incrementErrorRows($importer->getErrors());

                    continue;
                }

                $this->importLogService->incrementSuccessRows();
            } catch (\Exception $e) {
                Log::error("Erro ao processar linha {$rowNumber}", [
                    'metodo' => __METHOD__.'@'.__LINE__,
                    'exception' => $e,
                    'row' => $rowData,
                ]);
                $this->importLogService->incrementErrorRows([
                    "Linha {$rowNumber}: ".$e->getMessage(),
                ]);
            }
        }

        $this->importLogService->incrementBatchProcessed();
        Log::info('Lote processado para import_log_id: '.$this->importLogId);

    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Falha ao processar Job import_log_id: '.$this->importLogId, [
            'metodo' => __METHOD__.'@'.__LINE__,
            'import_log_id' => $this->importLogId,
            'batch' => $this->batch,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
