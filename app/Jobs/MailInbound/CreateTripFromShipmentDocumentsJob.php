<?php

namespace App\Jobs\MailInbound;

use App\Services\MailInbound\ShipmentTripService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateTripFromShipmentDocumentsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $shipmentDocumentGroupId) {}

    public function handle(ShipmentTripService $service): void
    {
        $service->createFromGroup($this->shipmentDocumentGroupId);
    }
}
