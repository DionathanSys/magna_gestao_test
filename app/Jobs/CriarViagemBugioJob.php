<?php

namespace App\Jobs;

use App\{Models,Services};
use Throwable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CriarViagemBugioJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected array $data
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $service = new Services\ViagemBugio\ViagemBugioService();
        $service->criarViagem($this->data);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Falha ao processar Job Criar Viagem Bugio', [
            'metodo' => __METHOD__ . '@' . __LINE__,
            'data' => $this->data,
            'exception' => $exception?->getMessage()
        ]);
    }
}
