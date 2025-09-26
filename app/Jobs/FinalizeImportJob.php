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
        private int $importLogId
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

        if (!$importLog) {
            return;
        }

        // Verificar se todos os batches foram processados
        if ($importLog->processed_batches >= $importLog->total_batches) {
            $importLog->markAsCompleted();

            Log::info("Importação finalizada", [
                'import_log_id' => $this->importLogId,
                'total_rows' => $importLog->total_rows,
                'success_rows' => $importLog->success_rows,
                'error_rows' => $importLog->error_rows,
            ]);
        } else {
            // Se ainda há batches pendentes, reagendar para mais tarde
            FinalizeImportJob::dispatch($this->importLogId)->delay(now()->addSeconds(30));
        }
    }
}
