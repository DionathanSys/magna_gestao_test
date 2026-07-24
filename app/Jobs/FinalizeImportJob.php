<?php

namespace App\Jobs;

use App\Models\ImportLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FinalizeImportJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private int $importLogId,
        private string $importerClass,
    ) {
        // Fazer este job rodar por último
        $this->delay(now()->addSeconds(30));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $importLog = ImportLog::find($this->importLogId);

        if (! $importLog) {
            Log::alert('ImportLog não encontrado', [
                'import_log_id' => $this->importLogId,
            ]);

            return;
        }

        // Verificar se todos os batches foram processados
        if ($importLog->processed_batches >= $importLog->total_batches) {
            $importer = app($this->importerClass);

            if (method_exists($importer, 'finalizeImport')) {
                $importer->finalizeImport($this->importLogId);
            }

            $importLog->markAsCompleted();

            Log::info('Importação finalizada', [
                'import_log_id' => $this->importLogId,
                'total_rows' => $importLog->total_rows,
                'success_rows' => $importLog->success_rows,
                'error_rows' => $importLog->error_rows,
            ]);
        } elseif // Se ainda há batches pendentes, reagendar para mais tarde
        ($importLog->total_rows <= $importLog->processed_rows) {
            // Se todas as linhas foram processadas, mas os batches não conferem, marcar como erro
            $importLog->markAsFailed('Inconsistência nos batches processados.');
            Log::error('Inconsistência nos batches processados', [
                'import_log_id' => $this->importLogId,
                'processed_batches' => $importLog->processed_batches,
                'total_batches' => $importLog->total_batches,
            ]);
        } else {
            Log::info('Importação ainda em andamento, reagendando finalização', [
                'import_log_id' => $this->importLogId,
                'processed_batches' => $importLog->processed_batches,
                'total_batches' => $importLog->total_batches,
            ]);
            FinalizeImportJob::dispatch($this->importLogId, $this->importerClass)->delay(now()->addSeconds(30));

        }
    }
}
