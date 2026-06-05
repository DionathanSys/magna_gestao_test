<?php

namespace App\Listeners\MailInbound;

use App\Events\MailInbound\ShipmentDocumentsMatched;
use App\Jobs\MailInbound\CreateTripFromShipmentDocumentsJob;

class QueueMatchedShipmentTripCreation
{
    public function handle(ShipmentDocumentsMatched $event): void
    {
        CreateTripFromShipmentDocumentsJob::dispatch($event->shipmentDocumentGroupId)
            ->onQueue(config('mail-inbound.queue.trip'));
    }
}
