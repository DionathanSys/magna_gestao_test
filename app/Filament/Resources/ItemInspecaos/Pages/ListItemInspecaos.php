<?php

namespace App\Filament\Resources\ItemInspecaos\Pages;

use App\Filament\Resources\ItemInspecaos\ItemInspecaoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListItemInspecaos extends ListRecords
{
    protected static string $resource = ItemInspecaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
