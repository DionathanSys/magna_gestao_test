<?php

namespace App\Filament\Resources\ShipmentDocumentGroups\Pages;

use App\Filament\Resources\ShipmentDocumentGroups\ShipmentDocumentGroupResource;
use Filament\Resources\Pages\ViewRecord;

class ViewShipmentDocumentGroup extends ViewRecord
{
    protected static string $resource = ShipmentDocumentGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
