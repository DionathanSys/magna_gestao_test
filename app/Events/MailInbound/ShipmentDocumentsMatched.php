<?php

namespace App\Events\MailInbound;

class ShipmentDocumentsMatched
{
    public function __construct(public int $shipmentDocumentGroupId)
    {
    }
}
