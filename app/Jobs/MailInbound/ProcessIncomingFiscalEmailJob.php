<?php

namespace App\Jobs\MailInbound;

use App\Services\MailInbound\FiscalEmailProcessingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessIncomingFiscalEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $incomingEmailId) {}

    public function handle(FiscalEmailProcessingService $service): void
    {
        $service->process($this->incomingEmailId);
    }
}
