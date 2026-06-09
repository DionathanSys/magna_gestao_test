<?php

namespace App\Listeners\MailInbound;

use App\Events\MailInbound\IncomingEmailStored;
use App\Jobs\MailInbound\ProcessIncomingBugioCteReturnEmailJob;

class QueueIncomingBugioCteReturnProcessing
{
    public function handle(IncomingEmailStored $event): void
    {
        ProcessIncomingBugioCteReturnEmailJob::dispatch($event->incomingEmailId)
            ->onQueue(config('mail-inbound.queue.cte_return'));
    }
}
