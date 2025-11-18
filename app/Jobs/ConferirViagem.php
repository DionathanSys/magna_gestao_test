<?php

namespace App\Jobs;

use App\{Models, Services};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ConferirViagem implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Models\Viagem $viagem
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if(! $this->viagem->conferido) {
            $service = new Services\Viagem\ViagemService();
                $service->marcarViagemComoConferida($this->viagem);
                if ($service->hasError()) {
                    Log::error('Erro ao marcar viagem como conferida', [
                        'viagem_id' => $this->viagem->id,
                        'error' => $service->getMessage()
                    ]);
                    return;
                }

        } else {
             $service = new Services\Viagem\ViagemService();
                $service->marcarViagemComoNãoConferida($this->viagem);
                if ($service->hasError()) {
                    Log::error('Erro ao marcar viagem como não conferida', [
                        'viagem_id' => $this->viagem->id,
                        'error' => $service->getMessage()
                    ]);
                    return;
                }
        }
    }
}
