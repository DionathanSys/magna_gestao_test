<?php

namespace App\Listeners\Viagem;

use App\Events\Viagem\RecalcularRateioKmDispersaoRequested;
use App\Services\Carga\CargaService;

class AtualizarRateioKmDispersaoCargas
{
    public function handle(RecalcularRateioKmDispersaoRequested $event): void
    {
        (new CargaService)->atualizarKmDispersao($event->viagemId);
    }
}
