<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Pages;

use App\Filament\Bugio\Resources\ViagemBugios\ViagemBugioResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewViagemBugio extends ViewRecord
{
    protected static string $resource = ViagemBugioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
