<?php

namespace App\Filament\Resources\DocumentoFretes\Pages;

use App\Filament\Resources\DocumentoFretes\DocumentoFreteResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDocumentoFrete extends EditRecord
{
    protected static string $resource = DocumentoFreteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
