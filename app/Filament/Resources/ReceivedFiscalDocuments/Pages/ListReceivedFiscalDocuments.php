<?php

namespace App\Filament\Resources\ReceivedFiscalDocuments\Pages;

use App\Filament\Resources\ReceivedFiscalDocuments\ReceivedFiscalDocumentResource;
use Filament\Resources\Pages\ListRecords;

class ListReceivedFiscalDocuments extends ListRecords
{
    protected static string $resource = ReceivedFiscalDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
