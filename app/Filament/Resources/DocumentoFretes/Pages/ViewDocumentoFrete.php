<?php

namespace App\Filament\Resources\DocumentoFretes\Pages;

use App\Filament\Resources\DocumentoFretes\DocumentoFreteResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDocumentoFrete extends ViewRecord
{
    protected static string $resource = DocumentoFreteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
