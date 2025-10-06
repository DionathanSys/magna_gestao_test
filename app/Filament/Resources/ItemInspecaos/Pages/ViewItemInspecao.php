<?php

namespace App\Filament\Resources\ItemInspecaos\Pages;

use App\Filament\Resources\ItemInspecaos\ItemInspecaoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewItemInspecao extends ViewRecord
{
    protected static string $resource = ItemInspecaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
