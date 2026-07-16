<?php

namespace App\Filament\Resources\VeiculoDocumentos\Pages;

use App\Filament\Resources\VeiculoDocumentos\VeiculoDocumentoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVeiculoDocumento extends EditRecord
{
    protected static string $resource = VeiculoDocumentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
