<?php

namespace App\Jobs;

use App\Services\CteService\CteService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
        $service = new CteService();
        $service->solicitarCtePorEmail($this->data);
    }
}
