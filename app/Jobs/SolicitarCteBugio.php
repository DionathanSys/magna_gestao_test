<?php

namespace App\Jobs;

use App\Services\CteService\CteService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SolicitarCteBugio implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected array $data)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $service = new CteService();
            $service->solicitarCtePorEmail($this->data);
        } catch (\Exception $e) {
            Log::error('Erro ao solicitar CTE: ' . $e->getMessage(), [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'data' => $this->data,
            ]);
        }
    }
}
