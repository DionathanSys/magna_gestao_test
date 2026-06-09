<?php

namespace App\Jobs\MailInbound;

use App\Services\Bugio\CteReturnEmailProcessingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessIncomingBugioCteReturnEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $incomingEmailId) {}

    public function handle(CteReturnEmailProcessingService $service): void
    {
        $service->process($this->incomingEmailId);
    }
}
