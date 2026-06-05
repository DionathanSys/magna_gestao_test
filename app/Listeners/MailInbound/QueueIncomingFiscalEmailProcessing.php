<?php

namespace App\Listeners\MailInbound;

use App\Events\MailInbound\IncomingEmailStored;
use App\Jobs\MailInbound\ProcessIncomingFiscalEmailJob;

class QueueIncomingFiscalEmailProcessing
{
    public function handle(IncomingEmailStored $event): void
    {
        ProcessIncomingFiscalEmailJob::dispatch($event->incomingEmailId)
            ->onQueue(config('mail-inbound.queue.process'));
    }
}
