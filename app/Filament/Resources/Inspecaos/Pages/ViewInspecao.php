<?php

namespace App\Filament\Resources\Inspecaos\Pages;

use App\Filament\Resources\Inspecaos\InspecaoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInspecao extends ViewRecord
{
    protected static string $resource = InspecaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
