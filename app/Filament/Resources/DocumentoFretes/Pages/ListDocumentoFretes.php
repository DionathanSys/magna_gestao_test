<?php

namespace App\Filament\Resources\DocumentoFretes\Pages;

use App\Filament\Resources\DocumentoFretes\DocumentoFreteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocumentoFretes extends ListRecords
{
    protected static string $resource = DocumentoFreteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
