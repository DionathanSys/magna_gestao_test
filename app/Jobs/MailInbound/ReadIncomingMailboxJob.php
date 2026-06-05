<?php

namespace App\Jobs\MailInbound;

use App\Services\MailInbound\InboundMessageIngestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ReadIncomingMailboxJob implements ShouldQueue
{
    use Queueable;

    public function handle(InboundMessageIngestionService $service): void
    {
        $service->ingest();
    }
}
