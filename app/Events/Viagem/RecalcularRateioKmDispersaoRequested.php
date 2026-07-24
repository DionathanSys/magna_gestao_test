<?php

namespace App\Events\Viagem;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RecalcularRateioKmDispersaoRequested
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $viagemId,
        public ?string $reason = null,
    ) {}
}
