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
        private array $batch,
        private array $headers,
        private string $importerClass,
        private int $importLogId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        // try {

        //     $importLog = Models\ImportLog::find($this->importLogId);

        //     Log::debug('Iniciando job de processamento de lote', [
        //         'batch_size' => count($this->batch),
        //         'importer' => $this->importerClass,
        //     ]);

        //     $importer = app($this->importerClass);

        //     if (!$importLog) {
        //         Log::error("ImportLog não encontrado: {$this->importLogId}");
        //         return;
        //     }

        //     $processedRows = 0;
        //     $successRows = 0;
        //     $errorRows = 0;
        //     $errors = [];
        //     $warnings = [];

        //     // Processar cada linha do batch
        //     foreach ($this->batch as $index => $row) {

        //         $rowNumber = $index + 2;

        //         try {

        //             $rowData = array_combine($this->headers, $row);

        //             if (!$rowData) {
        //                 throw new \Exception("Erro ao combinar headers com dados");
        //             }

        //             Log::debug('Processando linha do lote', [
        //                 'metodo' => __METHOD__ . '@' . __LINE__,
        //                 'rowData' => $rowData,
        //                 'row_index' => $index,
        //                 'row_data' => $row,
        //             ]);

        //             $validationErrors = $importer->validate($rowData, $rowNumber);

        //             if (!empty($validationErrors)) {
        //                 $errors[] = "Linha {$rowNumber}: " . implode(', ', $validationErrors);
        //                 $errorRows++;
        //                 $processedRows++;

        //                 Log::debug('Erro de validação na linha', [
        //                     'row_index' => $rowNumber,
        //                     'errors'    => $validationErrors,
        //                     'errorRows' => $errorRows,
        //                     'processedRows' => $processedRows,
        //                 ]);
        //                 continue;
        //             }

        //             // Transformar e processar
        //             $transformedData = $importer->transform($rowData);
        //             $result = $importer->process($transformedData);

        //             Log::debug('Resultado do processamento da linha', [
        //                 'row_index' => $rowNumber,
        //                 'result' => $result,
        //             ]);

        //             if ($result) {
        //                 $successRows++;
        //                 Log::debug("Linha {$rowNumber} processada com sucesso");
        //             } else {
        //                 $errorRows++;
        //                 $errors[] = "Linha {$rowNumber}: Erro no processamento";
        //             }

        //             $processedRows++;

        //         } catch (\Exception $e) {

        //             $errorRows++;
        //             $errors[] = "Linha {$rowNumber}: " . $e->getMessage();
        //             $processedRows++;

        //             Log::error('Erro ao processar linha', [
        //                 'import_log_id' => $this->importLogId,
        //                 'row_index' => $rowNumber,
        //                 'error' => $e->getMessage(),
        //             ]);
        //         }
        //     }

        //     // Atualizar o ImportLog com as estatísticas do batch
        //     $importLog->increment('processed_batches');
        //     $importLog->increment('total_rows', $processedRows);
        //     $importLog->increment('success_rows', $successRows);
        //     $importLog->increment('error_rows', $errorRows);

        //     // Mesclar erros existentes com novos erros
        //     $existingErrors = json_decode($importLog->errors ?? '[]', true);
        //     $allErrors = array_merge($existingErrors, $errors);

        //     $existingWarnings = json_decode($importLog->warnings ?? '[]', true);
        //     $allWarnings = array_merge($existingWarnings, $warnings);

        //     $importLog->update([
        //         'errors' => json_encode($allErrors),
        //         'warnings' => json_encode($allWarnings),
        //     ]);

        //     Log::info('Batch processado', [
        //         'processed' => $processedRows,
        //         'success' => $successRows,
        //         'errors' => $errorRows
        //     ]);

        // } catch (\Exception $e) {

        //     $importLog = Models\ImportLog::find($this->importLogId);

        //     $importLog->update([
        //         'status' => StatusImportacaoEnum::FALHOU,
        //         'finished_at' => now(),
        //         'total_rows' => $processedRows,
        //         'success_rows' => $successRows,
        //         'error_rows' => $errorRows,
        //     ]);

        //     Log::error('Erro ao processar batch de importação', [
        //         'import_log_id' => $this->importLogId,
        //         'error' => $e->getMessage(),
        //     ]);

        // }
    }
}
