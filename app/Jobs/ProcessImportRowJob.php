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
        // private ExcelImportInterface $importer,
        private string $importerClass,
        private int $importLogId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::debug('Iniciando processamento de lote', [
            'import_log_id' => $this->importLogId,
            'batch_size' => count($this->batch),
        ]);

        $importLog = Models\ImportLog::find($this->importLogId);

        $importer = app($this->importerClass);

        foreach ($this->batch as $index => $row) {
            Log::debug('Processando linha do lote', [
                'import_log_id' => $this->importLogId,
                'row_index' => $index + 2, // +2 para considerar o cabeçalho e índice 0
            ]);
            try {
                $rowData = array_combine($this->headers, $row);

                $validationErrors = $importer->validate($rowData, $index + 2);
                if (!empty($validationErrors)) {
                    Log::alert('Erro de validação na linha', [
                        'import_log_id' => $this->importLogId,
                        'row_index' => $index + 2,
                        'errors' => $validationErrors,
                    ]);
                }
                $transformedData = $importer->transform($rowData);
                $importer->process($transformedData);

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
