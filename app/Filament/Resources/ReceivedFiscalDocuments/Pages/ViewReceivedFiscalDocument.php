<?php

namespace App\Filament\Resources\ReceivedFiscalDocuments\Pages;

use App\Filament\Resources\ReceivedFiscalDocuments\ReceivedFiscalDocumentResource;
use Filament\Resources\Pages\ViewRecord;

class ViewReceivedFiscalDocument extends ViewRecord
{
    protected static string $resource = ReceivedFiscalDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
