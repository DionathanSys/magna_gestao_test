<?php

namespace App\Jobs;

use App\Models;
use App\Contracts\ExcelImportInterface;
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
        private array $batch,
        private array $headers,
        private ExcelImportInterface $importer,
        private int $importLogId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $importLog = Models\ImportLog::find($this->importLogId);

        foreach ($this->batch as $index => $row) {
            try {
                $rowData = array_combine($this->headers, $row);

                $validationErrors = $this->importer->validate($rowData, $index + 2);
                if (!empty($validationErrors)) {
                    Log::alert('Erro de validação na linha', [
                        'import_log_id' => $this->importLogId,
                        'row_index' => $index + 2,
                        'errors' => $validationErrors,
                    ]);
                }
                $transformedData = $this->importer->transform($rowData);
                $this->importer->process($transformedData);

            } catch (\Exception $e) {
                Log::error('Erro ao processar linha', [
                    'import_log_id' => $this->importLogId,
                    'row_index' => $index + 2,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Atualizar progresso no ImportLog
        $importLog->increment('processed_batches');

    }
}
