<?php

namespace App\Filament\Resources\ViagemComplementos\Pages;

use App\Filament\Resources\ViagemComplementos\ViagemComplementoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditViagemComplemento extends EditRecord
{
    protected static string $resource = ViagemComplementoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
