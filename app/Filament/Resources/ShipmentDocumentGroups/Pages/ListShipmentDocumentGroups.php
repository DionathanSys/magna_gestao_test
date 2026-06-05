<?php

namespace App\Filament\Resources\ShipmentDocumentGroups\Pages;

use App\Filament\Resources\ShipmentDocumentGroups\ShipmentDocumentGroupResource;
use Filament\Resources\Pages\ListRecords;

class ListShipmentDocumentGroups extends ListRecords
{
    protected static string $resource = ShipmentDocumentGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
